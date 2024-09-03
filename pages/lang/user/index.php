<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads('user,user_index');
$App->Lang->setActive('user_area');

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));

$App->Page->alternates($App->Lang->getAllAlternates());

$App->Page->addClass($App->Lang->getActive().' index negative');
$App->Page->addJs(<<<JAVASCRIPT

JAVASCRIPT
);
$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content">

        <section id="intro_section" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
    		<h1 class="page_title center_width"><?php $App->Lang->echoT('page_title'); ?></h1>
    		<p class="intro_text center_width"><?php $App->Lang->echoT('page_intro'); ?></p>
        </section>

        <section id="search_section">
            <form class=""
            <h3 class="title format_title mode3 center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('nav_ordering'); ?></h3>
            <p class="center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('ordering_section_text'); ?></p>
        </section>

	</main><!-- #page_content -->
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>
</div>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>