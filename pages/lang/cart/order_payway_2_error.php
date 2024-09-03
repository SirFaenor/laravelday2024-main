<?php
/**
 * 
 * Rientro da payway in seguito al verificarsi di un errore
 * per problemi tecnici.
 * Se il pagamento sempolicemente non è stato approvato,
 * si torna comunque alla pagina notifyUrl, cioè order_payway_2.php
 * 
 */
$App->BrowserQueue->noSave();
$App->Lang->loadTrads('cart_global,errors,form');


/**
 * Recupero ordine (da parametro nell'url, è stato passato in fase di init pagamento in order_payway_1.php)
 * Lancia da solo eccezione se ordine non trovato
 */
$orderId = !empty($_GET['orderId']) ? $_GET['orderId'] : null;
$Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $orderId);


/**
 * Aggiorno pagamento
 */
$Order->cancelPayment();


/**
 * Alert errore
 */
$App->ErrorLogger->alert("Payway payment error");


/**
 * Fermo procedura e mostro messaggio errore
 */
$message = '<br>'.$App->Lang->returnT('error_form_generic', ["mail" => $App->Config["site"]["mail_orders"]]);
$App->create("cart_error", '', $message, $back = $App->Lang->returnL('cart_user_data'));
