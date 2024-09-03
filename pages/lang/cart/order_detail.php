<?php
/**
 * Pagina di consultazione dettaglio ordine
 */
$App->Lang->loadTrads("cart_global,cart_mail,cart_completed,user");


/**
 * Recupero ordine da codice nella query
 */
$token = !empty($_GET["t"]) ? $_GET["t"] : null;
$orderCode = !empty($_GET["c"]) ? $_GET["c"] : null;
$Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'ordine_code', $orderCode, false, true);
if($Order->token != $token) {
    throw new Exception("Link dettaglio ordine non valido");
}


/**
 * Recupero dettaglio html da webservice
 */
$client = $App->create("WsClient");
$response = $client->request('GET', 'api/orders/'.$Order->ordine_code.'/html/'.$App->Lang->lgSuff);
$orderDetailWidget = $response->getBody();  

/**
 * Html
 */
require(ASSETS_PATH.'/html/common.php');
$App->Page->title($App->Lang->returnT("dati_ordine").' '.$Order->getordine_code());
$App->Page->includeCss(BASE_URL.'/css/build/cart.css');
$App->Page->addClass('page_order_detail negative');
$App->Page->open();
?>
<div id="main_wrapper">
<?php
require ASSETS_PATH.'/html/page_header.php';
?>

<main id="page_content">
    <div id="intro_section" class="content_section">
        <div class="center_width center">
            
            <header>
                <h1 class="page_title">
                    <?php echo $App->Lang->returnT("riepilogo_ordine") ?>
                </h1>
            </header>
            
        </div>
    </div>

    <div class="center_width s">
                
    <?php
        echo $orderDetailWidget;
    ?>

    </div>
</main>

<?php require ASSETS_PATH.'/html/page_footer.php'; ?>
</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>