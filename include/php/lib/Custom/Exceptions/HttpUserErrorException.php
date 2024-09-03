<?php
namespace Custom\Exceptions;

use Box\Exceptions\HttpException;

/**
 * Eccezione che contiene messaggio per utente ma non per admin
 */
class HttpUserErrorException extends \Box\Exceptions\GenericErrorException implements HttpException
{

    public function __construct($userMsg) {
        parent::__construct("", $userMsg);

    }


}