<?php
require(HTML_INCLUDE_PATH.'/common.php');
$App->Lang->loadTrads('tavern');
$App->Lang->setActive('tavern');

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));

$App->Page->alternates($App->Lang->getAllAlternates());

$ProductCatsRepository = $App->create('ProductCatsRepository');
$thisSection = $ProductCatsRepository->loadById(5);  // carico la sezione Menu

$arMenuCats = $ProductCatsRepository->loadByParent($thisSection);

$ProductsRepository = $App->create('ProductsRepository');
$arMenu = $ProductsRepository->order("position", "ASC")->loadByCat($thisSection);

$n_items = count($arMenu);

$App->Page->addClass($App->Lang->getActive().' index negative');
$App->Page->addJs(<<<JAVASCRIPT

    $('#menu_gallery_slider').slick({
        infinite: false
        ,speed: 300
        ,slidesToShow: 1
        ,prevArrow: '<button type="button" data-role="none" class="nav prev on-black slick-prev">&laquo; {$App->Lang->returnT('nav_prev')}</button>'
        ,nextArrow: '<button type="button" data-role="none" class="nav next on-black slick-next">{$App->Lang->returnT('nav_next')} &raquo;</button>'
        ,dots: true
        ,arrows: true
    });

    var n = $('#menu_gallery_slider_container .slick-dots > li').length;
    $('#menu_gallery_slider_container .slick-dots > li').css('width',(100/n)+'%');


    // comparsa del dettaglio


    MenuSlider = $('#menu_slider');
    var popup_box = $('#menu_slider_section');
    var popup_box_close_btn = $('<a class="close_btn" href="#"><span class="sr-only">'+window.arTrads.close+'</span></a>')
                                            .prependTo(popup_box)
                                            .on('click',function(e){
                                                e.preventDefault();
                                                popup_box.fadeOut();
                                            });
    var idx = 0;
    var slick_opt = {
        infinite: false
        ,speed: 300
        ,slidesToShow: 1
        ,adaptiveHeight: true
        ,prevArrow: '<button type="button" data-role="none" class="nav prev on-black slick-prev">&laquo; {$App->Lang->returnT('nav_prev')}</button>'
        ,nextArrow: '<button type="button" data-role="none" class="nav next on-black slick-next">{$App->Lang->returnT('nav_next')} &raquo;</button>'
        ,dots: false
    };
    var inited = false;
    MenuSlider.on('init',function(){
        inited = true;
    }).on('afterChange',function(e,slick){
        var sc = MenuSlider.find('.slick-current');
        var p = {
            x   : (Math.floor(sc.width()/2)+10)     // piccoli aggiustamenti costanti
            ,y  : (Math.floor(sc.height()/2)-12)
        };
        popup_box_close_btn.css({
            '-webkit-transform': 'translate('+p.x+'px,-'+p.y+'px)'
            ,'-moz-transform': 'translate('+p.x+'px,-'+p.y+'px)'
            ,'transform': 'translate('+p.x+'px,-'+p.y+'px)'
        })
    });
    $(document).on('click','#menu_cat_container .info_menu',function(e){
        e.preventDefault();
        idx = this.dataset.slide_index;
        if(inited === false){ MenuSlider.slick(slick_opt); };
        if(!popup_box.is(':visible')){
            popup_box.fadeIn(function(){
                MenuSlider.slick('slickGoTo',idx,true);
            });
        } else {
            MenuSlider.slick('slickGoTo',idx,true);
        };
    });

JAVASCRIPT
);
$App->Page->open();

