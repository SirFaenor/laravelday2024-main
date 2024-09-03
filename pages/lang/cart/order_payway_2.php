<?php
/**
 * 
 * Rientro da payway dopo il pagamento.
 * Rientro qui anche se il pagamento non è stato approvato.
 * (si va all'url di errore solo se c'è stato problema tecnico)
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
 * verifico transazione attraverso una chiamata alle api.
 * risposta in json che poi codifico in array
 */
$verifyUrl = 'api/payway/verify/'.$Order->getPayment()['PAYWAY_PAYMENT_ID'].'/'.$Order->id;
$response = $App->create("WsClient")->get($verifyUrl);
$response = json_decode((string)$response->getBody(), true);


/**
 * Se c'è stato qualche problema con il pagamento 
 * (si arriva qui anche in caso di pagamenti declinati/annullati), 
 * informo l'utente 
 */
if($response['error'] == true || $response['rc'] !== 'IGFS_000') {

    $paymentData = [
        'PAYWAY_PAYMENT_RAW' => json_encode($response)
    ];

    // comportametni dipendenti da specifici codici di errore
    switch($response['rc']) :

        // pagamento cancellato da utente
        case 'IGFS_20090' : 

            $Order->cancelPayment($paymentData);

            break;

        // negli altri casi intanto imposto pagamento fallito
        default : 

            $Order->failPayment($paymentData);

            break;

    endswitch;

    // recupero messaggio di errore 
    $message = $response['errorDesc'];
    $App->create("cart_error", '', $message, $back = $App->Lang->returnL('cart_user_data'));

}


/**
 * Aggiorno il pagamento (confermandolo) con le informazioni sulla transazione
 * e confermo l'ordine
 */
$Order->confirm();
$Order->confirmPayment([
    'PAYWAY_PAYMENT_RAW' => json_encode($response)
]);


/**
 * Tutto ok, reindirizzo a pagina di conferma ordine
 */
$App->redirect($App->Lang->returnL("cart_order_completed"));
