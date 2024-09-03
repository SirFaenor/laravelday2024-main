<?php

namespace Custom\Box\Exceptions;

/**
 * Contiene responso per "In manutenzione".
 */
class MaintenanceException extends \Box\Exceptions\AbsException
{
    
    protected $responseStatus = 'HTTP/1.1 503 Service Unavailable';
    
    public function __construct($requirePath = NULL,$message = '') {
        
        http_response_code(503);
        header('Retry-After: 3600');    # riprova ogni ora
        if(!$requirePath):
	        parent::__construct($message,E_USER_ERROR);
        else:
        	if (strpos($_SERVER['REQUEST_URI'], "meintenance.php") === false):
            	require($requirePath);
            	exit;
            endif;
        endif;

    }

}

