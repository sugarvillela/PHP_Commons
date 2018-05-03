<?php
/* This class is most useful in a case where many boolean flags of different
 * names need to be set and kept track of. The base array is in 3 dimensions.
 * The first dimension is $this->flags, which might be analogous to a table
 * in a relational database, execpt the rows and columns don't need to be of
 * uniform size.  The second and third dimensions are analogous to row and
 * column. Initially the only part set is the flag/table array; rows are set 
 * to null. If tru( $row, $column ) is called, function will return false.
 * If get( $row, $column )is called, a bad index error will result. (This is 
 * good if you're in dev. For release, change the code or call truget(), which
 * checks the index before accessing it. )
 * When set( $row, $column ) is called, it checks for a null row. If null,
 * it calls the format function for that row, then sets the column. (see test
 * class below )
 * 
 * There's a way to make flag data persistent after the object goes out
 * of scope.  Change the flags array to GLOBALS or SESSION and you have a class
 * that can be easily rebuilt in a different place, or a new page call.
 * 
 * You use the class by inheriting from it. In the example below, 
 * flag_object_example extends flag_object. It might be an object to hold
 * a word of text and keep attributes of it like whether it has uppercase or
 * numeric etc.  I use this during semantic interpretation of word 'tokens'
 * 
 * Flag object test runs it
 */
function flag_object_test(){
    $f = new flag_object_example('Hello');
    $f->set( 'attribute', 'cap' );//first call formats attribute and sets cap true
    echo $f->tru( 'attribute', 'cap' ).'<br>';//Prints 1 because cap is set
    echo $f->tru( 'attribute', 'numeric' ).'<br>';//false because numeric is not set
    echo $f->tru( 'type', 'unicorn' ).'<br>';//false because type is not set
    echo $f->doSomething( 6 ).'<br>';//Prints 6 because that's something
}
class flag_object_example extends flag_object {
    function __construct( $setText ) {
        self::format_flagContainer();
        self::set( 'text', 'orig', trim( $setText ) );
    }
    function format_flagContainer(){
        /* Required to extend flag_object */
        $this->flags=array(
            "text"=>null,
            "attribute"=>null,
            "type"=>null,
        );
    }
    function format_text(){
        /* function name is generated in parent::set() as 'format_'.'nameOfRow',
         * so follow this naming convention */
        $this->flags[ 'text' ]=array(
            "orig"=>"", "edited"=>""
        );
    }
    function format_attribute(){
        $this->flags[ 'attribute' ]=array(
            "cap"=>false, "allCap"=>false, "numeric"=>false, "nonEnglish"=>false
        );
    }
    function doSomething( $integer ) {
        /* Example function, does something based on flag state */
        if( self::tru( 'attribute', 'numeric' ) ){
            return self::get( 'text', 'orig' ) + $integer;
        }
        return $integer;
    }
}
abstract class flag_object {
    public $flags;
    /* The flag container should be a two-dimensional array. One dimension we call
     * the row and the second we call the column. Implementing class names the rows
     * and creates the array with rows set to null. Implementing class also 
     * creates formatting functions in the form of format_rowName() */
    protected abstract function format_flagContainer();
    
