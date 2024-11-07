<?php
/**
 * ---------------------------------------------------
 * 
 * SELEZIONE MENU
 * 
 * ---------------------------------------------------
 */


/**
 * Errori di validazione, se presenti.
 * Memorizzati in sessione da index-submit.php 
 */
$errors = !empty($_SESSION['cart_errors']) ? $_SESSION['cart_errors'] : [];


/**
 * Contatto api.
 * L'api ritorna l'html del carrello, con eventuali errori di validazione
 * (inoltrati da sessione locale, v. index-submit.php).
 * Inoltre, se non è presente un carrello attivo, viene creato dalle API un nuovo carrello
 * e ne viene restituito l'id.
 * Se il codice di risposta non è ok, verrà lanciata
 * un eccezione di tipo \GuzzleHttp\Exception\ClientException, che lasciamo propagare.
 */
$response = $App->apiClient->get('api/cart' , [
    'query' => [
        'locale' => $App->Lang->lgSuff,
        'submit_url' => $App->Lang->returnL('cart_index_submit'),
        // inoltra errori di validazione perché vengano visualizzati
        'previous-errors' => $errors,
        'cart_id' => $_SESSION['cart_id'] ?? null,
    ],
])->getBody();
$response = json_decode($response, true);


/**
 * Memorizzo l'id del carrello in sessione per chiamate successive
 */
$_SESSION['cart_id'] = $response['cart_id'];


/**
 * Svuota errori di validazione
 */
unset($_SESSION['cart_errors']);

/**
 * Pagina riepilogo carrello
 */
require(HTML_INCLUDE_PATH.'/common.php');
$App->Lang->setActive("cart_index");
$App->Lang->loadTrads("products_global,cart_global,cart_index");

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));
$App->Page->alternates($App->Lang->getAllAlternates());
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
echo $response['html'];
?>
           </div>
        </div>
    </div> <!-- refresh -->
	</main><!-- #page_content -->
	<?php require HTML_INCLUDE_PATH.'/page_footer.php'; ?>

</div> <!-- #main_wrapper -->

<?php
$App->Page->close(); // chiude body e html
