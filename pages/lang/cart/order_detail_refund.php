<?php
/**
 * Pagina di consultazione dettaglio ordine
 */

use Custom\Ecommerce\Order;

$App->Lang->loadTrads("cart_global,cart_mail,cart_completed,user");


/**
 * Recupero ordine da codice nella query
 */
$token = !empty($_GET["t"]) ? $_GET["t"] : null;
$orderCode = !empty($_GET["c"]) ? $_GET["c"] : null;
$Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'ordine_code', $orderCode, false, true);
if($Order->token !== $token) {
    throw new Exception("Link dettaglio ordine (rimborso) non valido");
}


/**
 * COntrollo ordine
 */
$linkBack = $App->Lang->returnL("order_detail").'?'.http_build_query([
    "t" => $Order->token,
    "c" => $Order->ordine_code,
]);



/**
 * Submit
 */
if(!empty($_POST["submit"])) {

   
    /**
     * Controllo richiesta
     */
    if(
        empty($_SESSION["submit"])
        || $_SESSION["submit"]!== $_POST["submit"]
    ) {
        exit("Bad request");
    }


    /**
     * Richiedo rimborso.
     * Internamente usa webservice, che imposta i dati necessari e invia eventuali mail di notificje
     * (se fallisce chiamata o ordine non è idoneo lancia errore)
     * @todo identificare eccezione di errore e restituire messaggio valido per utente
     */
    $Order->requestRefund();


    /**
     * Messaggio
     */
    $message = $App->Lang->returnT("order_refund_confirm_message", ["ore" => Order::REFUND_TIME]);

}


/**
 * Html
 */
require(HTML_INCLUDE_PATH.'/common.php');
$App->Page->title($App->Lang->returnT("dati_ordine").' '.$Order->getordine_code());
$App->Page->includeCss(BASE_URL.'/css/build/cart.css');
$App->Page->addClass('page_order_detail negative');
$App->Page->open();
?>
<div id="main_wrapper">
<?php
require HTML_INCLUDE_PATH.'/page_header.php';
?>

<main id="page_content">
    <div id="intro_section" class="content_section">
        <div class="center_width center">
            
            <header>
                <h1 class="page_title">
                    <?php echo $App->Lang->returnT("richiedi_rimborso") ?>
                </h1>
            </header>

            
        </div>
    </div>

    <div class="center_width s"  >
        <div style="padding: 5vh 0; text-align: center;">

<?php
/**
 * Richiesta conferma
 */
if(empty($_POST["submit"])) :
    $_SESSION['submit'] = hash("sha512", time().uniqid());
?>

        <p>
            <?php $App->Lang->echoT("order_refund_confirm", ["ore" => Order::REFUND_TIME]); ?>
            <br><br>
            <form action="<?php echo $_SERVER["REQUEST_URI"] ?>" method="post">
                
                <input type="hidden" name="submit" value="<?php echo $_SESSION['submit'] ?>">
                <button class="btn rounded yellow" type=submit>Conferma</button>

            </form>
        </p>
<?php
else :
?>
    <p><?php echo $message ?></p>
    <p>
        <a href="<?php echo $linkBack ?>">&lt; <?php echo $App->Lang->returnT("back") ?></a>
    </p>
<?php
endif;
?>  
        </div>
    </div>

</main>

<?php require HTML_INCLUDE_PATH.'/page_footer.php'; ?>
</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>