<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2017-04-04
 */

namespace Box\Exceptions;

use \Box\Response\StdResponse as StdResponse;

/**
 * Contiene responso per "Bad Request".
 * Ammette sovrascrittura da esterno del messaggio utente e della path.
 * Se gestita da Box\ErrorLogger non causa alert ad admin.
 */
class BadRequestException extends AbsException
{

    protected $responseStatus = 'HTTP/1.1 400 Bad Request';
   
    public function __construct($responseMessage = 'Bad Request') {
        
        parent::__construct(null, E_USER_WARNING);

        $this->responseMessage = $responseMessage;

    }

   
}
