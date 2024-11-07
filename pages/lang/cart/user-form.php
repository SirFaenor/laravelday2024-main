<?php
/**
 * ---------------------------------------------------
 * 
 * FORM DATI UTENTE
 * 
 * ---------------------------------------------------
 */


/**
 * Errori di validazione, se presenti.
 * Memorizzati in sessione da user-form-submit.php 
 */
$errors = !empty($_SESSION['cart_errors']) ? $_SESSION['cart_errors'] : [];


/**
 * Recupero html.
 * L'api ritorna il form per l'inserimento dei dati utente, con eventuali
 * errori di validazione (inoltrati da sessione locale, v. user-form-submit.php).
 * Se il codice di risposta non è ok, verrà lanciata
 * un eccezione di tipo \GuzzleHttp\Exception\ClientException.
 *
 */
try {
    
    // a questo punto dobbiamo avere un carrello attivo (v. cart/index.php)
    $cartId = $_SESSION['cart_id'];

    $response = $App->apiClient->get('api/cart/'.$cartId.'/form' , [
        'query' => [
            'locale' => $App->Lang->lgSuff,
            'submit_url' => $App->Lang->returnL('cart_form_submit'),
   
            // inoltra errori di validazione perché vengano visualizzati
            'submit-errors' => $errors,

            // inoltro valori old per precompilazione form (v. user-form-submit.php)
            'old' => $_SESSION['cart_form_old'] ?? null,


        ],
    ])->getBody();
    $response = json_decode($response);

} catch(\GuzzleHttp\Exception\ClientException $e) {
        
        $response = $e->getResponse();
        
        /**
        * Gestiamo solo errori di validazione,
        * altrimenti lasciamo propagare l'eccezione.
        */
        switch($response->getStatusCode()) {
        
            case 422:
                $_SESSION['cart_errors'] = json_decode($response->getBody()->getContents(), true)['message'];
                
                // redirect a inizio procedura
                $App->redirect($App->Lang->returnL('cart_index').'#cart-errors');
        
                break;

            // la sessione è scaduta o il carrello non è più disponibile (es. procedura completata)
            case 404 :
                
                // redirect a inizio procedura
                $App->redirect($App->Lang->returnL('cart_index'));

            default:
                throw $e;
        
        }
}

/**
 * Svuota errori di validazione precedenti
 */
unset($_SESSION['cart_errors']);
unset($_SESSION['cart_form_old']);

/**
 * Pagina riepilogo carrello
 */
require(HTML_INCLUDE_PATH.'/common.php');
$App->Lang->setActive("cart_form");
$App->Lang->loadTrads("products_global,cart_global,cart_index");

$App->Page->title($response->meta_title);
$App->Page->addClass('page_cart page_cart_index negative order_available_1');
$App->Page->open();
?>
<div id="main_wrapper">

<?php require HTML_INCLUDE_PATH.'/page_header.php'; ?>
<main id="page_content">
    <section id="intro_section" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
    </section>
    <div class="content_section">
        <div id="cart_wrapper" class="clearfix">
<?php
echo $response->html;
?>
           </div>
        </div>
    </div> <!-- refresh -->
	</main><!-- #page_content -->
	<?php require HTML_INCLUDE_PATH.'/page_footer.php'; ?>

</div> <!-- #main_wrapper -->

<?php
$App->Page->close(); // chiude body e html
