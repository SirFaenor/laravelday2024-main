<?php
/**
 * Script comune incluso per tutte le richieste
 * da dispatcher.php.
 * Init principale e di base, non connette al db, va incluso quindi
 * anche nelle pagine di errore.
 */

/**
 * Init
 */
ob_start();
if(!empty($_SERVER["DOCUMENT_ROOT"]) && str_replace($_SERVER["DOCUMENT_ROOT"], "", __FILE__) == $_SERVER["REQUEST_URI"])
{
    exit('Pagina non trovata: 404') ;
} 
if (!session_id()){session_start();}


/**
 * composer
 */
require __DIR__.'/../lib/vendor/autoload.php';
require __DIR__.'/../lib/functions.php';


/**
 * Carico file env corrispondente al dominio, se c'è,
 * altrimenti carico file .env 
 * NB: SONO ALTERNATIVI, NON SI SOVRASCRIVONO!!
 */
try {

    $fileNameCustom = __DIR__.'/../../../.'.gethostname().'.env';
    $envFile = null;

    if($fileNameCustom && file_exists($fileNameCustom)) {
        $envFile = $fileNameCustom;
    } elseif(file_exists(__DIR__.'/../../../.env')) {
        $envFile = realpath(__DIR__.'/../../../.env');
    }
 
    if($envFile) {
        $dotenv = Dotenv\Dotenv::create(dirname($envFile), basename($envFile));
        $dotenv->load();
    }
    
} catch (Dotenv\Exception\InvalidPathException $e) {
    
    
}


/*
* VARIABILI SVILUPPO
*/
require(__DIR__.'/development.php');


/* Visione in preview
------------------------------------------------------------------------------------------ */
/**
 * Impostazioni server
 */
date_default_timezone_set("Europe/Rome");


/**
 * App
 */
require __DIR__.'/../lib/Box/src/App.php';
$App = new \Box\App();

/**
 * Autoloader
 */
$App->registerAutoload(__DIR__.'/../lib', array(
    "DataAccessPdo" 	=> "DataAccessPdo/src"
    ,"Html" 			=> "Html/src"
    ,"LangManager" 		=> "LangManager/src"
));


/**
 * Configurazioni
 */
$c = require(__DIR__.'/../config/main.php');
$App->store("Config", new \Box\Params($c));


/**
 * Wrapper per risolvere globalmente il container di servizi
 * per evitare refactoeing pesante
 */
new App($App);

/*
* COSTANTI BASE
*/
define("DOCUMENT_ROOT", $App->Config["path"]["app_root"]);
define("DATA_ROOT", $App->Config["path"]["data_root"]);
define("HTML_INCLUDE_PATH", $App->Config["path"]["app_root"].'/include/html');
define("PAGES_ROOT", $App->Config["path"]["app_root"].'/pages');
define("LANGUAGE_ROOT", $App->Config["path"]["app_root"].'/pages/lang');
define("BASE_URL", $App->Config["site"]["url"]);
define("DEVELOPMENT_MODE",$App->Config["errors"]["debug_mode"]);
define('SITE_URL',$App->Config['site']['url']);

define('IS_AJAX_REQUEST',(!empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == 'xmlhttprequest' ? true : false));


/**
 * Inclusioni altri init
 */
require_once __DIR__.'/errors.php';
require_once __DIR__.'/container.php';
require_once __DIR__.'/cart.php';
require_once __DIR__.'/password.php';

/**
 * OPERAZIONI PRELIMINARI COMUNI
 */
$App->store('Browser',new \Utility\BrowserChecker());
$App->Browser->check();


/*
* VARIABILI DI USO 
*/
$sitemail = $App->Config['site']['mail'];


/**
 * Funzione globale per accesso alle impostazioni
 */
function setting($code) {
    
    $App = App::r();

    if(!array_key_exists($code, $App->SETTINGS)) {
        throw new Exception("Impostazione mancante [$code]");
    }

    return $App->SETTINGS[$code];

}