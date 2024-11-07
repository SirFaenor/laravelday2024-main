<?php
/**
 * Script di gestione per le richieste di risorse statiche,
 * reindirizzate qui da htaccess
 */

 /**
 * Mappatura pattern del link => file fisico sul filesystem
 */
$arMap = array(
    '/files/(.+)$' 	=> '/files/$1'
    ,'/(.+)$' 		=> '/assets/$1'
);

/**
 * Cerca corrispondenza all'interno della mappatura
 */
$request = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$target = null;
foreach ($arMap as $matchPreg => $replaceMent):
    
    if (preg_match("#".$matchPreg."#", $request, $matches)) :
        $target = preg_replace("#".$matchPreg."#", $replaceMent, $request);
    endif;

    if ($target) {break;}
endforeach;


/**
 * Se trovo corrispondenza, restuisco immagine
 */
$fullPath = $target ? __DIR__.$target : NULL;

require __DIR__.'/dispatcher.inc.php';

exit;