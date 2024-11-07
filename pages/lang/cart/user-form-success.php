<?php

/**
 * ---------------------------------------------------
 * 
 * RIENTRO DA PAGAMENTO
 * 
 * ---------------------------------------------------
 */

/**
 * Parametri
 */
$orderCode = $_GET['order_code'] ?? null;
$orderHash = $_GET['hash'] ?? null;


/**
 * Verifico parametri in ingresso
 */
if(
    empty($orderCode)
    || empty($orderHash)
){
    // qui naturalmente va gestito in maniera migliore l'output
    http_response_code(400);
    exit('Bad Request');
}


/**
 * Verifico che il codice ordine e l'hash siano quelli memorizzati in sessione.
 * La sessione è stata impostata in user-form-submit.php.s
 */
if(
    $orderCode !== $_SESSION['order_code']
    || $orderHash !== $_SESSION['order_hash']
){
    // qui naturalmente va gestito in maniera migliore l'output
    http_response_code(400);
    exit('Bad Request');
}


/**
 * Verifico ordine tramite api
 */
try {

    $cartId = $_SESSION['cart_id'];

    $response = $App->apiClient->get('api/cart/'.$cartId.'/'.$orderCode.'/payment' , [
        'query' => [
            'order_hash' => $orderHash,
        ],
    ]);

    $response = json_decode($response->getBody()->getContents());

} catch(\GuzzleHttp\Exception\ClientException $e) {
   

    $response = $e->getResponse();


    /**
     * Gestiamo solo errori di validazione,
     * altrimenti lasciamo propagare l'eccezione.
     * Se l'ordine non è valido, non avremmo comunque uno stato di success.
     */
    switch($response->getStatusCode()) {

        case 422:
            
            $response = json_decode($response->getBody()->getContents(), true);

            /**
             * Memorizzo errori di validazione in sessione e old input
             */
            $_SESSION['cart_errors'] = $response['message'];
            
            $App->redirect($App->Lang->returnL('cart_form').'#cart-errors');

            break;

        default:
            throw $e;

    }

}


/**
 * Ulteriore verifica, l'id del carrello deve essere lo stesso
 */
if(
    $response->cart_id !== $_SESSION['cart_id']
){
    // qui naturalmente va gestito in maniera migliore l'output
    http_response_code(400);
    exit('Bad Request');
}


/**
 * Se l'ordine non è completato, esco
 */
if(
    $response->order->status !== 'paid'
){
    http_response_code(400);
    exit('Pagamento con completato');
}


/**
 * Svuoto tutte le sessioni
 */
unset($_SESSION['cart_errors']);
unset($_SESSION['cart_form_old']);
unset($_SESSION['order_id']);
unset($_SESSION['order_code']);
unset($_SESSION['order_hash']);
unset($_SESSION['cart_id']);

/**
 * Ok, reindirizzo alla thank you page
 */
$App->redirect($App->Lang->returnL('cart_thankyou').'?order_code='.$orderCode.'&hash='.$orderHash);