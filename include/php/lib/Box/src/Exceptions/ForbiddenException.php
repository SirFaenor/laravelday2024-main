<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2016-09-30
 */

namespace Box\Exceptions;

use \Box\Response\StdResponse as StdResponse;

/**
 * Contiene responso per "Forbidden".
 * Ammette sovrascrittura da esterno del messaggio utente.
 * Se gestita da Box\ErrorLogger non causa alert ad admin.
 */
class ForbiddenException extends AbsException {

    protected $responseStatus = 'HTTP/1.1 403 Forbidden';
    
    public function __construct($responseMessage = 'Forbidden') {
        
        parent::__construct(null, E_USER_WARNING);

        $this->responseMessage = $responseMessage;

    }

}

