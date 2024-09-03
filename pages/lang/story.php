<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads('story');
$App->Lang->setActive('story');

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));

$App->Page->alternates($App->Lang->getAllAlternates());

$MyVideo = $App->Da->getSingleRecord(array(
	'model'		=> 'VIDEOS'
	,'cond'		=> 'AND XL.lang = :lang_id AND X.id = :video_id HAVING published'
	,'params'	=> array('lang_id' => $App->Lang->lgId,'video_id' => 3)
));

$App->Page->addJs(<<<JAVASCRIPT

// faccio vedere il nonno sopra il testo
var GrandpaPortrait = $('<div class="portrait cloned"></div>').hide();
GrandpaPortrait.append($('#timetravel_section .portrait .photo').clone());
GrandpaPortrait.insertAfter($('#timetravel_section .portrait'));
setTimeout(function(){
	GrandpaPortrait.fadeIn();
},200);

JAVASCRIPT
);

$App->Page->addClass($App->Lang->getActive().' negative');
$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content">
		<section id="timetravel_section" class="center_width">
			<h1 class="page_title" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('page_title'); ?></h1>
			<p class="intro" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('timetravel_intro'); ?></p>
			<p class="signature" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<span>Bruno Ferrin</span>
				<img src="/imgs/content/story/firma-bruno.jpg" alt="<?php $App->Lang->echoT('signature'); ?>">
			</p>
			<figure class="portrait clearfix" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="shape shape1" src="/imgs/content/story/forma-1.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="shape shape2" src="/imgs/content/story/forma-2.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="photo" src="/imgs/content/story/header-bruno@2x.png" alt="Bruno Ferrin">
			</figure>
		</section>

		<section id="mission_section" class="center_width center">
			<p class="intro" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('mission_section_intro'); ?></p>
<?php if($MyVideo): ?>
			<div class="video_figure" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<a class="video_link yt_link" href="<?php echo $MyVideo['link']; ?>" target="_blank"><span class="sr-only"><?php $App->Lang->echoT('play'); ?></span></a>
				<a class="video_link fancybox_link" href="<?php echo $MyVideo['link']; ?>" data-fancybox>
					<figure>
						<img src="<?php $App->Lang->echoL('video_img',array($MyVideo['id'],'M',$MyVideo['img_1'])); ?>" srcset="<?php $App->Lang->echoL('video_img',array($MyVideo['id'],'M',$MyVideo['img_1'])); ?> 1x,<?php $App->Lang->echoL('video_img',array($MyVideo['id'],'M2x',$MyVideo['img_1'])); ?> 2x" alt="<?php $App->Lang->echoT('alt_image'); ?>">
						<figcaption><span><?php $App->Lang->echoT('play'); ?></span></figcaption>
					</figure>
				</a>
			</div>
<?php endif; ?>
		</section>

		<section id="story_1" class="story_section center_width mode1 clearfix">
			<div class="text" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('story_1_text'); ?></div>
			<figure class="img1" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img1.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="shape" src="/imgs/content/story/forma-3.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
			<figure class="img2" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img2.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="shape" src="/imgs/content/story/forma-4.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
		</section>

		<section id="story_2" class="story_section mode2 center_width clearfix">
			<div class="text" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('story_2_text'); ?></div>
			<figure class="img1" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img3.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
			<figure class="img2" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img4.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
		</section>

		<section id="story_3" class="story_section mode1 center_width clearfix">
			<div class="text" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('story_3_text'); ?></div>
			<figure class="img1" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img5.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="shape" src="/imgs/content/story/forma-5.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
			<figure class="img2" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img6.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="shape shape1" src="/imgs/content/story/forma-3.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="shape shape2" src="/imgs/content/story/forma-6.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
		</section>

		<section id="story_4" class="story_section mode2 center_width clearfix">
			<div class="text" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('story_4_text'); ?></div>
			<figure class="img1" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img8.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="shape" src="/imgs/content/story/forma-7.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
			<figure class="img2" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img7.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
		</section>

		<section id="story_5" class="story_section mode1 center_width clearfix">
			<div class="text" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('story_5_text'); ?></div>
			<figure class="img1" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img9.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				<img class="shape" src="/imgs/content/story/forma-8.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
			<figure class="img2" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<img class="picture" src="/imgs/content/story/img10.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
			<p class="signature" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<span>Bruno Ferrin</span>
				<img src="/imgs/content/story/firma-bruno.jpg" alt="<?php $App->Lang->echoT('signature'); ?>">
			</p>
		</section>

	</main><!-- #page_content -->
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>