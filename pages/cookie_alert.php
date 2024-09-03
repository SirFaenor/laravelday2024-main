<?php
require_once(__DIR__.'/../include/php/init/main.php');
require_once(__DIR__."/../include/php/init/container.php");
$App->Lang->loadTrads("global");

$is_ajax 	= !empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1 ? true : false;
$Router->saveReferer = false;

if(!empty($_GET['c'])):
    if(!session_id()): session_start(); endif;
    setcookie('cookie_alert',1,time()+60*60*24*365,'/');
    if($is_ajax === true):
    	exit('1');
    else:
		$redirect = $Router->getReferrer() !== null ? $Router->getReferrer() : $App->Lang->returnL('homepage');
		header('Location: '.$redirect);
    endif;
else:
	exit('0');
endif;
?>