<?php
$itemUrl = $App->UrlUtility->getUrlItemByReverseIndex(0);
$thisItem = $App->create("ProductsRepository")->loadByUrl($itemUrl);
if (!$thisItem) {throw new \Box\Exceptions\NotFoundException();}

require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads('tavern');
$App->Lang->setActive('tavern');


$NextItem = $PrevItem = NULL;
$arMenu = $App->create('ProductsRepository')->load();

$n_items = count($arMenu);

foreach($arMenu as $k => $M):
    if($M->id == $thisItem->id):
        $PrevItem = $k > 0 ? $arMenu[($k-1)] : NULL;
        $NextItem = $k < $n_items-1 ? $arMenu[($k+1)] : NULL;
    endif;
endforeach;

$App->Page->title($thisItem->metaTitle);
$App->Page->description($thisItem->metaDescription);

//$App->Page->alternates($App->Lang->getAllAlternates());

$App->Page->addClass($App->Lang->getActive().' detail negative');

$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content">

        <section id="intro_section" class="center_width">
            <h1 class="page_title"><?php $App->Lang->echoT('nav_menu'); ?></h1>
        </section>
		<section id="menu_slider_section" class="center_width">
            <div id="menu_slider_container">

<?php 
$glutenfree = $thisItem->glutenfree ? '<span class="menu_info glutenfree">'.$App->Lang->returnT('glutenfree').'</span>' : '';
$vegan = $thisItem->vegan ? '<span class="menu_info vegan">'.$App->Lang->returnT('vegan').'</span>' : '';

echo '      <figure>
            '.( 
                strlen($thisItem->img1_M)
                ? '         <img src="'.$M->img1_M.'" srcset="'.$M->img1_M.' 1x, '.$M->img1_L.' 2x" alt="'.$M->title.'">'.PHP_EOL
                : ''
            ).'
            </figure>
			<h3 class="detail_title">'.$thisItem->title.'</h3>
            <p class="price">€ '.$App->Currency->print((float)$thisItem->getPrice()).'</p>
			<p class="detail_descr">'.$thisItem->description.'</p>
            '.(strlen($glutenfree.$vegan) ? '<p class="menu_info_wrapper">'.$glutenfree.$vegan.'</p>' : '').PHP_EOL;
?>
            </div>
		</section>

	</main><!-- #page_content -->
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>
</div>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>