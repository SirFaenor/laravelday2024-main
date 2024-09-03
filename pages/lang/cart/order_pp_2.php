<?php
/**
 * Pagina di ritorno da paypal dopo approvazione
 * pagamento da parte di utente su sito Paypal
 */
$App->BrowserQueue->noSave();
$App->Lang->loadTrads("cart_global,errors,cart_help");


/**
 * Funzioni paypal
 */
$PaypalHelper = $App->create("PaypalHelper");


try {
	   
	/* VERIFICO VALIDITÀ CARRELLO
	**********************************************************************/
	if($App->Cart->getTotalAmount() <= 0 || !$App->Cart->getorderID()):
		throw new Exception("Errore - Importo a 0 (".$App->Cart->getTotalAmount().") o non presente un id ordine (".$App->Cart->getorderID().")");
	endif;



	/* VERIFICO TOKEN PAYPAL
	**********************************************************************/
	$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : NULL;
	if(!($token && !empty($token))):
		throw new Exception("Errore - Token mancante nella pagina di ritorno da PayPal");
	endif;



	/* OTTENGO INFORMAZIONI SULLA TRANSAZIONE (negative test mode = 2)
	**********************************************************************/
	$resArray = $PaypalHelper->GetShippingDetails($token);
	$ack = strtoupper($resArray["ACK"]);
	if( $ack != "SUCCESS" ) :
		throw new Exception($PaypalHelper->ppApi_error($resArray,''));
	endif;



	/* VERIFICO CORRISPONDENZA TRANSAZIONE
	**********************************************************************/
	if ($App->Cart->getorderID() != $resArray["INVNUM"]):
		throw new Exception("Errore - Mancata corrispondenza INVOICE - ID_ORDINE nel ritorno da PP");
	endif;

    /**
     * Recupero ordine
     * Lancia da solo eccezione se ordine non trovato
     */
    $Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $App->Cart->getorderID());
	


	/* CONFERMO PAGAMENTO (negative test mode = 3)
	**********************************************************************/
	$arrayExtraOptions = array(
		'PAYMENTREQUEST_0_NOTIFYURL' => $App->Config['site']['url'].'/pp_ipn_listener.php'
	);

	$resArray = $PaypalHelper->ConfirmPayment($App->Cart->getTotalAmount(),$arrayExtraOptions);
	$ack = strtoupper($resArray["ACK"]);


	/**
	 * Aggiornamento stato pagamento ordine.
	 * Eseguiamo direttamente qui per avere risposta sincrona per il cliente.
	 * Il listener IPN rimane in ascolto per inviare la mail di conferma ed aggiornare
	 * altre info relative al pagamento (v. pp_ipn_listener.php)
	 */
	if(!array_key_exists('PAYMENTSTATUS', $resArray)) {
		throw new Exception("Errore nel recupero dello stato del pagamento. resArray:  ".print_r($resArray, 1 ));
	}
	switch($resArray['PAYMENTSTATUS']):
		// completato
		case 'Completed':

			$Order->confirmPayment();
			
			break;
		
		// fallito
		case 'Failed':
			
			$Order->failPayment();
		
			break;

	endswitch;



	/**
	 * Errore conferma pagamento,
	 * blocco la procedura
	 */
	if( $ack != "SUCCESS" ) :
		
		
		/**
		 * Aggiorno pagamento con errore
		 */
		$Order->paypalPaymentError($resArray);
		
		
		/**
		 * Errore admin e utente
		 */
		$App->ErrorLogger->alert($PaypalHelper->ppApi_error($resArray,''));
		$App->create("cart_error");
	
	endif;

        
	/**
	 * Tutto ok, confermo ordine.
	 * La mail di conferma viene mandata async dal listener.
	 */		
	$Order->confirm();


	/**
	 * Tutto ok, redirect a pagina di conferma
	 */
	$App->redirect($App->Lang->returnL("cart_order_completed"));


} catch (Exception $e) { 


	/**
	 * gestione errore
	 * Se eccezione non è grave, non blocca la procedura e si passa
	 * a uscita con errore da carrello (v. sotto)
	 */
	$App->ErrorLogger->handleException($e);
	

	/**
   	 * Tentativo di recupero transazione per codici di errore specifici
   	 */
	if(
		(
			!isset($_SESSION["paymentTry"]) || $_SESSION["paymentTry"] != $_SERVER['REQUEST_URI'] 
		) && isset($resArray["TOKEN"])
	):
	
		$_SESSION["paymentTry"] = $_SERVER['REQUEST_URI'];
		switch(urldecode($resArray["L_ERRORCODE0"])):
			case 10486:
			case 10417:
			case 10422:
				// reindirizzo a PayPal
				$PaypalHelper->RedirectToPayPal( $resArray["TOKEN"] );
				exit;
		endswitch;

	endif;
	

	/**
	 * Fermo procedura
	 */
	$App->create("cart_error");


}

