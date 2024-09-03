<?php 
$App->Lang->loadTrads('errors,carrello_help,carrello_mail,carrello');
$App->BrowserQueue->noSave();

$_SESSION['errors'] = $App->Lang->returnT('cart_order_suspended_error').'<br>'.$App->Lang->returnT('page_intro2');

try {
	
	// recupero l'ordine
	$thisOrder = $App->Da->getSingleRecord(array(
		"table" => "ordine"
		,"cond" => "WHERE id = ".$App->Cart->getorderID()
	));

	if (!$thisOrder):
		throw new \Exception("Errore - ordine non trovato con ID_ORDINE: ".$App->Cart->getorderID(),E_USER_WARNING);
	endif;

	// aggiornamento ordine 
	$arUpdateOrder = array(
						'stato'		=> 0					// lo stato non è ancora completato
					);

	// aggiorno ordine
	$App->Da->updateRecords(array(
		"table" => "ordine"
		,"data" => $arUpdateOrder
		,"cond" => "WHERE id = ".$thisOrder['id']
	));



	// aggiornamento pagamento 
	$arUpdatePayment = array(
						'stato'	=> 'Denied'
					);												// data ora esito transazione

	// aggiorno pagamento
	$App->Da->updateRecords(array(
		"table" => "ordine_pagamento"
		,"data" => $arUpdatePayment
		,"cond" => "WHERE ordine_code = '".$thisOrder['ordine_code']."' AND id = ".$App->Cart->getCustomData('ordine_pagamento','id')
	));


	// invio notifica ad admin e utente
	$Ordine = new \Custom\Ecommerce\Order($App->Da,$App->Lang,'id',$App->Cart->getorderID());
	if($Ordine->isLoaded() !== true):
		throw new \Exception("Errore invio comunicazione esito ordine: il pagamento è stato negato e non è stata inviata conferma mail a utente e admin",E_USER_WARNING);
	endif;	


} catch (Exception $e) { 

	$_SESSION['errors'] = $App->Lang->returnT('cart_order_generic_error').'<br>'.$App->Lang->returnT('page_intro2');
	$App->ErrorLogger->handleException($e);

} 

// reindirizzo
$App->redirect($App->Lang->returnL('cart_order_help'));
	
