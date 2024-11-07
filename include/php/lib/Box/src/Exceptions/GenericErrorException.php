<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2017-04-04
 */

namespace Box\Exceptions;

/**
 * Eccezione generica concreta adatta ad essere usata con ErrorLogger.
 * Ammette sovrascrittura da esterno del messaggio admin e del messaggio del responso
 */
class GenericErrorException extends AbsException
{
    
    protected $responseStatus = 'HTTP/1.1 500 Internal server error';
    
    public function __construct($adminMessage = 'Generic error', $responseMessage = '') {
        
        parent::__construct($adminMessage, E_USER_ERROR);

        $this->responseMessage = $responseMessage;

    }

   
}

