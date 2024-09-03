<?php 
$App->BrowserQueue->noSave();

$App->Lang->loadTrads("cart_global,cart_mail,errors,cart_help,user");

try{
    

    /**
     * Recupero ordine
     * Lancia da solo eccezione se ordine non trovato
     */
    $Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $App->Cart->getorderID());

	// azzero i tentativi di pagamento
	$App->Cart->paymentTry = '';

	// aggiorno ordine
	$Order->confirm();

	// aggiorno pagamento
	$Order->progressPayment();


    // Non invio mail in quanto l'ordine non è ancora stato pagato


} catch (Exception $e) { 
	$_SESSION['errors'] = $App->Lang->returnT('generic');
	$App->ErrorLogger->handleException($e);
}

$App->redirect($App->Lang->returnL('cart_order_completed'));

