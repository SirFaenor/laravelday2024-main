<?php
namespace Utility;

use Mobile_Detect;

class BrowserChecker {

	private $browserExpiredPath;  // percorso della pagina browser expired
    public $MobileDetect;

    public function __construct($browser_expired_path = NULL){
        if($browser_expired_path):
            if(file_exists($browser_expired_path)):
                $this->browserExpiredPath = $browser_expired_path;
            else:
                trigger_error('['.__METHOD__.'] '.$browser_expired_path.': percorso inesistente',E_USER_NOTICE);
            endif;
        endif;
    }

    public function setMobileDetect(Mobile_Detect $MD){
        $this->MobileDetect = $MD;
    }

    public function  check(){

        /**
         * fermo controllo  se non serve (es. cli)
         */
        if(empty($_SERVER["REQUEST_URI"])) {
            return;
        }

        if (
            !isset($_SERVER['HTTP_USER_AGENT'])
            ||  preg_match('/MSIE 6/', $_SERVER['HTTP_USER_AGENT'])
            ||  preg_match('/MSIE 7/', $_SERVER['HTTP_USER_AGENT'])
            ||  preg_match('/MSIE 8/', $_SERVER['HTTP_USER_AGENT'])
            ||  preg_match('/MSIE 9/', $_SERVER['HTTP_USER_AGENT'])
        ) :

            header('HTTP/1.1 403 Forbidden');
            if($this->browserExpiredPath):
                require $this->browserExpiredPath;
            else:
                exit('<a href="https://browsehappy.com/" target="_blank">Il tuo browser è obsoleto, scegli un nuovo browser</a>');
            endif;
            exit;

        endif;
    }

}
