<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2016-10-04
 */

namespace Box\Exceptions;

/**
 * Contiene responso per "Not found".
 * Ammette sovrascrittura da esterno del messaggio utente.
 * Se gestita da Box\ErrorLogger non causa alert ad admin.
 */
class NotFoundException extends AbsException
{
    
    protected $responseStatus = 'HTTP/1.1 404 Not found';
    
    public function __construct($responseMessage = 'Not found.') {
        
        parent::__construct(null, E_USER_WARNING);

        $this->responseMessage = $responseMessage;

    }

}

