<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads("sitemap_user");

$Sitemap = new Sitemap();
$Sitemap->setDomain($App->Config['site']['url']);

require_once(DOCUMENT_ROOT."/include/php/sitemap_common.php");
require_once(DOCUMENT_ROOT."/include/php/sitemap_html.php");

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->alternates($App->Lang->getAllAlternates());
$App->Page->addClass('negative');
$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content" class="center_width s">

        <h1 class="format_title_2"><?php $App->Lang->echoT("page_title") ?></h1>
        <div class="page_text">
<?php
/**
 * Sitemap gestito in include/sitemap_common.php
 */
$Sitemap->printHtml();
?>

	    </div>
	</main>

	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>