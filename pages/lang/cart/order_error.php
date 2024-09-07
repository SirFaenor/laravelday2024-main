<?php
/**
 * Pagina di errore nel processamento di un ordine.
 * Viene chiamata da sequenza cart_error registrata in container.php.
 * Usata anche in caso di annullamento manuale dell'ordine, con messaggi adeguati.
 * 
 * Viene usata anche direttamente come $errorURL passato alle api paypal,
 * se l'errore avviene nel flow paypal esterno al sito.
 */
$App->Lang->loadTrads("products_global,cart_help");


/**
 * Pulizia dati in corso
 */
$App->Cart->paymentHits = 0;
$App->Cart->paymentTry = '';


/**
 * Verifico title e message
 * (non ci sono se vengo ad es. reindirizzato qui direttamente da paypal)
 */
$title = isset($title) ? $title : $App->Lang->returnT("error_generic_title");
$message = isset($message) ? $message : $App->Lang->returnT("error_generic_text");



/**
 * link indietro
 */
$back = isset($back) ? $back : 'javascript:history.back();';
$backLabel = isset($backLabel) ? $backLabel : 'back';


/**
 * Html
 */
require(HTML_INCLUDE_PATH.'/common.php');
$App->Page->title($title);
$App->Page->includeCss(BASE_URL.'/css/pages/cart_base.css');
$App->Page->addClass('page_cart page_cart_error negative');
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
                    <?php echo $title ?>
                </h1>
            </header>
            <div class="intro_section_text center">
                <p>
                    <br>
                    <?php echo $message; ?>
                </p>
                <p>
                    <a id="historyback" href="<?php echo $back ?>">&laquo; <?php echo $backLabel; ?></a>
                    / <a href="<?php echo BASE_URL ?>/">home</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require HTML_INCLUDE_PATH.'/page_footer.php'; ?>
</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>