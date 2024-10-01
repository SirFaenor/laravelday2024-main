<?php
/**
 * 
 * 
 * Inizializzazione del container di Box
 * 
 * 
 */

use Custom\Ecommerce\Order;

/**
 * Inizializzazione componenti base
 */
if(!empty($_SERVER["REQUEST_URI"]) && __FILE__ == $_SERVER["DOCUMENT_ROOT"].$_SERVER["REQUEST_URI"]) {exit();} 


/**
 * ----------------------------------------------------------------------------------------------------------
 * SERVIZI
 * ----------------------------------------------------------------------------------------------------------
 */


/*
* DataAccess
*/
$App->store("Da", function () use($App) {
    
    $pdo =  new PDO('mysql:host='.$App->Config['mysql']['host'].';dbname='.$App->Config['mysql']['database'].';charset='.$App->Config['mysql']['charset'], $App->Config['mysql']['user'], $App->Config['mysql']['password'], array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)); 

    return new DataAccessPdo\Wrapper($pdo, $App->Config['mysql']['database']);
});


/**
 * Connessione a database webservice per
 * @todo scrive coda mail conferma ordine
 * - scrive elenco richieste rimborsi
 */
/* $App->store("DaWs", function () use($App) {

    $pdo =  new PDO('mysql:host='.$App->Config['mysql_ws']['host'].';dbname='.$App->Config['mysql_ws']['database'].';charset=utf8mb4', $App->Config['mysql_ws']['user'], $App->Config['mysql_ws']['password'], array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)); 

    return new DataAccessPdo\Wrapper($pdo, $App->Config['mysql_ws']['database']);
}); */


/**
 * Crea cliente per webservice, già autenticato
 */
$App->factory("WsClient", function() use ($App) {

    $client = new \GuzzleHttp\Client([
        'base_uri' => $App->Config["site"]["webservice_url"],
        'headers' => [
            'Authorization' => 'Bearer ' . $App->Config["site"]["webservice_token"],        
        ],
    ]);

    return $client;
    
});

/*
* LangManager
*/
$App->store("Lang", function () use($App) {
    
    $Lang = new \LangManager\LangManager($App->Da->getConnection(), array(
        "base_url"              => $App->Config["site"]["url"]
        ,"enable_language_dir"  => true
        ,"language_tags"        => true
        ,"force_language"       => !empty($_SERVER["REQUEST_URI"]) && $_SERVER["REQUEST_URI"] == '/' ? 'it' : false 
        ,"table_lg"             => "lang.active"
        ,"table_link"           => "lang_link"
        ,"table_trad"           => "lang_trad"
        ,"groups"               => "global,project,form"
    ));

    $Lang->loadLinks();
    $Lang->loadTrads();

    return $Lang;

});

$App->store('Logger',function() use ($App){
   
    return new \Utility\Logger($App->Config['logger']['log_path']);
 
});



/**
 * Classi necessarie al funzionamento del sito
 */
$App->store("Currency", function() use ($App) {
    return new \Utility\Currency(array(
        'decimalSeparator' => ','
        ,'thousandsSeparator' => '.'
        ,'currency' => ' €'
        ,'currencyPosition' => 1
    ));
});

$App->store("FixNumber", function() use ($App) {
    return new Utility\FixNumber;
});
$App->store("FixString", function() use ($App) {
    return new Utility\FixString(__DIR__.'/../lib/vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php');
});
$App->store("UrlUtility", function(){
    return new Utility\UrlUtility;
});

$App->store('warning',function() use ($App) {
    $thisWarning = $App->Da->getSingleRecord(array(
        'model'     => 'WARNINGS'
        ,'cond'     => 'AND XL.lang = '.$App->Lang->lgId.' HAVING published ORDER BY date_start ASC'
    ));

    if($thisWarning):
        $thisWarning['fullPageUrl'] = strlen($thisWarning['file_1']) && file_exists(DATA_ROOT.'/file_public/warning/'.$thisWarning['id'].'/'.$thisWarning['file_1']) ? $App->Lang->returnL('warning_img',array($thisWarning['id'],$thisWarning['file_1'])) : NULL;
    endif;

    return $thisWarning;
});



