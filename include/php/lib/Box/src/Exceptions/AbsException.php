<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2017-04-04
 */

namespace Box\Exceptions;

use \Exception as RootException;


/**
 * Implementazione astratta dell'interfaccia HttpException.
 * 
 * Non definisce un costruttore, in modo che le classi figlie accedano direttamente al costruttore
 * dell'eccezione base di Php.
 * 
 * Si possono creare classi con responsi che contengono messaggi / path specifici per un progetto.
 * v. README e tests/exceptions.php per esempi d'uso.
 * 
 */

abstract class AbsException extends RootException implements \Box\Exceptions\HttpException 
{
    

    /**
     * stato della risposta
     */
    protected $responseStatus = 'HTTP/1.1 500 Internal server error';
    

    /**
     * messaggio di risposta
     */
    protected $responseMessage = null;


    /**
     * Restuisce lo stato memorizzato internamente
     */
    public function getResponseStatus() {
        return $this->responseStatus;
    }
    public function getResponseMessage() {
        return $this->responseMessage;
    }



    /**
     * Accesso al solo codice dello stato
     */
    public function getResponseCode() {
        return (int)substr($this->responseStatus, 9,3);
    }

}
