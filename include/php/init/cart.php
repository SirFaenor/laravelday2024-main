<?php
/**
 * Funzionalità del carrello
 */


/**
 * Service per alcuni calcoli comuni
 */
$App->store("CalculatorService", function() use($App) {
    return new \Ecommerce\CalculatorService('EUR');
});


/**
 * Service per notifiche
 */
$App->factory("NotifierService", function($mode = null) use ($App) {
    $mode = $mode == null ? $App->Config["notifier"]["mode"] : $mode;
    return new Site\NotifierService($App->Config["notifier"]["log_path"], $mode);
});


/**
 * Classi del carrello
 */

// quando la classe utente viene istanziata, viene impostata la categoria di default
$App->User->hashOnLogin = 'sha512';

$App->Lang->loadTrads("cart_global");

$App->store("Ecomm", function() use ($App) {
    $Ecomm = new \Custom\Ecommerce\Ecommerce($App->Da,$App->Lang, $App->User, $App->FixNumber, $App->FixString);
    $Ecomm->Name = $App->Config['site']['name'];
    return $Ecomm;
});

$App->store("Cart", function() use ($App) {
    
    return new \Custom\Ecommerce\Cart($App->Da,$App->Lang, $App->User, $App->Ecomm, $App->Currency, $App->FixNumber, $App->FixString);
    
});


/**
 * Factory per la creazione degli oggetti destinati al carrello.
 * Converte un entita nel tipo di oggetto carrello corrispondente.
 * Necessita dei DataModel per ogni tipo di entità che deve essere
 * convertita per il carrello.
 * Per ora, solo i prodotti, ma di due tipi
 */
$App->store("CartItemFactory", function() use ($App) {

    return new \Custom\Ecommerce\CartItemFactory([
        "products" => $App->create("ProductsRepository")
    ]);

});


/**
 * Errore
 * Richiama uno script di uscita durante la procedura d'ordine
 * @param string $title titolo della pagina di errore
 * @param string $message messaggio della pagina di errore
 * @param string $back link da impostare nel tasto "indietro" nella pagina di errore
 */
$App->factory("cart_error", function($title = null, $message = null, $back = null) use ($App) {

    $title = $title !== null ? $title : $App->Lang->returnT("error_generic_title");

    $message = $message !== null ? $message : $App->Lang->returnT("error_generic_text");

    http_response_code(500);
    
    require LANGUAGE_ROOT.'/cart/order_error.php';
    exit;
});




/**
 * Ottengo gli elementi nel carrello
 */
$arCartItems = is_array($App->Cart->getItems()) ? $App->Cart->getItems() : array();



/**
 * Store disponible?
 * (per condizioni particolari)
 */
$storeAvailable = false;


/**
 * Impostazione da db
 */
if( (int) setting("STORE_AVAILABLE") ==  1) {
    $storeAvailable = true;
}   


/**
 * Le impostazioni da env o attivazione manuale sovrascrivono impostazione da db
 */
if(
    filter_var(getenv("STORE_AVAILABLE"), FILTER_VALIDATE_BOOLEAN) == true
    ||
    (!empty($_GET["store"]) && $_GET["store"] == 'fdlkjJye89364thdhk809872')    
    ||
    (array_key_exists("STORE_AVAILABLE", $_SESSION) && $_SESSION["STORE_AVAILABLE"] == true)    
) {

    $_SESSION["STORE_AVAILABLE"] = true;

    $storeAvailable = true;
}

define("STORE_AVAILABLE", $storeAvailable);

