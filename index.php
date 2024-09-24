<?php
/**
 * Script di gestione di tutte le richieste,inoltrate qui da htaccess
 */


/**
 * Inclusioni base
 */

require_once __DIR__."/include/php/init/main.php";


/**
 * Homepage lingua principale
 */
if ($_SERVER["REQUEST_URI"] == '/it/') :
    $App->redirect('/',"301");
endif;


/**
 * Link stampato in loco per "ordina online"
 */
if ($_SERVER["REQUEST_URI"] == '/osteria/') :
    $App->redirect($App->Lang->returnL("cart_detail"),"301");
endif;

/**
 * Manutenzione
 */
$_SESSION['pv'] = 
                (!empty($_GET['pv']) && $_GET['pv'] == 1) || 
                (isset($_SESSION['pv']) && $_SESSION['pv'] == 1)
                ? true : false;
$maintenance_mode = $App->Config["maintenance_mode"];

if(
    // SE SONO IN PREVIEW O STO RICHIAMANDO L'IPN LISTENER ANNULLO LA MANUTENZIONE
    $maintenance_mode && 
    (
        $_SESSION['pv'] === true 
        || (defined('BYPASS_PREVIEW') && BYPASS_PREVIEW === true)
    )
):
   $maintenance_mode = 0;
endif;
if ($maintenance_mode):

    // di base reindirizzo tutto alla home
    if($_SERVER['REQUEST_URI'] != '' && $_SERVER['REQUEST_URI'] != '/'):
        $App->redirect('/');
    endif;

    http_response_code(503);
    switch($maintenance_mode):
        case 2: // comingsoon
            header('Retry-After: 86400');   # riprova ogni giorno
            require(__DIR__.'/pages/comingsoon.php');
        break;
        case 1: // manutenzione
            header('Retry-After: 3600');    # riprova ogni ora
            require(__DIR__.'/pages/maintenance.php');
    endswitch;
    exit;
endif;


/**
 * Lingua custom non ancora pubblicata
 */
$_SESSION['custom_lang'] = 
                (!empty($_GET['cl']) && $_GET['cl'] == 1) || 
                (isset($_SESSION['custom_lang']) && $_SESSION['custom_lang'] == 1)
                ? true : false;


// ottengo le lingue pubblicate
$language_method = $_SESSION['custom_lang'] === true ? 'getAllLanguages' : 'getPublicLanguages';
$arLangs    = array_column($App->Lang->{$language_method}(),'suffix');


/**
 * Homepage lingua principale
 */
if ($_SERVER["REQUEST_URI"] == $arLangs[0] || $_SERVER["REQUEST_URI"] == '/'.$arLangs[0].'/homepage.php'):
    $App->redirect('/',"301");
endif;

if ($_SERVER["REQUEST_URI"] == '/'.$App->Lang->lgSuff.'/homepage.php') :
    $App->redirect($App->Lang->returnL('homepage'),"301");
endif;


/**
 * Routing
 */
$Router = new \Box\Router();

// link in lingua
$App->Lang->loadLinks();
$links = $App->Lang->getLinks();
if ($links) :
    foreach ($links as $code => $values): 
        if ($values["pattern"]):
            $Router->addRoutePattern($code, $values["link"], LANGUAGE_ROOT.'/'.$values["filename"]);
        else :
            $Router->addRoute($code, $values["link"], LANGUAGE_ROOT.'/'.$values["filename"]);
        endif;
    endforeach;
endif;

$Router->addRoute("homepage_root", "/", LANGUAGE_ROOT."/homepage.php"); 

// robots
$Router->addRoute("robots_txt", "/robots.txt", DOCUMENT_ROOT."/robots.txt.php"); 

// sitemap
$Router->addRoute("sitemap_xml", "/sitemap.xml", DOCUMENT_ROOT."/sitemap.xml.php"); 

// default link pagine lingua
$Router->addRoutePattern("default_pages_lang", "/(".implode('|',$arLangs).")/(.+)", LANGUAGE_ROOT.'/$2'); 

// default link pagine 
$Router->addRoutePattern("default_pages", "/(.+)", PAGES_ROOT.'/$1'); 


/**
 * Corrispondenza trovata
 */
$target = $Router->matchRequest($_SERVER["REQUEST_URI"]);


/**
 * Esegue lo richiesta
 */
if ($target["file"] && is_file($target["file"])) :

    // Unisco get con altri eventuali valori trovati nel target
    $_GET = array_merge($_GET ,$target["params"]);
    
    // correggo valori di alcune variabli server (per uniformare comportamento come se non ci fosse dispatcher.php)
    $_SERVER["SCRIPT_FILENAME"] = PAGES_ROOT.$target["file"];
    $_SERVER["PHP_SELF"] = str_replace(DOCUMENT_ROOT, "", $_SERVER["SCRIPT_FILENAME"]);
    $_SERVER["SCRIPT_NAME"] = $_SERVER["PHP_SELF"];

    // imposto attivo su nome regola (poi eventualmente verrà sovrascritto)
    $App->Lang->setActive($target["name"]);

    // richiedo pagina
    require($target["file"]);
  
    unset($target);

    exit();

endif;

/**
 * Not found finale 
 */
throw new \Box\Exceptions\NotFoundException(null);
