<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2016-09-30
 */

namespace Box;

use ArrayAccess;

/**
 * Semplice contenitore di variabili immutabili.
 */
class Params implements ArrayAccess
{

    private $registry = array();
    
    
    /**
     * Istanza / costruttore
     */
    public function __construct (array $values) {
        $this->registry = $values;
    }

    public function offsetSet($offset, $value) {
        if ($this->offsetExists($offset)) {throw new \Exception("Impossibile modificare valori (".$offset." - ".$value.").");}

        $this->registry[$offset] = $value;
    }

    public function offsetExists($offset) {
        return isset($this->registry[$offset]);
    }

    public function offsetUnset($offset) {
        throw new \Exception("Impossibile eliminare valori (".$offset." - ".$value.").");
    }

    public function offsetGet($offset) {
        return $this->offsetExists($offset) ? $this->registry[$offset] : null;
    }


    /**
     * Previene sovrascrittura dei valori già inseriti
     */
    public function __set($name, $value) {
        return null;
    }
    
    /**
     * Definisco anche __get senza ritorno per riferimento
     * v. https://bugs.php.net/bug.php?id=39449
     */
    public function __get($name) {
        return null;
    }


}
