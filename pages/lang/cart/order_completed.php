<?php

use Custom\Ecommerce\Order;

$App->Lang->loadTrads("cart_global,cart_mail,errors,cart_completed,user,form");

$dati_bonifico = '';

try {

    $id_ordine = $App->Cart->getorderID() 
                        ? $App->Cart->getorderID() 
                        : (array_key_exists('id_ord',$_GET) && (IS_DEVELOPER || IS_DEV_ENV) ? $App->FixNumber->fix($_GET['id_ord']) : $App->Cart->getorderID());

    // svuoto il carrello
    $App->Cart->emptyCart();
    $arCartItems = $arCartProducts = $arCartPacks = array();
    $nCartItems = $Discount = $totalAmount = 0;

    // carico ordine   
    $Ordine = new \Custom\Ecommerce\Order($App->Da,$App->Lang,'id',$id_ordine);

    $OrderUser  = $Ordine->getUserData();
    $Payment    = $Ordine->getPayment();
    $Expedition = $Ordine->getExpedition();
    $Discount   = $Ordine->getDiscount();
    $arOrderItems= $Ordine->getItems();
    $arOrderData = $Ordine->getOrderData();

} catch (Exception $e){    

    // disabilito segnalazione (altrimenti arriva errore se pagina viene ricaricata)
    //$App->ErrorLogger->alert($e->getMessage());
    $App->redirect($App->Lang->returnL('homepage'));

}

/**
 * HTML
 */
require_once(HTML_INCLUDE_PATH."/html/common.php");
$App->Page->title($App->Lang->returnT('meta_title'));
$App->Page->includeCss(BASE_URL.'/css/build/cart.css');
$App->Page->addClass('page_cart page_cart_completed negative');
$App->Page->open();
?>
<div id="main_wrapper">
<?php
require HTML_INCLUDE_PATH.'/page_header.php';
?>
<main id="page_content">
   
    <section id="intro_section">
        <h1 class="page_title center_width"><?php $App->Lang->echoT('ordine_confermato'); ?></h1>
        <p class="intro_text center_width s">
            <!-- <?php $App->Lang->echoT('page_text'); ?>
            <a class="uk-button uk-button-default" href="<?php $App->Lang->echoL('homepage'); ?>">
                <?php $App->Lang->echoT('vai_home'); ?>
            </a> -->
        </p>
        <ul class="order_steps reset">
            <li class="completed"><span><?php $App->Lang->echoT('order_steps_order_composition'); ?></span></li>
            <li class="completed"><span><?php $App->Lang->echoT('order_steps_insert_data'); ?></span></li>
            <li class="active"><span><?php $App->Lang->echoT('grazie'); ?>!</span></li>
        </ul>

        <div class="recap_highlight center_width s">
            <?php echo $App->Lang->echoT('recap_highlight', [
                'date' => (new DateTime($arOrderData["data2"]))->format('d/m/Y'),
                'time' => (new DateTime($arOrderData["data2"]))->format('H:i'),
                'code' => $arOrderData["ordine_code"],
                "expiration_minutes" => Order::EXPIRATION_TIME / 60, // il tempo originario è impostato in secondi
                "expiration_hours" => Order::EXPIRATION_TIME / 60 / 60,// il tempo originario è impostato in secondi
                ]) ?>
        </div>
    </section>

    <div class="content_section center_width recap_notes">
        <img src="<?php echo $App->create("qrEndpoint", $Ordine);?>" alt="Qr code">
    </div>
    
    <div class="content_section center_width recap_notes">
    <?php echo $App->Lang->echoT('recap_notes', [
        "expiration_hours" => Order::EXPIRATION_TIME / 60 / 60, // il tempo originario è impostato in secondi
        "refund_hours" => Order::REFUND_TIME,
    ]); ?>
    </div>


</main>

<?php require HTML_INCLUDE_PATH.'/page_footer.php'; ?>
</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html

