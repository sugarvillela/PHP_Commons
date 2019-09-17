<?php
/*
 * Arrays in PHP are like dictionaries in Python. The only difference is that
 * Python does not guarantee the order of the items, while PHP always provides
 * items in the order they were added (using foreach loop)
 */

/**
 * Behaves like a set (items only occur once), but also keeps count of
 * how many times each item was added. 
 * Emulates add() remove() contains() clear() toArray() from PHP set class
 * Also provides additional functions to get contents sorted by count.
 *
 * @author Dave Swanson
 */
class counting_set {
    private $set;
    function __construct() {
        $this->set=array();
    }
    /* Functions that follow PHP set class */
    function add( $item ){//Either add to set or increment existing
        if( isset($this->set[$item]) ){
            $this->set[$item]++;
        }
        else{
            $this->set[$item]=1;
        }        
    } 
    function contains( $item ){
        /* Returns 0 on absent item so use '==' for boolean, not '==='
         * Returns count of existing item. */
        return ( isset($this->set[$item]) )? $this->set[$item] : 0;
    }
    function remove( $item ){
        unset( $this->set[$item] );
    } 
    function clear(){
        $this->set=array();
    } 
    function toArray(){//already a PHP array
        return $this->set;
    } 
    /* Additional or non-corresponding functions */
    function get(){//returns a 2-d array of tuples: (item, count)
        $arr=array();
        foreach ($this->set as $item => $count) {
            $arr[]=array( $item, $count );
        }
        return $arr;
    }
    function get_sortByCount(){//Lowest count first
        $arr=self::get();
        usort($arr, 'cmp1');
        return $arr;
    }
    function get_sortByItem(){//Assumes string keys
        $arr=self::get();
        usort($arr, 'cmp0');
        return $arr;
    }
    function get_sortByCountRev(){//Highest count first
        $arr=self::get();
        usort($arr, 'rev1');
        return $arr;
    }
    function disp(){//for dev
        foreach ($this->set as $item => $count) {
            echo "$item => $count<br>";
        }
    }
    function demo(){
        /* To call demo:
         * $s = new counting_set();
         * $s->demo();
         */
        echo "Adding items: b, b, a, c, b, c<br>";
        self::add('b');
        self::add('b');
        self::add('a');
        self::add('c');
        self::add('b');
        self::add('c');
        //$s->disp();
        echo "Display as added<br>";
        $arr=self::get();
        foreach ($arr as $tuple) {
            echo $tuple[0].', '.$tuple[1].'<br>';
        }
        echo "Display sorted by count<br>";
        $arr=self::get_sortByCount();
        foreach ($arr as $tuple) {
            echo $tuple[0].', '.$tuple[1].'<br>';
        }
        echo "Display sorted by item name<br>";
        $arr=self::get_sortByItem();
        foreach ($arr as $tuple) {
            echo $tuple[0].', '.$tuple[1].'<br>';
        }
        echo "Display sorted by count (reverse)<br>";
        $arr=self::get_sortByCountRev();
        foreach ($arr as $tuple) {
            echo $tuple[0].', '.$tuple[1].'<br>';
        }
    }    
}
/* Helpers for usort */
function cmp0($a, $b){
        return ord($a[0][0]) - ord($b[0][0]);
}
function cmp1($a, $b){
        return $a[1] - $b[1];
}
function rev1($a, $b){
        return $b[1] - $a[1];
}


?>
