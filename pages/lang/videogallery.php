<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads('videogallery');
$App->Lang->setActive('videogallery');

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));

$arVideos = $App->Da->getRecords(array(
	'model'		=> 'VIDEOS'
	,'cond'		=> 'AND XL.lang = '.$App->Lang->lgId.' HAVING published ORDER BY X.position ASC'
));

$App->Page->alternates($App->Lang->getAllAlternates());

$App->Page->addClass($App->Lang->getActive().' negative');
$App->Page->addJs(<<<JAVASCRIPT


JAVASCRIPT
);
$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content">
		<section id="videos_section" class="center_width">
			<h1 class="page_title" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('page_title'); ?></h1>
			<p data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('page_intro'); ?></p>

<?php if($arVideos): ?>
				<ul id="videos_list" class="reset" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
<?php 
foreach($arVideos as $V):
	echo '				<li>
							<a class="yt_link" href="'.$V['link'].'" target="_blank"><span class="sr-only">'.$App->Lang->returnT('play').'</span></a>
							<a class="fancybox_link" href="'.$V['link'].'" data-fancybox>
								<figure>
									<img src="'.$App->Lang->returnL('video_img',array($V['id'],'M',$V['img_1'])).'" srcset="'.$App->Lang->returnL('video_img',array($V['id'],'M',$V['img_1'])).' 1x,'.$App->Lang->returnL('video_img',array($V['id'],'M2x',$V['img_1'])).' 2x" alt="'.$App->Lang->returnT('alt_image').'">
									<figcaption><span>'.$App->Lang->returnT('play').'</span></figcaption>
								</figure>
								<h4>'.$V['title'].'</h4>
							</a>
						</li>'.PHP_EOL;
endforeach;
?>
				</ul>
<?php endif; ?>
		</section>

	</main><!-- #page_content -->
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>