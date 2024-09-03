<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads('park');
$App->Lang->setActive('park');

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));

$App->Page->alternates($App->Lang->getAllAlternates());

$arParkItems = $App->Da->getRecords(array(
	'model'		=> 'PARKMAP_ITEMS'
	,'cond'		=> 'AND XL.lang = '.$App->Lang->lgId.' HAVING published ORDER BY X.position ASC'
));
$n_items = count($arParkItems);

$arParkCats = $App->Da->getRecords(array(
	'model'		=> 'PARKMAP_ITEM_CATS'
	,'cond'		=> 'AND XL.lang = '.$App->Lang->lgId.' HAVING published ORDER BY X.position ASC'
));

$App->Page->addClass($App->Lang->getActive().' index');
$App->Page->addJs(<<<JAVASCRIPT

    $('#park_gallery_slider').slick({
        infinite: false
        ,speed: 300
        ,slidesToShow: 1
        ,lazyLoad: 'ondemand'
        ,prevArrow: '<button type="button" data-role="none" class="nav prev on-black slick-prev">&laquo; {$App->Lang->returnT('nav_prev')}</button>'
        ,nextArrow: '<button type="button" data-role="none" class="nav next on-black slick-next">{$App->Lang->returnT('nav_next')} &raquo;</button>'
        ,dots: true
    });

    var n = $('#park_gallery_slider_container .slick-dots > li').length;
    $('#park_gallery_slider_container .slick-dots > li').css('width',(100/n)+'%');


    // street view

    // mostro street view
    $('#street_view_section .cover .circle_btn').on('click',function(){
    	$('#street_view_section iframe,#street_view_section .close_btn').fadeIn();
    });

    // chiudo street view
    $('#street_view_section .close_btn').on('click',function(){
    	$('#street_view_section iframe,#street_view_section .close_btn').fadeOut();
    });



    // gestione mappa
    var map_c 	= {							// contenitore della mappa
					w : 0					// larghezza
					,h : 0					// altezza
					,virtual_sizes : {w: 0,h: 0} // dimensioni virtuali per impostare i limiti di spostamento della mappa sulla base della del valore mode
    			}
    	,map 	= {							// mappa (l'immagine)
					w : 0					// larghezza
					,h : 0					// altezza
					,vw : 0					// larghezza virtuale (dopo aver applicato il fattore di scala)
					,vh : 0					// altezza virtuale (dopo aver applicato il fattore di scala)
					,vx : 0					// posizione x virtuale della mappa (legato al transform)
					,vy : 0					// posizione y virtuale della mappa (legato al transform)
					,grab_boundaries : {top: 0, right: 0, bottom: 0, left: 0}	// i limiti per il grab
					,scale_factor   : .05	// fattore di scala
					,scale_value    : null	    // valore di scala
					,scale_min		: .4	// valore minimo di scala
					,scale_max		: 2		// valore massimo di scala
					,mode 			: 'contain'	// definisce lo zoom minimo della mappa come la proprietà object-fit del css
    			}
    	;

    var MAP_CONTAINER = $('#map_container')
    	,MAP = $('#map')
    	,zoomIN = $('#map_container .zoom.in')
    	,zoomOUT = $('#map_container .zoom.out')
    	;

    // attivo il cursore di grab
    var is_mousedown = false 					// sto cliccando il mouse?
    	,clientStart = {x: 0, y: 0}				// posizione del puntatore all'inizio del trascinamento
    	,mapStart = {x: 0, y: 0}; 				// posizione della mappa all'inizio del trascinamento

    MAP.on('mousedown touchstart',function(e){ 	// quando inizia il trascianmento
    	e.preventDefault();
    	is_mousedown = true;					// il mouse è down
    	clientStart = {x: e.clientX, y: e.clientY}; // imposto i valori di partenza del puntatore
    	mapStart = {x: map.vx,y: map.vy} 		// imposto i valori di partenza della mappa
    	$(this).addClass('grabbing'); 			// mostro il cursore "grabbing"
	});

    MAP.on('mousemove touchmove',function(e){ 	// allo spostamento del puntatore
    	e.preventDefault();
    	if(is_mousedown === true){ 				// se sto premendo
    		map.vx = mapStart.x + e.clientX - clientStart.x;	// imposto i valori della mappa
    		map.vy = mapStart.y + e.clientY - clientStart.y;
    		moveMap();							// mostro la mappa che viene trascinata
    	};
	});

    $(document).on('mouseup touchend',function(e){ 	// alla fine del trascinamento (rimango in ascolto in tutto il DOM, così evito di avere la mappa che si muove se il rilascio avviene oltre la mappa stessa)
    	if(is_mousedown === true){					// se sto premendo 
	    	e.preventDefault();
	    	is_mousedown = false;					// disattivo il mousedown
	    	MAP.removeClass('grabbing'); 	// rimuovo il cursore "grabbing"
    	};
    });


    zoomIN.on('click',function(){				// al click sui pulsanti di zoom aumento il valore di scala della mappa
    	map.scale_value += map.scale_factor;
    	scaleMap();
    });

    zoomOUT.on('click',function(){
    	map.scale_value -= map.scale_factor;
    	scaleMap();
    });


    function setUpMap(){						// calcolo i fattori di scala della mappa
    	map_c.w = MAP_CONTAINER.width();		// ottengo la larghezza del contenitore
    	map_c.h = MAP_CONTAINER.height();		// ... e la sua altezza
    	map.w = MAP.width();					// ottengo la larghezza della mappa
    	map.h = MAP.height();					// ... e la sua altezza

    	scaleMap();
    };


    function scaleMap(){						// funzione che effettua lo scale della mappa

    	rw = map_c.w/map.w;						// calcolo i rapporti tra dimensioni del contenitore e dimensioni della mappa
    	rh = map_c.h/map.h;


    	if(map.mode == 'contain'){				// se sono in modalità contain
    		map.scale_min = rw < rh ? rw : rh;		// prendo il rapporto di scala più piccolo
    		map_c.virtual_sizes.w = map.w*map.scale_min;	// definisco i limiti di confronto per posizionare la mappa e definire i valori limite
    		map_c.virtual_sizes.h = map.h*map.scale_min;
    	} else {
    		map.scale_min = rw > rh ? rw : rh;		// prendo il rapporto di scala più grande
    		map_c.virtual_sizes.w = map_c.w;
    		map_c.virtual_sizes.h = map_c.h;
    	};

        if(!map.scale_value){
            map.scale_value = map.scale_min;
        };

    	if(map.scale_value < map.scale_min){	// se il valore di scala è inferiore al rapporto di scala minimo reimposto al valore minimo
    		map.scale_value = map.scale_min;
    	};
    	if(map.scale_value > map.scale_max){	// se il valore di scala è superiore al rapporto di scala massimo reimposto al valore massimo
    		map.scale_value = map.scale_max;
    	};

    	if(map.scale_value > map.scale_min && map.scale_value < map.scale_max){	// reimposto il valore di scala come multiplo di 5
    		var scale_value = Math.ceil(map.scale_value*100); 	// moltiplico per 100 perché con i numeri decimali a 2 cifre js ritorna sempre un'approssimazione
    		var r = scale_value%(map.scale_factor*100);
    		if(r > 0){
    			map.scale_value = (scale_value-r)/100;
    		};	
    	};

    	map.vw = map.w*map.scale_value;
    	map.vh = map.h*map.scale_value;

    	map.grab_boundaries.top = (map_c.virtual_sizes.h-map.vh)/2;
    	map.grab_boundaries.bottom = (map.vh-map_c.virtual_sizes.h)/2;

    	map.grab_boundaries.left = (map_c.virtual_sizes.w-map.vw)/2;
    	map.grab_boundaries.right = (map.vw-map_c.virtual_sizes.w)/2;

    	moveMap();

    };

    function moveMap(){

    	if(map.vy + map.grab_boundaries.top > 0){
    		map.vy = -map.grab_boundaries.top;
    	};
    	
    	if(map.vy + map.grab_boundaries.bottom < 0){
    		map.vy = -map.grab_boundaries.bottom;
    	};
    	
    	if(map.vx + map.grab_boundaries.left > 0){
    		map.vx = -map.grab_boundaries.left;
    	};
    	
    	if(map.vx + map.grab_boundaries.right < 0){
    		map.vx = -map.grab_boundaries.right;
    	};
    	
    	MAP.css({
    		'-webkit-transform': 'translate('+map.vx+'px,'+map.vy+'px) scale('+map.scale_value+')'
    		,'-moz-transform': 'translate('+map.vx+'px,'+map.vy+'px) scale('+map.scale_value+')'
    		,'transform': 'translate('+map.vx+'px,'+map.vy+'px) scale('+map.scale_value+')'
    	});

    };


    // comparsa del dettaglio
	ParkItemsSlider = $('#parkitems_slider');
	var popup_box = $('#parkitems_section');
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
    ParkItemsSlider.on('init',function(){
        inited = true;
    }).on('afterChange',function(e,slick){
        var sc = ParkItemsSlider.find('.slick-current');
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
    $(document).on('click','.park_detail_link',function(e){
    	e.preventDefault();
    	idx = $(this).closest('li').index();
        if(inited === false){ ParkItemsSlider.slick(slick_opt); };
        if(!popup_box.is(':visible')){
            popup_box.fadeIn(function(){
                ParkItemsSlider.slick('slickGoTo',idx,true);
            });
        } else {
            ParkItemsSlider.slick('slickGoTo',idx,true);
        };
    });


    $(window).on('resize',function(){
    	setUpMap();
    	if(window.windowWidth <= 680){
            if(inited === false){ ParkItemsSlider.slick(slick_opt); };
            ParkItemsSlider.slick('slickGoTo',0,true);
    	};
    });


    // creo gli elementi cliccabili sulla mappa
    $('.park_detail_link').each(function(){
        var x = this.dataset.x;
        var y = this.dataset.y;
        var number = this.dataset.number;
        var button = $('<button class="map_btn"></button>').css({
            top: y+'px'
            ,left: x+'px'
        }).on('click',function(){
            $('.park_detail_link[data-number="'+number+'"]').trigger('click');
        }).appendTo($('#map_container #map'));
    })


JAVASCRIPT
);
$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content">
		<header class="content_header">
			<h1 class="page_title" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('page_title'); ?></h1>
			<figure class="content_header_bg" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<picture>
					<source srcset="/imgs/content/park/poster-mobile.jpg" media="(max-width: 680px)">
					<img src="/imgs/content/park/poster.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				</picture>
			</figure>
		</header>
		
		<a class="fluid_scroll goto_btn" href="#map_section" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<img src="/imgs/layout/arrow.svg" alt="<?php $App->Lang->echoT('go_to',array('object' => $App->Lang->returnT('site_sections'))); ?>">
		</a>

		<div class="page_intro center_width s" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<p><?php $App->Lang->echoT('page_intro'); ?></p>
		</div>

        <!-- GALLERY GIOSTRE (solo mobile) -->
		<section id="parkitems_section">
			<h3 class="title" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('carousels'); ?></h3>
			<div id="parkitem_detail_section" class="slider_container" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<ul class="reset" id="parkitems_slider">
<?php 
if($arParkItems):
	foreach($arParkItems as $PI):
		echo '		<li>
						<figure>
							<img src="'.$App->Lang->returnL('park_item_img',array($PI['id'],'M',$PI['img_1'])).'" srcset="'.$App->Lang->returnL('park_item_img',array($PI['id'],'M',$PI['img_1'])).' 320w,'.$App->Lang->returnL('park_item_img',array($PI['id'],'L',$PI['img_1'])).' 640w" alt="'.$App->Lang->returnT('alt_image').'">
						</figure>
						<span class="n_items">
							<span class="item">'.$PI['number'].'</span>
							<span class="total">'.$n_items.'</span>
						</span>
						<h3 class="detail_title">'.$PI['title'].'</h3>
						<p class="detail_descr">'.$PI['descr'].'</p>
						<p class="detail_cat">'.$App->Lang->returnT('suggested_age').': '.$PI['title_cat'].'</p>
					</li>'.PHP_EOL;
	endforeach;
endif;
?>

				</ul>
			</div>
		</section>

        <!-- MAPPA -->
		<section id="map_section">
			<h3 class="title center_width format_title mode3" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('map_section_title'); ?></h3>
			<div id="map_container" class="loading" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
                <a id="map_link" class="fluid_scroll" href="#park_items_list" data-stop_before="100">&nbsp;</a>
                <div id="map_wrapper">
    				<figure id="map">
    					<img src="/imgs/content/park/map.png" alt="<?php $App->Lang->echoT('map'); ?>">
    				</figure>
                </div>
				<button type="button" class="zoom in"><?php $App->Lang->echoT('zoomin'); ?></button>
				<button type="button" class="zoom out"><?php $App->Lang->echoT('zoomout'); ?></button>
			</div>
			<ol id="park_items_list" class="reset park_item_info center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
<?php 
if($arParkItems):
	foreach($arParkItems as $PI):
		echo '		<li style="border-color: '.$PI['color_cat'].';" title="'.$PI['title_cat'].'">
						<span class="number">'.$PI['number'].'</span> <a class="park_detail_link" href="'.$App->Lang->returnL('park_detail', array($PI['url_page'])).'" data-x="'.$PI['x'].'" data-y="'.$PI['y'].'" data-number="'.$PI['number'].'">'.$PI['title'].'</a>
					</li>'.PHP_EOL;
	endforeach;
endif;
?>
			</ol>
			<ul id="park_items_legenda" class="reset park_item_info center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
<?php 
if($arParkCats):
	foreach($arParkCats as $PC):
		echo '		<li style="border-color: '.$PC['color'].';">
						'.$PC['title'].'
					</li>'.PHP_EOL;
	endforeach;
endif;
?>
			</ul>
		</section>

        <!-- STREETVIEW -->
		<section id="street_view_section" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<button class="close_btn"><span class="sr-only"><?php $App->Lang->echoT('close'); ?></span></button>
			<iframe src="https://www.google.com/maps/embed?pb=!4v1559050167074!6m8!1m7!1sCAoSLEFGMVFpcE1DWFllVnJlSzQ1VmR3eWEwUU03SjBlUHpSeHJITW5NT0U2YUVQ!2m2!1d45.84313882799827!2d12.18000809827402!3f253.56!4f4.060000000000002!5f1.242344593081269" frameborder="0" style="border:0" allowfullscreen></iframe>
			<div class="cover">
				<h3 class="title format_title mode3"><?php $App->Lang->echoT('street_view_section_title'); ?></h3>
				<p><?php $App->Lang->echoT('street_view_section_text'); ?></p>
				<button type="button" class="circle_btn"><span><?php $App->Lang->echoT('start'); ?></span></button>
			</div>
			<figure>
				<img src="/imgs/content/park/street-view-preview.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</figure>
		</section>

		<section id="park_gallery_section" class="center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<div id="park_gallery_slider_container" class="slider_container">
				<ul id="park_gallery_slider" class="slider reset">
					<li><img data-lazy="/imgs/content/park/gallery/gallery-1.jpg" alt=""></li>
					<li><img data-lazy="/imgs/content/park/gallery/gallery-2.jpg" alt=""></li>
					<li><img data-lazy="/imgs/content/park/gallery/gallery-3.jpg" alt=""></li>
					<li><img data-lazy="/imgs/content/park/gallery/gallery-4.jpg" alt=""></li>
					<li><img data-lazy="/imgs/content/park/gallery/gallery-5.jpg" alt=""></li>
					<li><img data-lazy="/imgs/content/park/gallery/gallery-6.jpg" alt=""></li>
					<li><img data-lazy="/imgs/content/park/gallery/gallery-7.jpg" alt=""></li>
					<li><img data-lazy="/imgs/content/park/gallery/gallery-8.jpg" alt=""></li>
					<li><img data-lazy="/imgs/content/park/gallery/gallery-9.jpg" alt=""></li>
				</ul>
			</div>
		</section>

<?php 
	  # FAQS
		$faq_cat_id = 2; // id della categoria parco
	  if($App->faqs['faqs'] && isset($App->faqs['faqs'][$faq_cat_id])): ?>

        <!-- FAQS -->
<section id="topic_faqs_section" class="center_width">
	<h3 class="format_title mode3 data-animscroll" data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('faqs_title'); ?></h3>
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
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>
</div>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>