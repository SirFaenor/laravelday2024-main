<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2017-04-04
 */

namespace Box\Exceptions;

/**
 * Contiene responso per "non autorizzato"
 * Ammette sovrascrittura da esterno del messaggio passandolo al costruttore.
 * Se gestita da Box\ErrorLogger non causa alert ad admin.
 */
class AuthorizationException extends AbsException {
    
    protected $responseStatus = 'HTTP/1.1 401 Not authorized';

    public function __construct($responseMessage = 'You are not authorized to access this resource.') {
        
        parent::__construct(null, E_USER_WARNING);

        $this->responseMessage = $responseMessage;

    }

}

