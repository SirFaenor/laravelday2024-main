<?php
/**
 * Uscita dall'esecuzione
 * Blocca esecuzione e restituisce codice di risposta adeguato.
 * Richiede costanti definite PAGES_ROOT
 * 
 * @param string $httpCode codice http di risposta
 * @param string $urlOrPath percorso script da includere
 * 
 * @version 2016/07/25
 */
function redirectExit($httpCode = '404', $pathOrUri = '') {
    
    $arHeader = array (
        '404' => 'HTTP/1.1 404 Not Found',
        '301' => 'HTTP/1.1 301 Moved Permanently',
        '301' => 'HTTP/1.1 302 Temporary Redirect',
        '307' => 'HTTP/1.1 307 Temporary Redirect',
        '403' => 'HTTP/1.1 403 Forbidden',
        '500' => 'HTTP/1.1 500 Internal Server Error',
        '400' => 'HTTP/1.1 400 Bad Request'
    );
    
    if (!empty($arHeader[$httpCode])) {
        header($arHeader[$httpCode]);
    }

    switch ($httpCode):
        case '404' : {
            $path = $pathOrUri ? $pathOrUri : PAGES_ROOT.'/not_found.php';

            require($path);
            exit;

        }

        case ( in_array((string)$httpCode, array('301','302','307') ) ) : {
            $url = $pathOrUri;

            header("Location: ".$url);
            exit;
        }

        default : {
            $path = $pathOrUri ? $pathOrUri : PAGES_ROOT.'/error.php';

            require($path);
            exit;
       }
    endswitch;
    
}


/**
 * Controllo disponbilità carrello.
 * Reindirizza a pogina dettaglio carrello (dove ci sarà listino
 * ma senza possbilità di ordine)
 */
function redirectStoreUnavailable()
{
   
    if(STORE_AVAILABLE !== true) {

        App::r()->Cart->emptyCart();

        redirectExit(302, App::r()->Lang->returnL('cart_detail'));
    }

    return true;
}