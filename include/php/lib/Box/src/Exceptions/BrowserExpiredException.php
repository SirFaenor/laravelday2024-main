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
class BrowserExpiredException extends AbsException
{
    
    protected $responseStatus = 'HTTP/1.1 406 Not Acceptable';
    
    public function __construct($responseMessage = 'Old browser.') {
        
        parent::__construct(null, E_USER_WARNING);

        $this->responseMessage = $responseMessage;

    }

}

