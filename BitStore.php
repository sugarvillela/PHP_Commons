<?php
class bitStore {
    /* Handy class if you have a bunch of boolean flags to set and you don't want
     * to use up space.  Each element of the integer array can store 32 boolean
     * values, so the array is 1/32 the size of your bit storage requirement.
     * Access the bits with masking, using the set and get functions. A few more
     * self-explanatory functions are included. 
     * Note: where the environment supports 64-bit integers, the wordLength is 
     * automatically set to 64.  
     */
    private $storeSize;
    private $arraySize;
    private $wordLength;
    private $store;
    public function __construct( $setSize, $setWordLength=null ) {
        /* Auto set based on PHP word length */
        $this->wordLength=( $setWordLength )? $setWordLength: 8*PHP_INT_SIZE;
        /* Auto correct size if not a multiple of 32 or 64 */
        if( $setSize%$this->wordLength ){
            $setSize+=$this->wordLength-( $setSize%$this->wordLength );
        }
        /* number of bits to be stored */
        $this->storeSize = $setSize;
        /* number of integer elements required to hold the bits */
        $this->arraySize = $setSize/$this->wordLength;
        /* Make array and initialize it to 0 for default false */
        $this->store = array();
        for($i=0;$i<$this->arraySize;$i++){
            $this->store[$i]= 0;
        }
    }
    function set( $index ){
        if( $index > $this->storeSize ){//get rid of this after dev
            echo "Error: BitStore set() index out of range <br>";
            $index = $index%$this->storeSize;      //mod for indexes beyond storageSize;
        }
        $this->store[ floor($index/$this->wordLength) ] |= ( 1 << ( $index%$this->wordLength ) );
    }
    function get( $index ){
        if( $index > $this->storeSize ){
            echo "Error: BitStore get() index out of range <br>";
            $index = $index%$this->storeSize;      //mod for indexes beyond storageSize;
        }
        return ( $this->store[floor($index/$this->wordLength)] & ( 1 << ( $index%$this->wordLength ) ) ) > 0;
    }
    function drop( $index ){
        if( $index > $this->storeSize ){
            echo "Error: BitStore drop() index out of range <br>";
            $index = $index%$this->storeSize;      //mod for indexes beyond storageSize;
        }
        $this->store[ floor($index/$this->wordLength) ] &= ~( 1 << ( $index%$this->wordLength ) );
    }
    function toggle( $index ){
        if( $index > $this->storeSize ){
            echo "Error: BitStore toggle() index out of range <br>";
            $index = $index%$this->storeSize;      //mod for indexes beyond storageSize;
        }
        if( self::get( $index ) ){
            self::drop( $index );
        }
        else{
            self::set( $index );
        }
    }
    function compare( $indexA, $indexB ){
        return self::get( $indexA ) === self::get( $indexB );
    }
    function disp(){//Display bit store as binary, decimal, with integer index
        echo "<br>";
        foreach(  $this->store as $key => $value ){
            for ( $i = $this->wordLength-1; $i >=0; $i-- ) {
                if( $value & (1<<$i) ){
                    echo "1";
                }
                else{
                    echo "0";
                }
            }
            echo ": ".$value.": ".$key."<br>";
        }
    }
    function getStore(){
        return $this->store;
    }
}
function testBitStore(){
    echo "PHP_INT_SIZE=".PHP_INT_SIZE."<br>";
    /* Create store and set some flags */
    $b=new bitStore( 250 );
    $b->set(10);
    $b->set(65);
    /* Some arbitrary operations */
    echo "Index 5=".$b->get(5)."<br>";
    echo "Index 10=".$b->get(10)."<br>";
    echo "Index 12=".$b->get(65)."<br>";
    echo "compare 10 12=".$b->compare(10,65)."<br>";
    echo "compare 5 12=".$b->compare(5,65)."<br>";
    echo "compare 6 7=".$b->compare(6,7)."<br>";
    $b->disp();
    $b->toggle(12);
    $b->toggle(65);
    $b->disp();
}
