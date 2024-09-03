<?php

namespace Custom\Box\Exceptions;

/**
 * Contiene responso per "Comingsoon".
 */
class ComingsoonException extends \Box\Exceptions\AbsException
{
    
    protected $responseStatus = 'HTTP/1.1 503 Service Unavailable';
    
    public function __construct($requirePath = NULL,$message = '') {
        
        http_response_code(503);
        header('Retry-After: 86400');    # riprova ogni giorno
        if(!$requirePath):
	        parent::__construct($message,E_USER_ERROR);
        else:
        	if (strpos($_SERVER['REQUEST_URI'], "comingsoon.php") === false):
            	require($requirePath);
            	exit;
            endif;
        endif;

    }

}

