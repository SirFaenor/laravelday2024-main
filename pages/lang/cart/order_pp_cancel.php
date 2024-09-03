<?php 
/**
 * Pagina di rientro da paypal dopo che utente ha annullato pagamento
 * (su Paypal)
 */
$App->Lang->loadTrads('errors,cart_help');
$App->BrowserQueue->noSave();


/**
 * Recupero ordine
 * Lancia da solo eccezione se ordine non trovato
 */
$Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $App->Cart->getorderID());


/**
 * Aggiorno pagamento
 */
$Order->cancelPayment();


/**
 * Fermo procedura e mostro messaggi "Bisogno di aiuto?"
 */
$message = '<br>'.$App->Lang->returnT('page_text', ["mail" => $App->Config["site"]["mail_orders"]]);
$App->create("cart_error", $App->Lang->returnT('page_title'), $message, $back = $App->Lang->returnL('cart_user_data'));