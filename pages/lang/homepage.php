<?php
require(HTML_INCLUDE_PATH.'/common.php');
$App->Lang->loadTrads('homepage');
$App->Lang->setActive('homepage');

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));

$App->Page->alternates($App->Lang->getAllAlternates());

$App->Page->addClass($App->Lang->getActive());
$App->Page->open();

?>
<div id="main_wrapper">

	<?php require HTML_INCLUDE_PATH.'/page_header.php'; ?>
	<main id="page_content">
		<header class="content_header" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<h1 class="page_title"><?php $App->Lang->echoT('page_title'); ?></h1>
			<div id="home_video" class="video_wrapper">
				<div class="video_container">
					<video src="/videos/ai_pioppi_osteria-parco-giochi.mp4" autoplay muted loop></video>
				</div>
			</div>
		</header>
		
		<div class="page_intro center_width s" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<!-- <p><?php $App->Lang->echoT('page_intro'); ?></p> -->
		</div>
		<a class="fluid_scroll goto_btn" href="#links_list" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<img src="/imgs/layout/arrow.svg" alt="<?php $App->Lang->echoT('go_to',array('object' => $App->Lang->returnT('site_sections'))); ?>">
		</a>

		<ul id="links_list" class="reset">
			<li class="tavern" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<a href="<?php $App->Lang->echoL('tavern'); ?>">
					<picture class="poster">
		                <source srcset="/imgs/content/tavern-poster-mobile.jpg" media="(max-width: 680px)">
						<img src="/imgs/content/tavern-poster.jpg" srcset="/imgs/content/tavern-poster.jpg 1x,/imgs/content/tavern-poster@2x.jpg 2x" alt="<?php $App->Lang->echoT('alt_image'); ?>">
					</picture>
					<img class="icon" src="/imgs/content/tavern-icon.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
					<h3 class="format_title mode2"><?php $App->Lang->echoT('tavern_title'); ?></h3>
				</a>
			</li>
			<li class="hours_info" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<a href="<?php $App->Lang->echoL('hours_info'); ?>">
					<picture class="poster">
		                <source srcset="/imgs/content/hours_info-poster-mobile.jpg" media="(max-width: 680px)">
						<img src="/imgs/content/hours_info-poster-mobile.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
					</picture>
					<img class="icon" src="/imgs/content/hours_info-icon.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
					<h3 class="format_title mode2"><?php $App->Lang->echoT('hours_info_title'); ?></h3>
				</a>
			</li>
			<li class="park" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<a href="<?php $App->Lang->echoL('park'); ?>">
					<picture class="poster">
		                <source srcset="/imgs/content/park-poster-mobile.jpg" media="(max-width: 680px)">
						<img src="/imgs/content/park-poster.jpg" srcset="/imgs/content/park-poster.jpg 1x,/imgs/content/park-poster@2x.jpg 2x" alt="<?php $App->Lang->echoT('alt_image'); ?>">
					</picture>
					<img class="icon" src="/imgs/content/park-icon.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
					<h3 class="format_title mode2"><?php $App->Lang->echoT('park_title'); ?></h3>
				</a>
			</li>
		</ul>

	</main><!-- #page_content -->
	<?php require HTML_INCLUDE_PATH.'/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>