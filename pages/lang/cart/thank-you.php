<?php
/**
 * ---------------------------------------------------
 * 
 * THANK YOU PAGE
 * 
 * La pagina può anche essere usata per linkare dettaglio
 * ordine.
 * 
 * ---------------------------------------------------
 */

/**
 * Parametri
 */
$orderCode = $_GET['order_code'] ?? null;
$orderHash = $_GET['hash'] ?? null;


/**
 * Verifico che l'ordine corrisponda a quello memorizzato in sessione.
 * La sessione è stata impostata in user-form-submit.php
 */
if(
    empty($orderCode)
    || empty($orderHash)
){
    // qui naturalmente va gestito in maniera migliore l'output
    http_response_code(400);
    exit('Bad Request');
}

try {

    $response = $App->apiClient->get('api/orders/'.$orderCode.'/thank-you' , [
        'query' => [
            'locale' => $App->Lang->lgSuff,
            'order_code' => $orderCode,
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
 * TYP  carrello
 */
require(HTML_INCLUDE_PATH.'/common.php');
$App->Lang->setActive("cart_thankyou");
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



