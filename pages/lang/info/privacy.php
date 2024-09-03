<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads("privacy");

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
            <?php $App->Lang->echoT("page_text",$legal_data); ?>
	    </div>
	</main>

	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>