    function set( $row, $column, $setTo=true ){//, $caller='word'
        if( !$column ){
            /* Case 0: caller tries to merge an array, but the array is null */
            return false;
        }
        /* Case: first set: as flags elements are initialized to null, the first call
         * to set a value triggers a format function, run from a string created here. */
        if( !$this->flags[ $row ] ){
            $f="format_".$row;
            $this->$f();
        }
        /* Case 1: Column is an array to be merged. In this usage, the name 
         * '$column' is misleading... set( 'row', 'arrayToMerge' ) */
        if( is_array( $column ) ){
            $this->flags[ $row ]=$column;
            //mergeBool( $this->flags[ $row ], $column );
        }
        /* Case 2: writes a single value to the specified array. Default value is 
         * 'true', but any value can be set ( string, integer )... set( 'row', 'column', 'stringOrInt' )
         * To set a column to false, use drop() */
        else{
            /* Once program is finished, get rid of these checks and just leave the assign */
            $cf=count( $this->flags );
            $c=count( $this->flags[ $row ] );
            $this->flags[ $row ][ $column ]=$setTo;
            if( count( $this->flags[ $row ] ) > $c){echo "<b>Dave!!! bad column name $row=>$column</b><br>";}
            if( count( $this->flags ) > $cf){echo "<b>Dave!!! bad row name $row</b><br>";} 
        }
        return true;
    }
    function merge( $row, $array ){
        /* Case: first set: as flags elements are initialized to null, the first call
         * to set a value triggers a format function */
        if( !$this->flags[ $row ] ){
            $f="format_".$row;
            $this->$f();
        }
        mergeBool( $this->flags[ $row ], $array );
    }
    function set_exclusive( $row, $column, $setTo=true ){//, $caller='word'
        $f="format_".$row;
        $this->$f();
        return self::set( $row, $column, $setTo );
    }
    function set_dup( $row, $column, $setTo, $flag ){
        /* Same as set, but sets a flag if set is called on the same */
        if( $this->flags[ $row ] AND !$this->flags[ $row ][$column] ){
            /* Row is formatted (prior set) but column is different from current */
            self::set( 'flag', $flag );
            self::set( $row, $column, $setTo );
            return true;
        }
        self::set( $row, $column, $setTo );
        return false; 
    }
    function set_verify( $row, $column, $setTo=true ){
        /* Same as set, but makes sure the destination index exists */
        if( isset( $this->flags[ $row ][ $column ] ) ){
            self::set( $row, $column, $setTo );
            return true;
        }
        return false;
    }
    function tru( $row, $column=false ){
        /* Check whether a row and column are set. Does not protect against
         * non-existent row or column names. Use isset() for that.
         * Case 1: args row and column are both there: check non-falsiness of the
         * data at specified location: !false, !empty string, !integer zero etc */
        if( $column !== false ){
            return ( $this->flags[ $row ] && $this->flags[ $row ][ $column ] )? 
                true : false; 
        }
        /* Case 2: no column arg: tells whether the column location has been 
         * formatted, ie not null */
        else {
            return ( $this->flags[ $row ] )? 
                true : false; 
        }
    }
    function get( $row, $column=false ){
        /* Same as tru, except returns the data instead of a true or false
         * (unless the data happens to be a true or false) */
        if( $column !== false ){
            return $this->flags[ $row ][ $column ];
        }
        else {
            return $this->flags[ $row ]; 
        }
    }
    function truget( $row, $column=false ){
        /* Combination of tru and get; why not just make this get? During dev, I
         * need the bad index errors  */
        if( $this->flags[ $row ] ){
            if( $column !== false ){
                return $this->flags[ $row ][ $column ];
            }
            else {
                return $this->flags[ $row ];
            }  
        }
        return false;
    }
    function import( $row, $importArray ){
        /* like set, but sets row with array from arg */
        $cf=count( $this->flags );
        $this->flags[ $row ]=$importArray;
        if( count( $this->flags ) > $cf){echo "<b>Dave!!! In import() bad row name $row</b><br>";} 
    }
    function toggle( $row, $column ){//assume the row is set to save having to check and format
        $this->flags[ $row ][ $column ]=!$this->flags[ $row ][ $column ];
    }
    function compare( $rowA, $columnA, $rowB, $columnB=false ){
        /* Compares either the values of two elements or the values of 
         * an element and a string.
         * Case1: four args: compare the elements, only if both set */
        if( $columnB !== false ){
            return ( self::tru( $rowA, $columnA ) && self::tru( $rowA, $columnB ) )? 
                ( $this->flags[ $rowA ][ $columnA ] == $this->flags[ $rowB ][ $columnB ] ) : false;
        }
        /* Case2: three args: compare the element and string.
         * The name 'rowB' is misleading. Just think of rowB as 'compareMe'  */
        else{
            return ( self::tru( $rowA, $columnA ) )? 
                ( $this->flags[ $rowA ][ $columnA ] == $rowB ) : false;
        }
    }
    function copy( $rowTo, $columnTo, $rowFrom=null, $columnFrom=null ){
        /* This function calls other functions so that their protections
         * can be used. Case 1: copy one value */
        if( $columnFrom ){
            self::set( $rowTo, $columnTo, self::get( $rowFrom, $columnFrom ) );
        }
        /* Case 2: If only 2 args are sent, columnTo becomes arrayIn, as in
         * copy the whole array.  Prioritizing versatility over user-friendliness, 
         * the name 'columnTo' is misleading.  */
        else{
            self::import( $rowTo, $this->flags[ $columnTo ] );
        }
    }
    function copyRow( $rowTo, $rowFrom ){
        self::import( $rowTo, $this->flags[ $rowFrom ] );
    }
    function copyNo( $rowTo, $columnTo, $rowFrom=null, $columnFrom=null ){
        /* This function calls other functions so that their protections
         * can be used. Case 1: copy one value */
        if( $columnFrom ){
            self::set( $rowTo, $columnTo, self::get( $rowFrom, $columnFrom ) );
        }
        /* Case 2: If only 2 args are sent, columnTo becomes arrayIn, as in
         * copy the whole array.  Prioritizing versatility over user-friendliness, 
         * the name 'columnTo' is misleading.  */
        else{
            self::import( $rowTo, $this->flags[ $columnTo ] );
        }
    }
    
