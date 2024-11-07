<?php
/**
 * Inizializzazione della gestione errori
 */

// se non sono in debug, gli errori fatali sono presi in carico da shutdown_function
// tutti gli altri errori sono passati al gestore set_error_handler 
ini_set("display_errors", $App->Config["errors"]["debug_mode"]);
define("DEBUG_MODE", $App->Config["errors"]["debug_mode"]);

// registro il servizio di gestione errori
// a cui passare errori ed eccezioni (v.sotto)
$App->store("ErrorLogger", new \Box\ErrorLogger(array(
    
    "debug_mode" => $App->Config["errors"]["debug_mode"]
        
    // impostare directory se si vuole controllo ddos
    ,"error_counter_path" => DOCUMENT_ROOT.'/file_public/error_counter'

    // qui arrivo solo se ErrorLogger NON è impostato in debug mode
    ,"admin_alert_callback" => function ($error_logger_message) use ($App) {

        $mailBody = require realpath(__DIR__.'/../').'/admin_alert_body.php';
        foreach (array(
                "site_name" =>  $App->Config["site"]["name"]
                ,"msg" =>  $error_logger_message
            ) as $search => $value): 
            $mailBody = str_replace('{{'.$search.'}}', $value, $mailBody);    
        endforeach;

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true); // true per lanciare eccezioni
        $mail->IsHTML(true);
        $mail->CharSet = "UTF-8";

        $mail->AddAddress($App->Config["errors"]["alert_mail"], $App->Config["site"]["name"]);
        $mail->SetFrom('errors@'.$App->Config["site"]["domain"],$App->Config["site"]["name"]);

        $mail->Subject = strip_tags('ERRORE SITO - '.$App->Config["site"]["name"]);
        $mail->Body = $mailBody;
        $mail->AltBody = strip_tags('ERRORE SITO - '.$App->Config["site"]["name"]);
        $mail->WordWrap = 50;
        try {
            $c = $mail->Send();
        } catch (Exception $e) {
            if (defined("DEBUG_MODE") && DEBUG_MODE) {echo $e->getMessage();}
        }

    } 

    ,"site_name"    => $App->Config['site']['name']

)));


// gestore base delle eccezioni
set_exception_handler(function($exception) use ($App) {

    // delego a ErrorLogger per eventuali comunicazioni
    $App->ErrorLogger->handleException($exception);
    if ($exception instanceof \Box\Exceptions\HttpException && $exception->getResponseMessage()):
        $App->store("ResponseMessage", $exception->getResponseMessage());
    endif;
    $code = $exception instanceof \Box\Exceptions\HttpException ? $exception->getResponseCode() : 500;

    // esco da esecuzione  
    if (!headers_sent()):
        http_response_code($code);
    endif;

    if (!$App->Config["errors"]["debug_mode"]): 
        ob_end_clean(); 
        ob_start(); 
    endif;
    require($code == 404 ? PAGES_ROOT.'/not_found.php' : PAGES_ROOT.'/error.php');
    exit;

});


// gestore base degli errori
set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext = null) use ($App) {

    // delego a ErrorLogger per eventuali comunicazioni
    $App->ErrorLogger->handleError($errno, $errstr, $errfile, $errline);

    // se errore grave, abort on responso di errore generico (clearBuffer se non sono in debug mode)
    if (in_array($errno, [2,256])) :
    
        // esco da esecuzione  
        if (!headers_sent()):
            header('HTTP/1.1 500 Internal server error');
        endif;
        if ($App->Config["errors"]["debug_mode"] == false) {ob_end_clean(); ob_start(); }
        require(PAGES_ROOT.'/error.php');
        exit;
    endif;

});


register_shutdown_function(function() use ($App) {
    
    // se ho un ultimo errore fatale
    $error = error_get_last();

    if ($error && in_array((int)$error["type"], array(1,4))) {
    
        $App->ErrorLogger->handleError($error["type"], $error["message"], $error["file"], $error["line"]);

        // esco da esecuzione  
        if (!headers_sent()):
            header('HTTP/1.1 500 Internal server error');
        endif;
        if ($App->Config["errors"]["debug_mode"] == false) {ob_end_clean(); ob_start(); }
        require(PAGES_ROOT.'/error.php');
        exit;
    }
});