/**
 * phpmailer
 * Istanza base
 */
$App->factory("PhpMailer", function () use($App) {
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->IsHTML(true);
    $mail->CharSet = "UTF-8";

    return $mail;

});


$App->factory("Mailer",function() use ($App){
    $Mailer = new \Custom\Mailer($App->Lang);

    // dati fissi
    $Mailer->arMailInfo["PIVA"] = $App->Config["company"]["data"]["vat"];
    $Mailer->arMailInfo['SITE_NAME']  = $App->Config['site']['name'];
    $Mailer->arMailInfo['SITE_COLOR'] = $App->Config['site']['color'];

    // base url per link nella mail
    $Mailer->serverName = $App->Config["site"]["url"];

    return $Mailer;
});

/**
 * Form 
 * $params = array(
 *      'path_to_xml_file'  => percorso per il file xml con i campi
 *      ,'form_name'        => nome del form
 *  );
 */
$App->factory("Form", function ($params = array()) use ($App) {
    return new Site\Form($App->Lang,$params);
});


/**
 * Gestione referer
 */
$App->store("BrowserQueue" , new \Site\BrowserQueue([
    "max_referers" => 10
]));

// utente
$App->store("User", function() use ($App) {
    return new \Custom\User($App->Da, $App->Lang, $App->FixNumber, $App->FixString);
});


/**
 * FAQs
 */
$App->store('faqs',function() use ($App) {

    $arToReturn = array(
        'cats'  => array()
        ,'faqs'  => array()
    );

    $arToReturn['cats'] = $App->Da->getRecords(array(
        'model'     => 'FAQ_CATS'
        ,'cond'     => 'AND XL.lang = '.$App->Lang->lgId.' HAVING published ORDER BY position ASC'
    ));

    $arFaqs = $App->Da->getRecords(array(
        'model'     => 'FAQS'
        ,'cond'     => 'AND XL.lang = '.$App->Lang->lgId.' HAVING published ORDER BY XC.position ASC, X.position ASC'
    ));

    if($arFaqs):
        foreach($arFaqs as $F):
            $arToReturn['faqs'][$F['id_cat']][] = $F;
        endforeach;
    endif;

    return $arToReturn;

});


/**
 * ----------------------------------------------------------------------------------------------------------
 * REPOSITORY
 * ----------------------------------------------------------------------------------------------------------
 */ 


// Repository Categorie
$App->factory("ProductCatsRepository", function() use ($App) {
    
    return new \Custom\Repository\ProductCats($App->Da, $App->Lang, $App->ProductCatsDataModel);

});


// Repository Prodotti
$App->factory("ProductsRepository", function() use ($App) {
    
    return new \Custom\Repository\Products($App->Da, $App->Lang, $App->ProductsDataModel);

});




/**
 * ----------------------------------------------------------------------------------------------------------
 * DATAMODELS
 * ----------------------------------------------------------------------------------------------------------
 */ 


// DataModel Categorie (istanza unica)
$App->store("ProductCatsDataModel", function() use ($App) {
    return new \Custom\DataModel\ProductCats($App->Lang,$App->Ecomm->getAllSettings());
});


// DataModel Modelli prodotto (istanza unica)
$App->store("ProductsDataModel", function() use ($App) {

    return new \Custom\DataModel\Products($App->Lang,$App->CalculatorService,$App->Currency);

});


/**
 * ----------------------------------------------------------------------------------------------------------
 * ALTRI SERVIZI
 * ----------------------------------------------------------------------------------------------------------
 */ 

 /**
  * Endpoint webservice per generare qr code
  */
$App->factory("qrEndpoint", function(Order $order) use ($App) {    
    return $App->Config["site"]["webservice_url"].'/api/orders/'.$order->token.'/qrcode/png';
});


/**
 * Impostazioni da db
 */
$App->store("SETTINGS", function() use ($App) {
    $dbSettings = $App->Da->select([
        "table" => "settings"
    ]);

    if(!$dbSettings) {
        return [];
    }

    $settings = [];
    foreach ($dbSettings as $key => $row) {
        $settings[$row["code"]] = $row["valore"];
    }

    return $settings;
});

