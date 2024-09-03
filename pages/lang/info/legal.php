<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads("legal_info");

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->alternates($App->Lang->getAllAlternates());
$App->Page->addClass('negative');
$App->Page->open();

?>
<div id="main_wrapper">

    <?php require ASSETS_PATH.'/html/page_header.php'; ?>
    <main id="page_content" class="center_width s">
        <h1 class="format_title mode3"><?php $App->Lang->echoT("page_title") ?></h1>
        <div class="page_text">
            <h3 class="format_title_3"><?php echo $schema[0]['legalName']; ?></h3>
            <p>
                <?php echo 
                    $schema[0]['address']['streetAddress'].' <br>'.
                    $schema[0]['address']['postalCode'] .' '.$schema[0]['address']['addressLocality'].' ('.$schema[0]['address']['addressRegion'].') <br>'.
                    $schema[0]['address']['addressCountry'] ; 
                ?>
            </p>
            <p itemprop="contactPoint" itemscope itemtype="https://schema.org/ContactPoint">
                <?php echo 
                    '<strong>'.$App->Lang->returnT('label_telefono').'</strong>: '.$schema[0]['ContactPoint']['telephone'] .' <br>'.
                    '<strong>'.$App->Lang->returnT('label_email').'</strong>:  '.$schema[0]['ContactPoint']['email']
                    ;
                ?>
            </p>
            <p>
                <?php echo 
                    '<strong>'.$App->Lang->returnT('label_piva').'</strong>: '.$schema[0]['vatID']
                    ; ?>
            </p>
        </div>
    </main>

    <?php require ASSETS_PATH.'/html/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>