?>
<div id="main_wrapper">

	<?php require HTML_INCLUDE_PATH.'/page_header.php'; ?>
	<main id="page_content">
        <nav class="page_menu center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
            <a class="fluid_scroll" data-stop_before="50" href="#ordering_section"><?php $App->Lang->echoT('nav_ordering'); ?></a>
            <a class="fluid_scroll" data-stop_before="50" href="#menu_section"><?php $App->Lang->echoT('nav_menu'); ?></a>
            <a class="fluid_scroll" data-stop_before="50" href="#topic_faqs_section"><?php $App->Lang->echoT('nav_faqs'); ?></a>
        </nav>

        <section id="intro_section" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
    		<h1 class="page_title center_width"><?php $App->Lang->echoT('page_title'); ?></h1>
    		<p class="intro_text center_width"><?php $App->Lang->echoT('page_intro'); ?></p>
            <figure>
                <img class="picture" src="/imgs/content/tavern/img-header.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                <img class="shape" src="/imgs/content/tavern-icon.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
            </figure>
        </section>

        <section id="ordering_section">
            <h3 class="title format_title mode3 center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('nav_ordering'); ?></h3>

            <?php require HTML_INCLUDE_PATH.'/order_info_widget.php' ?>
            
        </section>

		<section id="menu_slider_section">
			<h3 class="title format_title mode3" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('nav_menu'); ?></h3>
			<div id="menu_slider_container" class="slider_container" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<ul class="reset slider" id="menu_slider">
<?php
// array in cui associo l'id del prodotto all'indice nello slideshow successivo
if($arMenu):
    $arSlideIndexes = array(); 
    $slide_index = 0;
	foreach($arMenu as $k => $M):
        if($M->showDetail()):
            $glutenfree = $M->glutenfree ? '<span class="menu_info glutenfree">'.$App->Lang->returnT('glutenfree').'</span>' : '';
            $vegan = $M->vegan ? '<span class="menu_info vegan">'.$App->Lang->returnT('vegan').'</span>' : '';
            $arSlideIndexes[$M->id] = $slide_index++;
    		echo '		<li id="detail_'.$M->id.'">
                            <figure>
                            '.( 
                                $M->img1
                                ? '         <img src="'.$M->img1_M.'" srcset="'.$M->img1_M.' 1x, '.$M->img1_L.' 2x" alt="'.$M->title.'">'.PHP_EOL
                                : ''
                            ).'
                            </figure>
    						<h3 class="detail_title">'.$M->title.'</h3>
                            <p class="price">'.$M->getPrice().'</p>
    						<p class="detail_descr">'.$M->description.'</p>
                            '.(strlen($glutenfree.$vegan) ? '<p class="menu_info_wrapper">'.$glutenfree.$vegan.'</p>' : '').'
    					</li>'.PHP_EOL;
        endif;
	endforeach;
endif;
?>

				</ul>
			</div>
		</section>

        <!-- MENU -->
		<section id="menu_section" class="center_width">
			<h3 class="title format_title mode3" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('nav_menu'); ?></h3>
            <div id="menu_cat_container" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
<?php
if($arMenuCats && $arMenu):
    foreach($arMenuCats as $C):
        $arMenuByCat = array_filter($arMenu,function($v) use ($C){  // raggruppo le voci di menu di questa categoria in un unico array
            return $v->catId == $C->id;
        });
        if(is_array($arMenuByCat) && count($arMenuByCat) > 0):
            echo '      <div id="menu_cat_'.$C->id.'" class="menu_cat">
                            <h3 class="menu_cat_title">'.$C->title.'</h3>
                            <table class="menu_list">'.PHP_EOL;
            foreach($arMenuByCat as $M):
                $glutenfree = $M->glutenfree ? '<span class="menu_info glutenfree" title="'.$App->Lang->returnT('glutenfree').'"><span class="sr-only">'.$App->Lang->returnT('glutenfree').'</span></span>' : '';
                $vegan = $M->vegan ? '<span class="menu_info vegan" title="'.$App->Lang->returnT('vegan').'"><span class="sr-only">'.$App->Lang->returnT('vegan').'</span></span>' : '';
                $alcohol = $M->alcohol ? '<span class="menu_info alcohol" title="'.$App->Lang->returnT('menu_alcohol').'"><span class="sr-only">'.$App->Lang->returnT('menu_alcohol').'</span></span>' : '';
                $subtitle = strlen($M->subtitle) ? '<small>'.$M->subtitle.'</small>' : '';

                $detail = $M->showDetail() ? '<a class="info_menu" data-href="'.$App->Lang->returnL('tavern_detail',array($M->pageUrl)).'" data-slide_index="'.$arSlideIndexes[$M->id].'" data-item="'.$M->id.'"><abbr title="'.$App->Lang->returnT('view_detail').'">'.$App->Lang->returnT('info_abbr').'</abbr></a>' : '';
                echo '          <tr data-id="'.$M->id.'"  data-code="'.$M->code.'" data-cat="'.$M->catId.'">
                                    <td class="menu'.(strlen($detail) ? ' has_detail' : '').'">
                                        '.$detail.$M->title.$glutenfree.$vegan.$alcohol.$subtitle.' 
                                    </td>
                                    <td class="price">'.$M->getPrice().'</td>
                                </tr>'.PHP_EOL;
            endforeach;
            echo '          </table>
                        </div>'.PHP_EOL;
        endif;
    endforeach;
