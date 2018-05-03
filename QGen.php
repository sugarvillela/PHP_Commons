<?php
/* Why use a query generator? Query language is intuitive, simple and versatile.
 * Many frameworks like Laravel have wrapper functions, where you can pass in some
 * values and get generated queries.  But that just seems like trading one syntax
 * for another.  What's the point?
 * 
 * Reasons to use a query generator:
 * 1. Data comes in array form. Adapting it to string form is something you end
 *    up doing over and over again.  You're already generating queries with your
 *    ugly code.  Maybe it's better to centralize the process.
 * 2. Centralization means you can alter ALL the queries in your application 
 *    from one spot.  If you want to share tables between two workgroups you can
 *    add an identifier to every WHERE clause to route data.  If you want to
 *    get rid of that, you can (like I did when I replaced my special clause
 *    with a 1 on line 481 below)
 * 3. Centralization means you can also treat all incoming and outgoing data in
 *    the same place (sanitizing input or processing output in some way)
 * 4. Maybe you're just sick of typing SELECT and you want to type array() a
 *    bunch of times
 * 
 * How to use this tool:
 * Look at QGenTest(); Most fields are demonstrated there. Mix and match
 * For additional fields, read the (very long) comment in the sel() function.
 * For explanation of 'skipUntil', 'haltOn', 'pairsHasSkips' etc
 * see QGen::_arrayToString()
 * Supports most of the simpler things like...
 * AND, OR, ranges, like, in, isNull, isNotNull, order by, desc, limit, offset,
 * group by, concat, concat as, incrementing/decrementing a value on update
 * The only things not supported are JOINs and such
 */
function QGenTest(){
    $table='client';
    echo QGen::sel( array('table'=>$table ) )."<br>";//
    echo QGen::sel( array('table'=>$table, 'col'=>array( 'ID_TABLE','first_name' ) ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'where'=>array('first_name'=>'frank','last_name'=>'fruity') ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'where'=>array('first_name'=>'frank','last_name'=>'fruity'), 'cond'=>'OR' ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'like'=>array('first_name'=>'frank') ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'where'=>array('first_name'=>'frank'),'in'=>array( 'ID_TABLE'=>'2,3,4' ) ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'isNull'=>array( 'first_name' ) ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'isNotNull'=>array( 'first_name' ) ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'loBound'=>'2016-08-01 00:00:00' ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'loBound'=>'2016-08-01 00:00:00','noninclusive'=>true ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'loBound'=>'2016-08-01 00:00:00', 'hiBound'=>'2017-08-01 00:00:00' ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'loBound'=>'2016-08-01 00:00:00', 'hiBound'=>'2017-08-01 00:00:00','noninclusive_lo'=>true ) )."<br>";
    echo QGen::sel( array('table'=>$table, 'boundCol'=>'birthday','loBound'=>'2016-08-01 00:00:00' ) )."<br>";
    echo QGen::sel( array('table'=>$table,'orderBy'=>'first_name' ) )."<br>";//
    echo QGen::sel( array('table'=>$table,'orderBy'=>'first_name','desc'=>true ) )."<br>";//
    echo QGen::sel( array('table'=>$table,'orderBy'=>array('last_name', 'dob'), 'limit'=>'100','offset'=>'0' ) )."<br>";//
    /* */
    echo QGen::ins( array('table'=>$table, 'pairs'=>array( 'first_name'=>"Hank",'last_name'=>"Henry" ) ) )."<br>";
    echo QGen::upd( array('table'=>$table, 'pairs'=>array( 'first_name'=>"Hank",'last_name'=>"Henry" ) ) )."<br>";
    echo QGen::del( array('table'=>$table, 'where'=>array('ID_TABLE'=>'157') ) )."<br>";
    
    echo QGen::upd( array('table'=>$table, 'inc'=>array( 'num_clients'=>"1" ) ) )."<br>";
    /* Resulting output
    SELECT * FROM `company`.`client` ;
    SELECT `id_client`, `first_name` FROM `company`.`client` ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `first_name`='frank' AND `last_name`='fruity' ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `first_name`='frank' OR `last_name`='fruity' ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `first_name` LIKE 'frank%' ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `first_name`='frank' ) AND ( `id_client` IN ( 2,3,4 ) ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `0` IS NULL ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `0` IS NOT NULL ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `date` >= '2016-08-01 00:00:00' ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `date` > '2016-08-01 00:00:00' ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `date` >= '2016-08-01 00:00:00' AND `date` <= '2017-08-01 00:00:00' ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `date` > '2016-08-01 00:00:00' AND `date` <= '2017-08-01 00:00:00' ) ;
    SELECT * FROM `company`.`client` WHERE 1 AND ( `birthday` >= '2016-08-01 00:00:00' ) ;
    SELECT * FROM `company`.`client` ORDER BY `first_name` ;
    SELECT * FROM `company`.`client` ORDER BY `first_name` DESC ;
    SELECT * FROM `company`.`client` ORDER BY `last_name`, `dob` LIMIT 100 OFFSET 0 ;
    INSERT INTO `company`.`client` ( `first_name`, `last_name` ) VALUES ( 'Hank', 'Henry' );
    UPDATE `company`.`client` SET `first_name`='Hank', `last_name`='Henry' ;
    DELETE FROM `company`.`client` WHERE 1 AND ( `id_client`='157' );
    UPDATE `company`.`client` SET `num_clients` = `num_clients` + 1 ;
     */
}
class QGen{
    const skipUntil='op';//See QGen::_arrayToString()
    const haltOn='halt';

