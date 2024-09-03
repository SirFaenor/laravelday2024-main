<?php
/**
 * Controlla se url attuale necessita di redirect
 * Incluso da dispatcher.php
 */
//return;


/**
 * Controlla tabella che contiene i redirect
 * per corrispondenza con url attuale.
 */
$requestUri = $App->UrlUtility->requestUri();




// recupero record
$resultRedirect = $App->Da->customQuery("SELECT * FROM link_redirect WHERE link_old LIKE ? ORDER BY id DESC LIMIT 1", array('%'.$App->Config["site"]["domain"].$requestUri));

// se trovo, reindirizzo al protocollo http (eventualmente cambiare)
if ($resultRedirect) :
    
    $link = $resultRedirect[0]["link_new"];

    if($App->Config['site']['has_certificate'] === true):
    	$link = str_replace($App->Config['site']['protocol'],'http://',$link);
    endif;
    $link = str_replace($App->Config["site"]["url"],'',$link);
     
    $App->redirect($link,$resultRedirect[0]['code']);
endif;
