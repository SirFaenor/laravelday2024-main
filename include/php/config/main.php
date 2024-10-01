<?php
/**
 * Configurazione base dell'applicazione
 * v. init.php
 */


/* ARRAY CONFIGURAZIONE 
------------------------------------------------------------------------------------------ */
$AppDocumentRoot = realpath(__DIR__.'/../../../');
$has_certificate = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? true : false;
$protocol = $has_certificate === true ? 'https://' : 'http://';

$c = array(
    
    "maintenance_mode"                  => getenv("APP_DOWN") == 'maintenance' ? 1 : 0

    ,"site"                             => require __DIR__.'/project/site.php'

    ,"company"                          => require __DIR__.'/project/company.php'

    ,"errors" => array(
         "alert_mail"                   => 'errori@atriostudio.it'
        ,"default_page"                 => '/error.php'
        ,"debug_mode"                   => 
            (isset($_SESSION['APP_DEVELOPMENT_MODE']) && $_SESSION['APP_DEVELOPMENT_MODE'] == 1)
            ? 1
            : 0
        ,"user_default_message"         => 'Si &egrave; verificato un errore nel caricamento della pagina, prova ancora.<br>
                                            I dettagli di questo errore sono gi&agrave; stati inviati all\'amministratore del sistema'
    )
    
    ,"urls" => array(
        "app_base"                      => ''
    )

    ,"path" => array(
       
        // root del server
        "document_root"                 => $_SERVER["DOCUMENT_ROOT"]

        // root dell'applicazione (sito)
        ,"app_root"                     => $AppDocumentRoot

        // root dei dati (recupero sempre i files nel dominio test e non dev)
        ,"data_root"                     => !empty($_SERVER['HTTP_HOST']) && preg_match('/dev[0-9]+\-[^\/\?\#\:\@]+/',$_SERVER['HTTP_HOST']) ? realpath($AppDocumentRoot.'/../httpdocs/') : $AppDocumentRoot

    )
  
    /**
     * PhpMailer
     */
    ,"phpmailer"                         => __DIR__.'/../lib/vendor/PhpMailer/PHPMailerAutoload.php'
  
    /**
     * Db
     */
    ,"mysql"                             => require __DIR__.'/project/db.php'
    
    ,"mysql_ws" => [
        "host" => getenv("WS_DB_HOST") ?: ""
        ,"user" => getenv("WS_DB_USERNAME") ?: ""
        ,"password" => getenv("WS_DB_PASSWORD") ?: ""
        ,"database" => getenv("WS_DB_DATABASE") ?: ""
    ]


    /**
     * impostazioni notifiche
     */
    ,"notifier" => array(
        "mode" => getenv("NOTIFIER_MODE") ?: 'ls'
        ,"log_path" => __DIR__.'/../../../app_logs'
    )


    /**
     * tracking
     */
    ,"google"                           => require __DIR__.'/project/google.php'

    ,"social_data"                      => require __DIR__.'/project/social.php'

    ,"newsletter"                       => require __DIR__.'/project/newsletter.php'
    
    
    /**
     * Performance/SEO
     */
    ,"modules"                          => array(
                                                    'minimize'                  => false
                                                    ,'structured_data'          => false
                                                    ,'responsive_images'        => false
                                                    ,'multi_favicon'            => true
                                                    ,'pwa'                      => false
                                                )

);


if(strlen(getenv("APP_DEBUG"))) {
   $c["errors"]["debug_mode"] =  filter_var(getenv("APP_DEBUG"), FILTER_VALIDATE_BOOLEAN) === true ? 1 : 0;
}

return $c;


// TOKEN GITHUB
// //  5f1102080431211ba19b0dd68b59e4f1042d4810 