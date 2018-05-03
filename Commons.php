<?php
/* Database functions */
/* The functions below assume you have opened a mysqli database connection and
 * given it a global handle named $dbc.  You also have set a global variable
 * $dbName with the name of the schema
 */
function getColNames( $table ){
    global $dbc; global $dbName;
    $query="SELECT * FROM `$dbName`.`$table` LIMIT 1;";
    $result = mysqli_query( $dbc, $query );
    if ($result AND $row = mysqli_fetch_assoc( $result ) ) {
        $out=array();
        foreach ( $row as $key => $value ){
            $out[]=$key;
        }
        mysqli_free_result($result);
        return $out;
    }
    echo 'getColNames(), error: ' . mysqli_error( $dbc );
    return array();
}
function getTableNames(){
    global $dbc; global $dbName;
    $result = mysqli_query( $dbc, "SHOW TABLES FROM `$dbName`;" );
    if ($result) {
        $out=array();
        while( ( $row = mysqli_fetch_row( $result ) ) ){
            $out[]=$row[0];
        }
        mysqli_free_result($result);
        return $out;
    }
    echo 'getTableNames(), error: ' . mysqli_error( $dbc );
    return array();
}
function dbToFile_json( $directory='' ){
    /* Backs up all tables in the schema in JSON format.
     * Generates file names based on table name. If you want the file in a
     * directory, pass the path, including the slash.  Example:
     * dbToFile_json('db_backup/');
     */
    global $dbc; global $dbName;
    $row; $fout; 
    $tables=getTableNames();
    disp( $tables, 'List of Tables:' );
    foreach ( $tables as $table ) {
        $query="SELECT * FROM `$dbName`.`$table` ORDER BY `text`;";
//        echo "$query<br>";
//        echo "$directory$table.txt<br>";
        $result = mysqli_query( $dbc, $query );
        if ( $result AND ( $fout=fopen( "$directory$table.txt", 'w' ) ) ) {
            $linesWritten=0;
            while( ( $row = mysqli_fetch_assoc( $result ) ) ){
                fwrite( $fout, json_encode( $row ).PHP_EOL );
                $linesWritten++;
            }
            echo "Wrote $linesWritten lines to $directory$table.txt<br>";
            mysqli_free_result( $result );
            fclose( $fout );
        }
    }
}
/* Array functions */
function initArrayToRange( $lo, $hi ){
    $out=array();
    for ($i=$lo; $i<=$hi; $i++) {
        $out[]=$i;
    }
    return $out;
}
function getAssocKey( $array, $i ){
    /* For associative array
     * Returns string key in the position $i */
    $c=0;
    foreach ($array as $key => $value) {
        if( $i == $c++){return $key;}
    }
    return "";
}
/* Numeric */
function inRange( $lo, $hi, $n ){//inclusive like this [ lo, hi ]
    return ( $lo<=$n && $n <= $hi );
}
/* String functions */
function char_count( $char, $str ){
    /* Simpler than substr_count() because needle is one char*/
    $len=strlen( $str );
    $count=0;
    for ($i = 0; $i < $len; $i++) {
        if( $str[$i] == $char ){
            $count++;
        }
    }
    return $count;
}
function matchCap( $subject, $template ){
    /* Bizarre but necessary: if templete is Hi Bob and subject is hi BOB,
     * returns Hi Bob
     */
    $lenS=strlen( $subject );
    $lenT=strlen( $template );
    for ($i = 0; $i < $lenS && $i < $lenT; $i++) {
        if( ctype_upper( $template[$i] ) ){
            $subject[$i]=strtoupper( $subject[$i] );
        }
        else{
            $subject[$i]=strtolower( $subject[$i] );
        }
    }
    return $subject;
}
function telToReadable( $n ){
    /* Add pretty format to digit string from DB 
     * Or send an integer: automatically converts int to str
     */
    switch( strlen( "$n" ) ){
        case 10:
            return "(".substr($n, 0, 3).") ".substr($n, 3, 3)."-".substr($n, 6);
        case 7:
            return substr($n, 0, 3)."-".substr($n, 3, 4);
        default:
            return $n;//return empty or false to assert length 7 or 10
    }
}
function dateStringToArray( $dateString ){
    /* Example input and call:
        $s='2018-01-13 00:00:00';
        var_dump( dateStringToArray( $s ) );
     * Output:
        array(4) { ["yyyy"]=> int(2018) ["mm"]=> int(1) ["dd"]=> int(13) ["mName"]=> string(7) "January" }
     */
    $mm=substr( $dateString, 5, 2 )-0;
    $ms=array( "","January", "February", "March", "April", "May", "June", 
        "July", "August", "September", "October", "November", "December" );
    return array(
        "yyyy"=>substr( $dateString, 0, 4 )-0,
        "mm"=>$mm,
        "dd"=>substr( $dateString, 8, 2 )-0,
        "mName"=>$ms[$mm]
    );
}
/* TMath for math with time: use time as minutes */
function numPad( $padMe, $n=2 ){//returned string length >= n
    /* pretty wrapper for ugly formatting */
    return sprintf( "%'.0".$n."d", $padMe );
}
class TMath{
    /* I wrote a bigger library for this in Javascript. These are just the 
     * few that I translated to PHP */
    static function minTimeToRead( $min ){
        /* This one is for time, not duration 
         * Input time as minutes, like 555=09:15 AM */
        if( $min<720 ){//0 thru 11:59
            $AP="AM";
            if( $min<60 ){//0 thru 00:59
                $min+=720;//make 00:15 into 12:15
            }
        }
        else if( $min<780 ){//12:00 thru 13:00
            $AP="PM";
        }
        else if($min<1440){//13:00 thru 24:00
            $min-=720;
            $AP="PM";
        }
        else{//midnight as 00 or some error
            $AP="AM";
            $min=0;
        }
        $hours=floor( $min/60 );
        $min-=$hours*60;
        if( $hours<10 ){ $hours="0$hours"; }
        if( $min<10 ){ $min="0$min"; }
        return $hours.":".$min." ".$AP;
    }
    static function toDateString( $y, $m, $d ){
        return numPad( $y, 4 ).
            "-".numPad( $m ).
            "-".numPad( $d ).
            " 00:00:00";
    }
    static function fromDateString( $dateString ){
        $mm=substr( $dateString, 5, 2 )-0;
        return array(
            "yyyy"=>substr( $dateString, 0, 4 )-0,
            "mm"=>$mm,
            "dd"=>substr( $dateString, 8, 2 )-0,
            "mName"=>TMath::monthName($mm)
        );
    }
    static function dateStringToRead( $str ){
        return substr( $str, 5, 2 ).'/'.substr( $str, 8, 2 ).'/'.substr( $str, 0, 4 );
    }
    static function dateStringToRead_monthName( $str ){
        return TMath::monthName( substr( $str, 5, 2 ) ).
                ' '.substr( $str, 8, 2 ).', '.substr( $str, 0, 4 );
    }
    /* Add an offset for these time functions, whatever gives back the correct
     * time.  Especially if a remote server is in a different time zone*/
    static function nowDateStr(){
        $time=time();//+$_SESSION['adm']['timeOffset'];//compensate for php time
        return date("Y-m-d 0:0:0", $time );
    }
    static function nowDay(){
        $time=time();//+$_SESSION['adm']['timeOffset'];//compensate for php time
        return date('w',$time );
    }
    static function nowTime(){
        $time=time();//+$_SESSION['adm']['timeOffset'];//compensate for php time
        return date('G:i:s',$time );
    }
    static function isPast( $dateStr ){
        $now=time();//+$_SESSION['adm']['timeOffset'];//compensate for php time
        $time=strtotime( $dateStr );
        return ( $time < $now );
    }
    static function monthName( $n ){
        switch ( "$n" ) {
            case '1': return "January";
            case '2': return "February";
            case '3': return "March";
            case '4': return "April"; 
            case '5': return "May";    
            case '6': return "June";   
            case '7': return "July";
            case '8': return "August";
            case '9': return "September";
            case '10': return "October";
            case '11': return "November";
            case '12': return "December";
            default: return "$n";
        }
    }
    static function months_full(){
        return array( 
            "January"=>1, "February"=>2, "March"=>3, "April"=>4, 
            "May"=>5, "June"=>6, "July"=>7, "August"=>8, 
            "September"=>9, "October"=>10, "November"=>11, "December"=>12 
        );
    }
    static function weekdays_full(){
        return array( 
            "Sunday","Monday","Tuesday","Wednesday",
            "Thursday","Friday","Saturday" 
        );
    }
}
class QStack{
    /* Iterable array with functions:
     * can be a stack, a queue or a self iterator
     */
    protected $iArr, $iCount, $limit;
    function __construct( $setArr=null ) {
        $this->itrInit( $setArr );
        $this->limit=999;
    }
    function itrInit( $setArr=null ){
        $this->iArr=( $setArr )? $setArr : array();
        $this->itrReset();
    }
    function itrReset( $setTo=false ){
        $this->iCount=( $setTo!==false )? $setTo : -1;
        //echo "iCount=$this->iCount<br>";
    }
    function getCounter(){ return $this->iCount; }
    function itrSetLimit( $setLimit ){
        $this->limit=$setLimit;
    }
    function initFromString( $str ){
        $len=strlen( $str );
        for ($i = 0; $i < $len; $i++) {
            $this->iArr[]=$str[$i];
        }
    }
    function more(){//for terminating iterator
        return ( $this->iCount < $this->len() && $this->iCount < $this->limit );
    }
    function inc(){//iterator
        $this->iCount++;
    }
    function dec(){//iterator
        $this->iCount--;
    }
    function getCurr(){
        return $this->iArr[ $this->iCount ];
    }
    function getNext( &$returnMe ){
        /* One function does it all */
        $this->iCount++;
        if( $this->more() ){
            $returnMe=$this->iArr[ $this->iCount ];
            return true;
        }
        return false;
    }
    function getPrev( &$returnMe ){
        /* One function does it all */
        $this->iCount--;
        if( $this->iCount >= 0 ){
            $returnMe=$this->iArr[ $this->iCount ];
            return true;
        }
        return false;
    }
    function push( $pushMe ){//queue or stack-like behavior: peek
        $this->iArr[]=$pushMe;
    }
    function top( &$returnMe ){//stack-like behavior: peek
        $count=$this->len();
        if( $count ){
            $returnMe=$this->iArr[ $count-1 ];
            return true;
        }
        return false;
    }
    function pop( &$returnMe ){
        $top=array_pop( $this->iArr );
        if( $top ){
            $returnMe=$top;
            return true;
        }
        return false;
    }
    function bottom( &$returnMe ){//queue-like behavior: peek
        $count=$this->len();
        if( $count ){
            $returnMe=$this->iArr[ 0 ];
            return true;
        }
        return false;
    }
    function dequeue( &$returnMe ){
        $top=array_shift( $this->iArr );
        if( $top ){
            $returnMe=$top;
            return true;
        }
        return false;
    }
    function at( $i ){//vector-like behavior
        return $this->iArr[$i];
    }
    function itrUnset( $i ){
        unset( $this->iArr[$i] );
    }
    function itrGetKey( $value ){
        return array_search ( $value , $this->iArr );
    }
    function len(){
        return count( $this->iArr );
    }
    function getIArr(){ return $this->iArr; }
}
/* Display functions */
function disp( $array, $label='Display:<br>' ){
    foreach(  $array as $key => $value ){
        if(is_array($value)){disp($value, $key);}
        else{
            echo $key.": ".$value."<br>";

        }
    }
}
function dispArrayTable( $in, $label="Display Array"){
    if(!$in){return;}
    echo "<br><table>";
    echo '<tr><th colspan="2">'.$label.': '.count($in).' items</th></tr>';
    foreach($in as $key => $value) {
        if(is_array($value)){
            echo "<tr>&nbsp;<th></th><td>";
            dispArrayTable($value, $key);
            echo "</td></tr>";
        }
        else{
            echo "<tr><th>$key</th><td>$value</td></tr>";

        }
    }
    echo "</table><br>";
}
function dispArrayLine( $table ){
    /* Alternate display for 1-d array: example input and call
        $arr=array('first'=>'one', 'second'=>'two', 'third'=>'three', 'last'=>'four');
        dispArrayLine( $arr );
     * Example output:
        first   	second   	third   	last   
        one   	two   	three   	four   
     */
    if(!$table){return;}
    $line="<table><tr>";
    foreach ($table as $key=>$val) {//get horizontal headings; corner element blank
        if( $val !==false ){
            $line.="<td><b>$key</b>&nbsp;&nbsp;&nbsp;</td>";
        }
    }
    $line.="</tr><tr>";
    foreach ($table as $key=>$val) {//get horizontal data; first element is key in bold   
        if( $val!==false ){
            $line.="<td>$val&nbsp;&nbsp;&nbsp;</td>";
        }
    }
    $line.="</tr></table>";
    echo $line;
}
function dispFlagKeys( $in, $label="dispFlagKeys"){
    /* Alternate display for 1-d array: Ignores elements whose value is falsey
     * Displays key only of truthy values. 
     * Example input and call
        $arr=array('first'=>false, 'second'=>true, 'third'=>true, 'last'=>false);
        dispFlagKeys( $arr );
     * Example output:
        dispFlagKeys	
        second, third
     */
    if(!$in){return;}
    $implodeMe=array();
    foreach($in as $key => $value) {
        if($value){
            $implodeMe[]=$key;
        }
    }
    echo "<table>";
    echo '<tr><th>'.$label.'</th><td></td></tr>';//line, no count
    echo "<tr><td>". implode(', ', $implodeMe) ."</td></tr>";
    echo "</table>";
}