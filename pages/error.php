<?php
require(__DIR__.'/../include/php/init/development.php');

$arTrads = array(
	'it'		=> array(
					    'meta_title' 	=> 'Errore'
					    ,'home_link'  	=> '/'
					    ,'page_title' 	=> 'OPS... si è verificato un errore'
					    ,'page_text'  	=> 'Si &egrave; verificato un errore nel caricamento della pagina, prova ancora.<br> I dettagli di questo errore sono gi&agrave; stati inviati all\'amministratore del sistema'
					    ,'back'       	=> 'Indietro'

					)
	,'en'		=> array(
					    'meta_title' 	=> 'Error'
					    ,'home_link'  	=> '/'
					    ,'page_title' 	=> 'OPS... an error occurred'
					    ,'page_text'  	=> 'An error occurred while loading the page, please retry.<br> Webmaster has been informed with error\'s details'
					    ,'back'       	=> 'back'

					)
);


try {
	$browser_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) 
	                                    ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2) 
	                                    : 'en';
	$current_lang = array_search($browser_lang,$ALLOWED_LANGUAGES) !== false ? $browser_lang : 'en';
} catch(Exception $e){

	$current_lang = $current_lang ?: 'en';
	
}

require ASSETS_PATH.'/html/page_static.php';
