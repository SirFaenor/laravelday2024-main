<?php
/**
 * ---------------------------------------------------
 * 
 * SUBMIT SELEZIONE MENU
 * 
 * ---------------------------------------------------
 */

try {

    // a questo punto dobbiamo avere un carrello attivo (v. cart/index.php)
    $cartId = $_SESSION['cart_id'];

    $response = $App->apiClient->put('api/cart/'.$cartId , [
        'form_params' => [
            'locale' => $App->Lang->lgSuff,
            'quantity' => !empty($_POST['quantity']) ? $_POST['quantity'] : [],
            // url a cui reindirizzare se l'operazione va a buon fine
            'redirect_url' => $App->Lang->returnL('cart_form'),

        ],
    ]);
} catch(\GuzzleHttp\Exception\ClientException $e) {
   
    $response = $e->getResponse();

    /**
     * Gestiamo solo errori di validazione,
     * altrimenti lasciamo propagare l'eccezione
     */
    switch($response->getStatusCode()) {

        case 422:
            $_SESSION['cart_errors'] = json_decode($response->getBody()->getContents(), true)['message'];
        
            $App->redirect($App->Lang->returnL('cart_index').'#cart-errors');

            break;

        default:
            throw $e;

    }

}


/**
 * Reindirizza alla pagina richiesta
 */
$response = json_decode($response->getBody()->getContents());
$App->redirect($response->redirect_url);


/**
 * Punti di conoscenza dell'app principale
 * 
 * - $App->Lang->lgSuff: suffisso lingua attiva
 * - accesso a $_POST
 * - $App->Lang->returnL('cart_form'): ritorna url form
 * - impostazione sessione
 * - $App->redirect(): reindirizzamento
 */