endif;
?>                  
                <?php if(STORE_AVAILABLE) { ?>
                
                <div class="menu_cat">
                    <a class="btn yellow rounded" href="<?php $App->Lang->echoL('cart_detail'); ?>"><?php $App->Lang->echoT('nav_order_online'); ?></a>
                </div>
                
                <?php } ?>
            </div>
            <div class="legenda" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
                <!-- <a class="btn rounded red menu_btn" href="<?php $App->Lang->echoL('download_doc',array('osteria-ai-pioppi-menu.pdf')); ?>" target="_blank"><?php $App->Lang->echoT('download_menu'); ?></a> -->
                <h5 class="legenda_title"><?php $App->Lang->echoT('legenda'); ?></h5>
                <div class="legenda_text">
                    <span class="menu_info glutenfree"><?php $App->Lang->echoT('glutenfree'); ?></span>
                    <span class="menu_info vegan"><?php $App->Lang->echoT('vegan'); ?></span>
                    <span class="menu_info alcohol"><?php $App->Lang->echoT('menu_alcohol'); ?></span>
                </div>
                <p class="legenda_info"><?php $App->Lang->echoT('legenda_info',array('email' => $App->Config['company']['mail'][0],'phone' => $App->Config['company']['phone'][0])); ?></p>
            </div>

		</section>

        <!-- STREETVIEW -->
		<section id="menu_gallery_section" class="center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<div id="menu_gallery_slider_container" class="slider_container">
				<ul id="menu_gallery_slider" class="slider reset">
					<li><img data-lazy="/imgs/content/tavern/gallery/gallery-3.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-4.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-5.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-6.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-7.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-8.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-9.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-10.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-11.jpg" alt=""></li>
                    <li><img data-lazy="/imgs/content/tavern/gallery/gallery-12.jpg" alt=""></li>
				</ul>
			</div>
		</section>

<?php 
	  # FAQS
		$faq_cat_id = 1; // id della categoria parco
	  if($App->faqs['faqs'] && isset($App->faqs['faqs'][$faq_cat_id])): ?>

        <!-- FAQS -->
<section id="topic_faqs_section" class="center_width">
	<h3 class="format_title mode3" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('faqs_title'); ?></h3>
	<div id="faqs_slider_container" class="slider_container" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
	<?php 

		echo '		<ul id="faqs_slider" class="reset slider">'.PHP_EOL;
		foreach($App->faqs['faqs'][$faq_cat_id] as $F):
				echo '		<li>
								<h5 class="question">'.$F['title'].'</h5>
								<p class="answer">'.$F['descr'].'</p>
							</li>'.PHP_EOL;
		endforeach;
		echo '		</ul>'.PHP_EOL;
	?>
	</div>
	<a class="view_all" href="<?php $App->Lang->echoL('hours_info'); ?>#faqs_section"><?php $App->Lang->echoT('view_all'); ?></a>
</section>

<?php endif; ?>

	</main><!-- #page_content -->
	<?php require HTML_INCLUDE_PATH.'/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>