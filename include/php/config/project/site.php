<?php 

if(!defined("HAS_CERTIFICATE")):
    define('HAS_CERTIFICATE',(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443));
endif;

if(!defined("SITE_PROTOCOL")):
    define('SITE_PROTOCOL',(HAS_CERTIFICATE ? "https://" : "http://"));
endif;

$key = 'laravel';        /* valore univoco per l'impostazione dei cookies e delle sessioni */
$domain = 'laravel';
$host = defined("IS_LOCAL_SERVER") && IS_LOCAL_SERVER && !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

$site = array(
		        "name"          => 'Laravel'
		        ,"has_certificate" => HAS_CERTIFICATE
		        ,"protocol"     => SITE_PROTOCOL
				,"host" => $host
		        ,"domain"       => $domain
				,'url'  => getenv("APP_URL") ?: SITE_PROTOCOL.$_SERVER['HTTP_HOST']
				,"absolute_url" => getenv("APP_URL") ?: SITE_PROTOCOL.$_SERVER['SERVER_NAME']
				,"cookie_name" => $key.'_frontcookie'
				,"cookie_cms_name" => $key.'_cmscookie'
		        ,"color"        => '#F8CA14'
		        ,"code"         => 'Laravel'


		            
		        /**
		         * le caselle sono impostate come destinatari in maniera specifica
		         * in vari punti del sito
		         */
		        ,"mail"         => array()
				,"mail_orders" => ''
				,"mail_fallback" => ''
				,"mail_noreply" => ''

				,"webservice_url" => getenv("WEBSERVICE_URL") ?: ''
				,"webservice_token" => getenv("WEBSERVICE_TOKEN") ?: ''
			);

return $site;
