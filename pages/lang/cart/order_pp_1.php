<?php
/**
 * 
 * Script di reindirizzamento a paypal per pagamento via NVP.
 * 
 */
$App->BrowserQueue->noSave();
$App->Lang->loadTrads("cart_global,errors,cart_help");


/**
 * Funzioni paypal
 */
$PaypalHelper = $App->create("PaypalHelper");


/**
 * Controllo situazione
 */
if($App->Cart->getTotalAmount() <= 0 || !$App->Cart->getorderID()):
    throw new Exception('Ordine non valido, dati mancanti.');
endif;


/**
 * Preparo opzioni
 */
$lgTag = 'IT'; 
if (!empty($App->Lang->lgTag)):
	$arTag = explode('-',$App->Lang->lgTag);
	$strTag = array_pop($arTag);
	$lgTag = strtoupper($strTag);
endif;
$arrayExtraOptions = array(
	'PAYMENTREQUEST_0_INVNUM' 		=> $App->Cart->getorderID()
	,'INVNUM' 						=> $App->Cart->getorderID()
	,'LANDINGPAGE' 					=> $App->Cart->getSelectedPaymentMethod()['code'] == 'PP_EXPRESS_CHECKOUT' ? 'Login' : 'Billing'
	,'SOLUTIONTYPE' 				=> 'Sole'		// l'utente non deve avere l'account paypal
	,'LOCALECODE' 					=> $lgTag
	,'PAYMENTREQUEST_0_NOTIFYURL' 	=> $App->Config['site']['url'].'/pp_ipn_listener.php'
	,'NOTIFYURL' 					=> $App->Config['site']['url'].'/pp_ipn_listener.php'
);




// ==================================
// PayPal Express Checkout Module
// ==================================

//'------------------------------------
//' The paymentAmount is the total value of 
//' the shopping cart, that was set 
//' earlier in a session variable 
//' by the shopping cart page
//'------------------------------------
$paymentAmount 		= $App->Cart->getTotalAmount();

//'------------------------------------
//' The currencyCodeType and paymentType 
//' are set to the selections made on the Integration Assistant 
//'------------------------------------
$currencyCodeType 	= "EUR";
$paymentType 		= "Sale";

//'------------------------------------
//' The returnURL is the location where buyers return to when a
//' payment has been succesfully authorized.
//'
//' This is set to the value entered on the Integration Assistant 
//'------------------------------------
$returnURL = $App->Lang->returnL('cart_order_pp_2');

//'------------------------------------
//' FALLBACK URLS
//' The cancelURL is the location buyers are sent to when they hit the
//' cancel button during authorization of payment during the PayPal flow
//' 
//' The errorURL is the location buyers are sent to when an error
//' occurs during authorization of payment during the PayPal flow
//' 
//' The helpURL is the location buyers are sent to when an error occurs
//' or they hit cancel during authorization of payment during the PayPal flow
//'------------------------------------
$cancelURL 	= $App->Lang->returnL('cart_order_pp_cancel');
$errorURL 	= $App->Lang->returnL('cart_order_pp_error');



//'------------------------------------
//' Calls the SetExpressCheckout API call
//'
//' The CallShortcutExpressCheckout function is defined in the file PayPalFunctions.php,
//' it is included at the top of this file.
//'-------------------------------------------------
$resArray = $PaypalHelper->CallShortcutExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $arrayExtraOptions);
$ack = strtoupper($resArray["ACK"]);


/**
 * Errore
 */
if($ack !== "SUCCESS"):

	// invio l'errore ad admin
	$App->ErrorLogger->alert($PaypalHelper->ppApi_error($resArray,''));
	

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


endif;


/**
 * Tutto ok, reindirizzo a PayPal
 */
$PaypalHelper->RedirectToPayPal ( $resArray["TOKEN"] );