    function drop( $row, $column=false ){
        /* either set one item false, or set the whole row null */
        //echo "flag object drop $row, $column<br>";
        if( $column ){
            /* Case 1: flags row not set: no need to do anything */
            if( !$this->flags[ $row ] ){return;}
            /* Case 2: flags row set; delete one item */
            $this->flags[ $row ][ $column ] = false;
        }
        else{
            /* column arg left blank; delete the whole row */
            $this->flags[ $row ]=null;
        }
    }
    function see( $row=null, $column=null ){
        /* Developer tool: tell status of row if null, contents of column, or 
         * contents of row; display all flags if no args */
        if( !$row ){
            //echo "<b>See all flags</b><br>";
            foreach ($this->flags as $key=>$table) {
                dispArray($table, "<b>See $key</b>");
            }
        }
        else if( !$this->flags[ $row ] ){ echo "$row is null<br>"; }
        else if( $column ){
            echo "<b>See '$row', '$column'</b><br>";
            if( $this->flags[ $row ][ $column ] === true ){ echo "$column=true<br>"; }
            else if( $this->flags[ $row ][ $column ] === false ){ echo "$column=false<br>"; }
            else{ echo "$column=".$this->flags[ $row ][ $column ]."<br>"; }
        }
        else {
            echo "<b>See '$row'</b><br>";
            dispArrayLine( $this->flags[ $row ] );
        }
    }
    function countTrues( $row ){
        /* Returns the number of columns set, ie not falsey */
        if( !$this->flags[ $row ] ){ return 0; }
        $count=0;
        foreach ( $this->flags[ $row ] as $value ) {
            if( $value ){
                $count++;
            }
        }
        return $count;
    }
    function firstTrueKey( $row ){
        /* Returns the column name of the first column set, ie not falsey */
        if( !$row ){ return ""; }
        foreach ( $this->flags[ $row ] as $key=> $value ) {
            if( $value ){
                return $key;
            }
        }
    }
    /* Below: String functions: assumes string at specified locations.
     * To keep our syntax from getting unreadable as before, we need to
     * wrap common functions with a place for row and column. Using the same 
     * function name keeps clear the purpose of each function. Kind of like Java, 
     * where everything is a function... */
    function strlen( $row='text', $column='in' ) {
        return strlen( $this->flags[ $row ][ $column] );
    }
    function dropFirst( $row='text', $column='in', $index=1 ){
        self::set( $row, $column, substr( self::get( $row, $column ), $index ) );
    }
    function dropLast( $row='text', $column='in', $index=-1 ){
        self::set( $row, $column, substr( self::get( $row, $column ), 0, $index ) );
    }
    function str_replace( $row, $column, $search, $replace ){
        //str_replace($search, $replace, $subject);
        self::set( $row, $column, str_replace( $search, $replace, self::get( $row, $column ) ) );
    }
    function charAt( $row, $column, $index ){
        $temp=self::get( $row, $column );
        return $temp[$index];
    }
    function firstChar( $row='text', $column='in' ){
        return $this->flags[ $row ][ $column][0];
    }
    function lastChar( $row='text', $column='in' ){
        $temp=$this->flags[ $row ][ $column];
        return $temp[strlen( $temp )-1];
    }
    function concat( $row, $column, $addThis ){//assume destination is formatted
        self::set( $row, $column, self::truget( $row, $column ).$addThis );
    }
    function prepend( $row, $column, $addThis ){//assume destination is formatted
        self::set( $row, $column, $addThis.self::truget( $row, $column ) );
    }
    /* Custom regexes (class RX) can read flag objects by calling get. 
     * The below functions supply text.in as the row.column, so regexes
     * can access traits not flagged */
    function last(){
        $temp=self::get( 'text', 'in' );
        return $temp[strlen( $temp )-1];
    }
    function first(){
        $temp=self::get( 'text', 'in' );
        return $temp[0];
    }
    function len(){
        return strlen( $this->flags['text']['in'] );
    }
    /* Below: Integer functions: assumes integer at specified locations */
    function inc( $row, $column, $addThis=1 ){//
        if( !$this->flags[ $row ] ){
            $f="format_".$row;
            $this->$f();
        }
        $this->flags[ $row ][ $column ]+=$addThis;
    }
    function dec( $row, $column, $subtractThis=1 ){//kind of redundant, but...
        echo "add this=$addThis<br>";
        if( !$this->flags[ $row ] ){
            $f="format_".$row;
            $this->$f();
        }
        $this->flags[ $row ][ $column ]-=$subtractThis;
    }
    function mult( $row, $column, $multThis=1, $op='*' ){
        /* Handles multiply, divide and mod, with /0 safe  */
        if( !$this->flags[ $row ] ){
            /* Format if not formatted. Some int arrays are initialized to a 
             * non-zero, so it's good not to return false yet */
            $f="format_".$row;
            $this->$f();
        }
        if( $op=='*' ){
            $this->flags[ $row ][ $column ]*=$multThis;
            return true;
        }
        if( $op =='/' && $multThis ){//false on 0
            $this->flags[ $row ][ $column ]/=$multThis;
            return true;
        }
        if( $op =='%' && $multThis ){
            echo "In flag mult %<br>";
            $this->flags[ $row ][ $column ]%=$multThis;
            return true;
        }
        return false;
    }
    /* Below: Array functions */
    function count( $row ){//if array is null, count will return zero (per documentation)
        return count( $this->flags[ $row ] );
    }
    /* Below: Specialized math function to be implemented by child class */
    
}
?>
