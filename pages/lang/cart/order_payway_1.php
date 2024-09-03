<?php
/**
 * 
 * Script di reindirizzamento a payway per pagamento.
 * Contatta il webservice che a sua volta inoltra la richiesta
 * di pagamento a payway inoltrando qui l'url che verrà utilizzata
 * per reindirizzare l'utente al pagamento.
 * 
 */
$App->BrowserQueue->noSave();
$App->Lang->loadTrads("cart_global,errors,cart_help");


/**
 * Controllo situazione
 */
if($App->Cart->getTotalAmount() <= 0 || !$App->Cart->getorderID()):
    throw new Exception('Ordine non valido, dati mancanti.');
endif;


/**
 * Recupero ordine
 * Lancia da solo eccezione se ordine non trovato
 */
$Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $App->Cart->getorderID());


/**
 * Contatto il nostro webservice perchè avvii una richiesta
 * di pagamento inoltrando la richiesta al gateway.
 */
$notifyUrl = $App->Lang->returnL('cart_order_payway_2', [$App->Cart->getorderID()]);
$errorUrl = $App->Lang->returnL('cart_order_payway_2_error', [$App->Cart->getorderID()]);
$response = $App->create("WsClient")->request('POST', 'api/payway/init', [
    'form_params' => [
        'orderId' => $App->Cart->getorderID(),
        'notifyUrl' => $notifyUrl,
        'errorUrl' => $errorUrl,
    ]
]);
$response = json_decode($response->getBody());  


/**
 * Se la richiesta è andata a buon fine, la riposta
 * contiene:
 * - paymentID identificativo payway del pagamento
 * - redirectURL url a cui reindirizzare utente per pagamento.
 * Memorizzo le informazioni
 */
$Order->updatePayment([
    'PAYWAY_PAYMENT_ID' => $response->paymentID,
    'PAYWAY_PAYMENT_REDIRECT' => $response->redirectURL,
]);


/**
 * Reindirizzo al pagamento
 */
$App->redirect($response->redirectURL);


