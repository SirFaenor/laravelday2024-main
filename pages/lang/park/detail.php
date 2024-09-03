<?php

$itemUrl = $App->UrlUtility->getUrlItemByReverseIndex(0);
$thisItem = $App->Da->getSingleRecord(array(
	'model'		=> 'PARKMAP_ITEMS'
	,'cond'		=> 'AND XL.lang = '.$App->Lang->lgId.' AND XL.url_page = "'.$itemUrl.'" HAVING published'
));
if (!$thisItem) {throw new \Box\Exceptions\NotFoundException();}

require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads('park');
$App->Lang->setActive('park');


$NextItem = $PrevItem = NULL;
$arParkItems = $App->Da->getRecords(array(
	'model'		=> 'PARKMAP_ITEMS'
	,'cond'		=> 'AND XL.lang = '.$App->Lang->lgId.' HAVING published ORDER BY X.position ASC'
));

$n_items = count($arParkItems);

foreach($arParkItems as $k => $PI):
	if($PI['id'] == $thisItem['id']):
		$PrevItem = $k > 0 ? $arParkItems[($k-1)] : NULL;
		$NextItem = $k < $n_items-1 ? $arParkItems[($k+1)] : NULL;
	endif;
endforeach;


$App->Page->title($thisItem['title_page']);
$App->Page->description($thisItem['description_page']);

$App->Page->addClass($App->Lang->getActive().' detail');

$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content" class="center_width">
		<header class="content_header">
			<h1 class="page_title"><?php $App->Lang->echoT('page_title'); ?></h1>
			<figure class="content_header_bg">
				<picture>
					<source srcset="/imgs/content/park/poster-mobile.jpg" media="(max-width: 680px)">
					<img src="/imgs/content/park/poster.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				</picture>
			</figure>
		</header>
		
		<a class="fluid_scroll goto_btn" href="#parkitem_detail_section">
			<img src="/imgs/layout/arrow.svg" alt="<?php $App->Lang->echoT('go_to',array('object' => $App->Lang->returnT('site_sections'))); ?>">
		</a>

		<section id="parkitem_detail_section">
			<figure>
				<img src="<?php $App->Lang->echoL('park_item_img',array($thisItem['id'],'M',$thisItem['img_1'])); ?>" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
<?php 
if($PrevItem !== NULL):
	echo '		<a class="nav prev park_detail_link" href="'.$App->Lang->returnL('park_detail', array($PrevItem['url_page'])).'"><span class="sr-only">'.$App->Lang->returnT('nav_prev').'</span></a>'.PHP_EOL;
endif;
if($NextItem !== NULL):
	echo '		<a class="nav next park_detail_link" href="'.$App->Lang->returnL('park_detail', array($NextItem['url_page'])).'"><span class="sr-only">'.$App->Lang->returnT('nav_next').'</span></a>'.PHP_EOL;
endif;
?>
			<span class="n_items">
				<span class="item"><?php echo $thisItem['number']; ?></span>
				<span class="total"><?php echo $n_items; ?></span>
			</span>
			<h3 class="detail_title"><?php echo $thisItem['title']; ?></h3>
			<p class="detail_descr"><?php echo $thisItem['descr']; ?></p>
			<p class="detail_cat"><?php $App->Lang->echoT('suggested_age'); ?>: <?php echo $thisItem['title_cat']; ?></p>

		</section>

	</main><!-- #page_content -->
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>
</div>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>