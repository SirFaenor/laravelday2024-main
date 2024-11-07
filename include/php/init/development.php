<?php

if(php_sapi_name() == 'cli') {
    return;
}


$DEVELOPER_SEVERS 	= array('192.168.1.127','192.168.1.128','192.168.1.129','127.0.0.1');
$DEVELOPER_IPS 		= array('5.157.97.37','79.7.244.221');
$IS_LOCAL_SERVER 	= array_search($_SERVER['SERVER_ADDR'],$DEVELOPER_SEVERS) !== false || (!empty($_SERVER) && $_SERVER["SERVER_NAME"] == 'localhost');
define("IS_LOCAL_SERVER",$IS_LOCAL_SERVER);

// ip host e connessione da cui è consentito attivare la sessione sviluppo

$IPs['local_host_ips']      = array("127.0.0.1","192.168.1.129");   // ip locali
$IPs['remote_host_ips']     = array();                              // ip remoti
$IPs['dev_host_ips']        = array_merge($IPs['local_host_ips'],$IPs['remote_host_ips']);
$IPs['remote_addr_ips']     = array("5.157.97.37","79.7.244.221");  // ip di connessione

if(!defined("IS_DEVELOPER")):           // se mi connetto da ip pubblici specifici, sono uno sviluppatore
    define("IS_DEVELOPER",(array_search($_SERVER['REMOTE_ADDR'],$IPs['remote_addr_ips']) !== false));
endif;
if(!defined("IS_DEV_ENV")):             // se sono ip di host specifici, allora sono in sviluppo
    define("IS_DEV_ENV",
        array_search($_SERVER['SERVER_ADDR'],$IPs['dev_host_ips']) !== FALSE
        || filter_var(getenv("IS_DEV_ENV"), FILTER_VALIDATE_BOOLEAN) === true
    );
endif;
if(!defined("IS_LOCAL_ENV")):    // se mi connetto da ip locali in server locali allora sono in locale
    define("IS_LOCAL_ENV",(IS_LOCAL_SERVER && (isset($_SERVER['REMOTE_ADDR']) && preg_match('/192\.168\.1\.[0-9]{1,3}/',$_SERVER['REMOTE_ADDR']))));
endif;

# imposto la modalità sviluppo con un parametro
if(isset($_GET['development_mode'])):
    if(IS_DEVELOPER || IS_LOCAL_SERVER):
        $_SESSION['APP_DEVELOPMENT_MODE'] = $_GET['development_mode'];
    else:
        unset($_SESSION['APP_DEVELOPMENT_MODE']);
    endif;
endif;



// Lingue utilizzabili in modo statico nel sito
$ALLOWED_LANGUAGES 	= array('it');
$fallback_site_lang = 'it';     // lingua di fallback del sito

// url esclusi dalla preview
$arByPassUrls = array(
    '/pp_ipn_listener\.php/'
    ,'/^\/?cron\/(.+)\.php$/'
);
foreach($arByPassUrls as $RE):
    if(!defined('BYPASS_PREVIEW') && preg_match($RE,$_SERVER['REQUEST_URI'])):
        define('BYPASS_PREVIEW',true);
        break;
    endif;
endforeach;
if(!defined('BYPASS_PREVIEW')):
    define('BYPASS_PREVIEW',false);
endif;
