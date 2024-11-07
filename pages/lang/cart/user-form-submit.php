<?php
/**
 * ---------------------------------------------------
 * 
 * SUBMIT FORM UTENTE
 * 
 * ---------------------------------------------------
 */

try {
    // a questo punto dobbiamo avere un carrello attivo (v. cart/index.php)
    $cartId = $_SESSION['cart_id'];

    $response = $App->apiClient->post('api/cart/'.$cartId.'/order' , [
        'form_params' => [
            'locale' => $App->Lang->lgSuff,

            'email' => !empty($_POST['email']) ? $_POST['email'] : null, 
            'email_confirmation' => !empty($_POST['email_confirmation']) ? $_POST['email_confirmation'] : null,
            'accept_tos' => !empty($_POST['accept_tos']) ? $_POST['accept_tos'] : null,
            'accept_privacy' => !empty($_POST['accept_privacy']) ? $_POST['accept_privacy'] : null,
            
            // url a cui reindirizzare se l'operazione va a buon fine (nell'applicazione main)
            'success_url' => $App->Lang->returnL('cart_form_success'),

            // a questo punto dobbiamo avere un carrello attivo (v. cart/index.php)
            'cart_id' => $_SESSION['cart_id'],
        ],
    ]);

    $response = json_decode($response->getBody()->getContents());

} catch(\GuzzleHttp\Exception\ClientException $e) {
   
    $response = $e->getResponse();

    /**
     * Gestiamo solo errori di validazione,
     * altrimenti lasciamo propagare l'eccezione.
     */
    switch($response->getStatusCode()) {

        case 422:
            
            $response = json_decode($response->getBody()->getContents(), true);

            /**
             * Memorizzo errori di validazione in sessione e old input
             */
            $_SESSION['cart_errors'] = $response['message'];
            $_SESSION['cart_form_old'] = $response['old'];
        
            $App->redirect($App->Lang->returnL('cart_form').'#cart-errors');

            break;

        default:
            throw $e;

    }

}


/**
 * Memorizzo  codice ordine e hash in sessione
 */
$_SESSION['order_code'] = $response->order_code;
$_SESSION['order_hash'] = $response->hash;


/**
 * Reindirizza all'url per il pagamento
 * (ottenuta dal webservice)
 */
$App->redirect($response->redirect_url);