    static function sel( $param ){
        /* All possible fields to pass to the four functions
         *  ===================================================================
            $query=QGen::sel(//sel, ins, upd, del
                array(
                    'table'=>'client',//Include for all cases! No default
                //***SELECT fields***
                //***Column selection***
                    //'col'=>array( 'ID_TABLE','first_name' ),//val array, item or none=all (don't send quotes)
                    //'hasSkips_col'=>true,
                //***concat options***
                    //'concat'=>array( 'first_name','last_name' ),
                    //'concatAs'=>'name',
                    //'concatNoSpace'=>true,
                    //'hasSkips_concat'=>true,
                //***WHERE clause ( SELECT, UPDATE, DELETE )
                    //'where'=>$fields,//key=>val array or `key`='val' literal
                    //'hasSkips_where'=>true,
                    //'innerCond_where'=>'OR', //'cond'=>'OR'(also works for 'where')
                    //
                    //'like'=>array( 'last_name'=>"Boo" ),
                    //'hasSkips_like'=>true,
                    //'innerCond_like'=>'OR',
                    //'condPreceding_like'=>'OR'
                    //
                    //'in'=>array( 'ID_TABLE'=>'2,3, 4' ),
                    //'hasSkips_in'=>true,
                    //'innerCond_in'=>'OR',
                    //'condPreceding_in'=>'OR'
                    //
                    //'notIn'=>array( 'ID_TABLE'=>'2,3, 4' ),
                    //'hasSkips_notIn'=>true,
                    //'innerCond_notIn'=>'OR',
                    //'condPreceding_notIn'=>'OR'
                    //
                    //'isNull'=>array( 'day' ) //item or array
                    //'hasSkips_isNull'=>true,
                    //'innerCond_isNull'=>'OR',
                    //'condPreceding_isNull'=>'OR'
                    //
                    //'isNotNull'=>array( 'day' ) //item or array
                    //'hasSkips_isNotNull'=>true,
                    //'innerCond_isNotNull'=>'OR',
                    //'condPreceding_isNotNull'=>'OR'
                    //
                //***Extra 'where' field for versatility
                    //'keyEqualsValue'=>$fieldsAlt,//key=>val array or `key`='val' literal
                    //'hasSkips_keyEqualsValue'=>true,
                    //'innerCond_keyEqualsValue'=>'OR',
                    //'condPreceding_keyEqualsValue'=>'OR'
                    //
                //***date ranges***
                    //'loBound'=>'2016-08-01 14:35:55',//loBound, triggers >=loBound
                    //'hiBound'=>'2016-08-09 14:35:55',//hiBound, triggers <=hiBound
                    //'boundCol'=>'date_set',//overwrite default column name: `date`
                    //***Inclusive is: item >= loBound && item =< hiBound; default is inclusive
                    //'noninclusive'=>true,//item > loBound && item < hiBound 
                    //'noninclusive_lo'=>true,//item > loBound && item =< hiBound
                    ////'noninclusive_hi'=>true,//item >= loBound && item < hiBound
                //***Output specifiers (self-explanatory?)***
                    //'groupBy'=>//val array or item (don't send quotes)
                    //'orderBy'=>//val array or item (don't send quotes)
                    //'desc'=>true,//order by DESC
                    //'limit'=>'100',
                    //'offset'=>'0',
                //***INSERT***
                //***Simple way: key value array only
                    //'pairs'=>array( 'first_name'=>"Hank" )//key=>val array only
                    //'hasSkips_pairs'=>true,
                //***Alternate way: val array or item (don't send quotes)
                    //'col'=>array( 'first_name' ),
                    //'val'=>array( "Hank" ),
                //***UPDATE***
                    //'pairs'=>array( 'first_name'=>"Hank" )//key=>val array or `key`='val' literal
                    //'hasSkips_pairs'=>true,
                    //inc=>array('item'=>'1'),//increment a numeric column by value
                    //dec=>array('item'=>'2'),//increment a numeric column by value
                    //'hasSkips_inc'=>true,
                    //'hasSkips_dec'=>true,
                )
            );

         * ===================================================================*/
        $table=$param['table']; //no default table
        /* Table Id is a common item: caller can send 'ID_TABLE', a 
         * non-specific value, and it gets switched to the right value here,
         * assuming the format is as below */
        $param=QGen::searchReplaceAll( $param, 'ID_TABLE', "id_$table");
        $col=QGen::_colClause( $param, '*' );
        $where=QGen::_whereClause( $param );
        /* Output designators */
        $groupBy=isset( $param['groupBy'] )? $param['groupBy'] : "";
        $orderBy=isset( $param['orderBy'] )? $param['orderBy'] : "";
        $desc=isset( $param['desc'] )? 'DESC':'';//mysql defaults ASC
        $limit=isset( $param['limit'] )? ' LIMIT '.$param['limit'].' ' : "";
        $offset=isset( $param['offset'] )? ' OFFSET '.$param['offset'].' ' : "";
        /* Construct ORDER BY clause: send single value or array. Quotes are 
         * inserted so don't include them. Note: the order of the array matters
         */
        if( $orderBy ){
            if( is_array( $orderBy ) ){
                $orderBy=QGen::_arrayToString( 
                        $orderBy, 
                        new ValToCol_(),
                        '',
                        false
                    );
                $orderBy="ORDER BY $orderBy $desc";
            }
            else{
                $orderBy="ORDER BY `$orderBy` $desc";
            }  
        }
        /* Construct GROUP BY clause: send single value or array. Quotes are 
         * inserted so don't include them.
         */
        if( $groupBy ){
            if( is_array( $groupBy ) ){
                $groupBy="GROUP BY ".
                    QGen::_arrayToString( 
                        $groupBy,
                        new ValToCol_(),
                        '',
                        false
                    );
            }
            else{
                $groupBy="GROUP BY `$groupBy`"; 
            }
        }
        /* Assemble query */
        global $dbName;
        return "SELECT $col FROM `$dbName`.`$table` $where $groupBy $orderBy $limit $offset;";
    }
    static function ins( $param ){
        $skipUntil=QGen::skipUntil;//
        $table=$param['table']; //no default table
        $pairsHasSkips=( isset( $param['pairsHasSkips'] ) || isset( $param['hasSkips_pairs'] ));
        $pairs=isset( $param['pairs'] )? $param['pairs']:'';
        /* Construct pairs clause: pairs only makes sense as an array */
        if( $pairs ){
            $col=QGen::_arrayToString( 
                    $pairs, 
                    new KeyToKey_(),
                    '',
                    ( $pairsHasSkips )? $skipUntil:false
                );
            $val=QGen::_arrayToString( 
                    $pairs, 
                    new valToval_(),
                    '',
                    ( $pairsHasSkips )? $skipUntil:false
                );
        }
        else {
            $colHasSkips=( isset( $param['colHasSkips'] ) || isset( $param['hasSkips_col'] ));
            $valHasSkips=( isset( $param['valHasSkips'] ) || isset( $param['hasSkips_val'] ));
            $col=isset( $param['col'] )? $param['col'] : "";
            $val=isset( $param['val'] )? $param['val'] : "";
            /* Construct columns clause: send single value or array. Quotes are 
             * inserted so don't include them. */
            if( is_array( $col ) ){
                $col=QGen::_arrayToString( 
                        $col, 
                        new KeyToKey_(),
                        '',
                        ( $colHasSkips )? $skipUntil:false
                    );
            }
            else if( $col ){
                $col="`$col`";
            }
            else{
                return '';
            }
            /* Construct VALUES clause: send single value or array. Quotes are 
             * inserted so don't include them. */
            if( is_array( $val ) ){
                $val=
                    QGen::_arrayToString( 
                        $val, 
                        new ValToVal_(),
                        '',
                        ( $valHasSkips )? $skipUntil:false
                    );
            }
            else if( $val ){
                $val="'$val'";
            }
            else{
                return '';
            }
        }
        //$col.=", `site_id`";
        //$val.=", '".session::getSiteId()."'";
        /* Assemble query */
        global $dbName;
        return "INSERT INTO `$dbName`.`$table` ( $col ) VALUES ( $val );";
    }
    static function upd( $param ){
        $skipUntil=QGen::skipUntil;//
        $table=$param['table']; //no default table
        /* Table Id is a common item: caller can send 'ID_TABLE', a 
         * non-specific value, and it gets switched to the right value here,
         * assuming the format is as below */
        $param=QGen::searchReplaceAll( $param, 'ID_TABLE', "id_$table");
        $where=QGen::_whereClause( $param );
        /* Construct SET clause: send single key value pair or array. Quotes 
         * are inserted so don't include them. */
        $pairs="";
        $inc="";
        $dec="";
        if( isset( $param['pairs'] ) && is_array( $param['pairs'] ) ){
            $pairsHasSkips=( isset( $param['hasSkips_pairs'] ) );// || isset( $param['pairsHasSkips'] ) 
            $pairs.=QGen::_arrayToString( 
                    $param['pairs'], 
                    new KeyEqualsVal_(),
                    '',
                    ( $pairsHasSkips )? $skipUntil:false
                );
        }
        if( isset( $param['inc'] ) && is_array( $param['inc'] ) ){
            foreach ( $param['inc'] as $key => $value) {
                if( !is_numeric( $value ) ){
                    $param['inc'][$key]='1';
                }
            }
            $inc=QGen::_arrayToString( 
                    $param['inc'], 
                    new IncKeyByVal_(),
                    '',
                    ( isset( $param['hasSkips_inc'] ) )? $skipUntil:false
                );
        }
        if( isset( $param['dec'] ) && is_array( $param['dec'] ) ){
            foreach ( $param['dec'] as $key => $value) {
                if( !is_numeric( $value ) ){
                    $param['dec'][$key]='1';
                }
            }
            $dec=QGen::_arrayToString( 
                    $param['dec'], 
                    new DecKeyByVal_(),
                    '',
                    ( isset( $param['hasSkips_dec'] ) )? $skipUntil:false
                );
        }
        global $dbName;
        return "UPDATE `$dbName`.`$table` SET $pairs $inc $dec $where;";
    }
    static function del( $param ){
        $table=$param['table']; //no default table
        /* Table Id is a common item: caller can send 'ID_TABLE', a 
         * non-specific value, and it gets switched to the right value here,
         * assuming the format is as below */
        $param=QGen::searchReplaceAll( $param, 'ID_TABLE', "id_$table");
        $where=QGen::_whereClause( $param );
        global $dbName;
        return "DELETE FROM `$dbName`.`$table` $where;";
    }
    //support functions
    static function keyValAt( $array, &$returnKey, &$returnVal, $index=0 ){
        $i=0;
        foreach ( $array as $key => $value ) {
            if( $index==$i++ ){
                $returnKey=$key;
                $returnVal=$value;
                return;
            }
        }
    }
    static function searchReplaceAll( $array, $search, $replace ){
        /* Iterates recursively, replacing both keys and values where found.
         * Doesn't replace two in the same iteration, so if both key and value 
         * match the search, value will be changed, key will be missed.
         * Doesn't branch and replace in the same iteration, so if key of a 
         * subarray matches search, it will be missed
         */
        $out=array();
        foreach ( $array as $key => $value ){
            $changed=false;
            if( is_array( $value ) ){
                //echo "branching $key<br>";
                $out[ $key ]=QGen::searchReplaceAll( $value, $search, $replace );
            }
            else if( $value===$search ){
                //echo "match value$value<br>";
                $out[ $key ]=$replace;
                $changed=true;
            }
            else if( $key===$search ){
                //echo "match key=$key<br>";
                $out[ $replace ]=$value;
                $changed=true;
            }
            else{
                $out[ $key ]=$value;
            }
        }
        return $out;
    } 
    static function _arrayToString( $array, $strategy, $cond, $skipUntil ){
        /*Some queries come from AJAX or GET strings, so there needs to be a way
         * of reading only the right fields. If $skipUntil is set, this function
         * will wait until after that field has passed before reading.  And it
         * always halts when the field matches haltOn defined in QGen
         * Example: For an array like 
         * 'stuff'=>1, 'op'=>'go','name'=>'frank', 'halt'=>1, 'moreStuff'=>'666'
         * this function will only read 'name'=>'frank'
         */
        /* output: `value1`, `value2`, `value3` */
        $out=array();
        if( $skipUntil ){//choose a loop
            $skip=true;
            foreach ( $array as $key => $value){
                if( $key===QGen::haltOn ){break;}//always quits on haltOn
                if( $skip ){
                    if( $key===$skipUntil ){
                        $skip=false;
                    }
                }
                else{
                    $out[]=$strategy->format( $key, $value );
                }
            }
        }
        else{
            foreach ( $array as $key => $value){
                if( $key===QGen::haltOn ){break;}//always quits on haltOn
                $out[]=$strategy->format( $key, $value );
            }
        }
        //dispArray( $out );
        return $strategy->finish( $out, $cond );
    }
    static function _colClause( $param, $default=false ){
        /* If using array for col and there are indexes you don't 
         * want to include in the formatted string, pass 'colHasSkips'=>true,
         * to stringify only the items after $skipUntil.
         * Note: _arrayToString always halts on $key==$haltOn */
        $skipUntil=QGen::skipUntil;//
        /* Query parts*/
        $col=isset( $param['col'] )? $param['col'] : "";
        $concat=isset( $param['concat'] )? $param['concat'] : false;
        $concatAs=isset( $param['concatAs'] )? $param['concatAs'] : false;
        if( $concat && $concatAs ){
            $concat=QGen::_arrayToString( 
                $concat,
                ( isset( $param['concatNoSpace'] ) )? 
                    new ValToCol_():new valToConcat_,
                '',//cond, not used
                ( isset( $param['hasSkips_concat'] ) )? $skipUntil:false
            );
            $colClause=" CONCAT ( $concat ) as $concatAs";
            if( $col ){ $colClause.=", "; }
            //echo "$colClause<br>";
        }
        else{
            if( !$col ){
                return ( $default )? $default : "";
            }
            $colClause="";
        }
        /* Formatter: reads single word or array */
        /* Construct column clause: Quotes are always inserted 
         * where needed, so if multiple values, always send array.
         * Don't send `col2`, `col2` */
        if( is_array( $col ) ){
            $col=QGen::_arrayToString( 
                $col, 
                new ValToCol_(),
                '',//cond, not used
                ( isset( $param['colHasSkips'] ) || isset( $param['hasSkips_col'] ) )? 
                    $skipUntil:false
            );
            if( !$col && $default ){//process fail, set default
                $col=$default;
            }
        }
        else if( $col ){
            $col="`".$col."`";
        }
        return $colClause.$col;
    }
    static function _whereClause( $param ){
        /* If using array for WHERE, and there are indexes you don't want to
         * include in the formatted string, pass 'whereHasSkips'=>true, to 
         * stringify only the items after $skipUntil.
         * Note: _arrayToString always halts on any key named 'halt'.
         * Note: array to string algos are either key=value or key.
         * Hint: pass 'ID_TABLE' as key and it will be changed to the right key,
         * assuming the id system follows that format */
        $skipUntil=QGen::skipUntil;//
        /* Construct WHERE clause: every query includes site id.
         * Can send WHERE parts two ways:
         *   1. send a key=>val array (inserts quotes)
         *   2. send a string: `key1`='val1', `key2`='val2' ( caller inserts quotes ) 
         */
        $whereClause="WHERE ";
        $whereClause.='1 ';//"`site_id` = '".session::getSiteId()."'";//my app needs this
        $temp="";
        /* To keep simple queries simple, there is a field called 'where' to pass
         * simple key=val parameters.  There is also a field called 'cond' to define 
         * the connector inside the clause. Default is AND. For longer queries a 
         * different naming convention is used: innerCond_where. Either way will 
         * work for 'where', but for all others follow the innerCond_* convention.
         * Similarly,the 'conditionPreceding_* naming convention applies to all 
         * sections. Where does not have one since it's handled first */
        if( isset( $param['cond'] ) ){ $cond=$param['cond']; }
        else if( isset( $param['innerCond_where'] ) ){ $cond=$param['innerCond_where']; }
        else{ $cond="AND"; }
        $allowOR=false;//don't allow OR connector for first item
        /* Assemble clauses */
        if( isset( $param['where'] ) &&
            $temp=QGen::_whereParts(
                $param['where'],
                isset( $param['whereHasSkips'] ) || isset( $param['hasSkips_where'] ),
                $cond,
                new KeyValToWhere_()
            )){
            $allowOR=true;
            $whereClause.=" AND ( $temp )";
        }
        if( isset( $param['like'] ) &&
            $temp=QGen::_whereParts(
                $param['like'],
                isset( $param['hasSkips_like'] ),
                ( isset( $param['innerCond_like'] ) )? $param['innerCond_like'] : $cond,
                new KeyLikeVal_()
            )){
            $condPreceding=( $allowOR && isset( $param['condPreceding_like'] ) )? 
                $param['condPreceding_like'] : "AND";
            $allowOR=true;
            $whereClause.=" $condPreceding ( $temp )";
        }
        if( isset( $param['isNull'] ) &&
            $temp=QGen::_whereParts(
                $param['isNull'],
                isset( $param['hasSkips_isNull'] ),
                ( isset( $param['innerCond_isNull'] ) )? $param['innerCond_isNull'] : $cond,
                new KeyNull_()
            )){
            $condPreceding=( $allowOR && isset( $param['condPreceding_isNull'] ) )? 
                $param['condPreceding_isNull'] : "AND";
            $allowOR=true;
            $whereClause.=" $condPreceding ( $temp )";
        }
        if( isset( $param['isNotNull'] ) &&
            $temp=QGen::_whereParts(
                $param['isNotNull'],
                isset( $param['hasSkips_isNotNull'] ),
                ( isset( $param['innerCond_isNotNull'] ) )? $param['innerCond_isNotNull'] : $cond,
                new KeyNotNull_()
            )){
            $condPreceding=( $allowOR && isset( $param['condPreceding_isNotNull'] ) )? 
                $param['condPreceding_isNotNull'] : "AND";
            $allowOR=true;
            $whereClause.=" $condPreceding ( $temp )";
        }
        if( isset( $param['in'] ) &&
            $temp=QGen::_whereParts(
                $param['in'],
                isset( $param['hasSkips_in'] ),
                ( isset( $param['innerCond_in'] ) )? $param['innerCond_in'] : $cond,
                new KeyInVal_()
            )){
            $condPreceding=( $allowOR && isset( $param['condPreceding_in'] ) )? 
                $param['condPreceding_in'] : "AND";
            $allowOR=true;
            $whereClause.=" $condPreceding ( $temp )";
        }
        if( isset( $param['notIn'] ) &&
            $temp=QGen::_whereParts(
                $param['notIn'],
                isset( $param['hasSkips_notIn'] ),
                ( isset( $param['innerCond_notIn'] ) )? $param['innerCond_notIn'] : $cond,
                new KeyNotInVal_()
            )){
            $condPreceding=( $allowOR && isset( $param['condPreceding_notIn'] ) )? 
                $param['condPreceding_notIn'] : "AND";
            $allowOR=true;
            $whereClause.=" $condPreceding ( $temp )";
        }
        if( isset( $param['keyEqualsValue'] ) &&
            $temp=QGen::_whereParts(
                $param['keyEqualsValue'],
                isset( $param['hasSkips_keyEqualsValue'] ),
                ( isset( $param['innerCond_keyEqualsValue'] ) )? $param['innerCond_keyEqualsValue'] : $cond,
                new KeyValToWhere_()
            )){
            $condPreceding=( $allowOR && isset( $param['condPreceding_keyEqualsValue'] ) )? 
                $param['condPreceding_keyEqualsValue'] : "AND";
            $allowOR=true;
            $whereClause.=" $condPreceding ( $temp )";
        }
        /* Add bound clause to WHERE clause */
        $temp=QGen::_boundClause( $param );
        if( $temp ){
            $condPreceding=( $allowOR && isset( $param['condPreceding_bound'] ) )? 
                $param['condPreceding_bound'] : "AND";
            $whereClause.=" $condPreceding ( $temp ) ";
        }       
        return ( strlen( trim($whereClause) )==7 )? '' : $whereClause;
    }
    static function _whereParts( $list, $hasSkips, $cond, $strategy ){
        if( is_array( $list ) ){
            return QGen::_arrayToString(
                    $list,
                    $strategy,
                    $cond,
                    ( $hasSkips )? QGen::skipUntil : false
                );
        }
        return $strategy->format( $list,'' );//only works for isNull, isNotNull
    }
    static function _boundClause( $param ){
        /* Date or number ranges. If equating, pass as a regular WHERE 
         * parameter. Only use this for range between, or greater or less than
         * Default is inclusive: >= <=
         * For noninclusive (>, <), add a 'noninclusive'=>true to array
         * For noninclusive_lo (>, <=), add 'noninclusive_lo'=>true to array
         * For noninclusive_hi (>=, <), add 'noninclusive_hi'=>true to array
         * Default boundColumn name is `date`:
         * If different, add a 'boundCol'=>'column_name' to array...
         * Or pass a key value array: 'lobound=>array( $boundCol=>$boundValue )
         * Specify one, both or all bound columns using above
         * Hint:    passing only loBound selects > loBound
         *          passing only hiBound selects < hiBound
         *          passing both selects range inbetween
         */
        $loBound=isset( $param['loBound'] )? $param['loBound'] : false;
        $hiBound=isset( $param['hiBound'] )? $param['hiBound'] : false;
        /* Most queries don't use bound, so better to skip if not set */
        if( !$loBound && !$hiBound){ return ""; }
        /* Set hi and lo bound columns to specified or default */
        $boundCol_lo=$boundCol_hi=isset( $param['boundCol'] )? 
            $param['boundCol'] : "date";
        /* Set 'equal' vars according to noninclusive or default */
        $noninclusive=isset( $param['noninclusive'] );
        $equal_lo=( $noninclusive || isset( $param['noninclusive_lo'] ) )? '':'=';
        $equal_hi=( $noninclusive || isset( $param['noninclusive_hi'] ) )? '':'=';
        /* Respond to one or both bound parameters */
        if( $loBound ){
            /* Allow boundCol to be specified as key of array */
            if( is_array( $loBound ) ){
                QGen::keyValAt( $loBound, $boundCol_lo, $loBound );
            }
            if( is_array( $hiBound ) ){
                QGen::keyValAt( $hiBound, $boundCol_hi, $hiBound );
            }          
            if( $hiBound ){//range between dates
                $boundClause="`$boundCol_lo` >$equal_lo '$loBound' AND `$boundCol_hi` <$equal_hi '$hiBound'";
            }
            else{//range above loBound
                $boundClause="`$boundCol_lo` >$equal_lo '$loBound'";
            }
        }
        else if( $hiBound ){//range below hiBound
            if( is_array( $hiBound ) ){
                QGen::keyValAt( $hiBound, $boundCol_hi, $hiBound );
            }
            $boundClause="`$boundCol_hi` <$equal_hi '$hiBound'";
        }
        else{
            $boundClause=false;
        }
        return $boundClause;
    }
    
}
interface QGenStrategy{
    function format( $key, $value );
    function finish( $array, $cond );
}
class ValToCol_ implements QGenStrategy{
    function format( $key, $value ){ return "`$value`"; }
    function finish( $array, $cond ){
        return ( count( $array ) )? implode( ", ", $array ) : '';
    }
}
class valToConcat_ extends ValToCol_ implements QGenStrategy{
    function finish( $array, $cond ){
        return ( count( $array ) )? implode( ", ' ', ", $array ) : '';
    }
}
class KeyToCol_ extends ValToCol_ implements QGenStrategy {
    function format( $key, $value ){ return "`$key`"; }
}
class ValToVal_ extends ValToCol_ implements QGenStrategy{
    function format( $key, $value ){ return "'$value'"; }
}
class KeyToKey_ extends ValToCol_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key`"; }
}
class KeyEqualsVal_ extends ValToCol_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key`='$value'"; }
}
class KeyValToWhere_ extends KeyEqualsVal_ implements QGenStrategy{
    function finish( $array, $cond='AND' ){
        return ( count( $array ) )? implode( " $cond ", $array ) : '';
    }
}
class KeyLikeVal_ extends KeyValToWhere_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key` LIKE '$value%'"; }
}
class KeyNull_ extends KeyValToWhere_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key` IS NULL"; }
}
class KeyNotNull_ extends KeyValToWhere_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key` IS NOT NULL"; }
}
class KeyInVal_ extends KeyValToWhere_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key` IN ( $value )"; }
}
class KeyNotInVal_ extends KeyValToWhere_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key` NOT IN ( $value )"; }
}
class IncKeyByVal_ extends ValToCol_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key` = `$key` + $value"; }
}
class DecKeyByVal_ extends ValToCol_ implements QGenStrategy{
    function format( $key, $value ){ return "`$key` = `$key` - $value"; }
}