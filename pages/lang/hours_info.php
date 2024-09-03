<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads('hours_info');
$App->Lang->setActive('hours_info');

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));

$arOpenings = $App->Da->getRecords(array(
	'model'		=> 'CALENDARS'
	,'cond'		=> 'AND XL.lang = '.$App->Lang->lgId.' HAVING published ORDER BY X.position DESC'
));

$App->Page->alternates($App->Lang->getAllAlternates());

$App->Page->addClass($App->Lang->getActive().' negative');
$App->Page->addJs(<<<JAVASCRIPT

	// se voglio vedere una sezione in particolare ci vado direttamente
	if (location.hash){
		setTimeout(function() {
			window.scrollTo(0, 0);
			$('a[href="'+location.hash+'"]').trigger('click');
	  	}, 1);
	};


	if($('#openings_slider').length > 0){
	    $('#openings_slider').slick({
	        infinite: false
	        ,speed: 300
	        ,slidesToShow: 3
	        ,prevArrow: '<button type="button" data-role="none" class="nav prev on-black slick-prev">&laquo; {$App->Lang->returnT('nav_prev')}</button>'
	        ,nextArrow: '<button type="button" data-role="none" class="nav next on-black slick-next">{$App->Lang->returnT('nav_next')} &raquo;</button>'
	        ,dots: false
	        ,responsive: [
			    {
					breakpoint: 1200,
					settings: {
						slidesToShow: 2
					}
			    },
			    {
					breakpoint: 768,
					settings: {
						slidesToShow: 1
					}
			    }
			]
	    });
	};

JAVASCRIPT
);
$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content">
		<nav class="page_menu center_width" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<a class="fluid_scroll" href="#"><?php $App->Lang->echoT('nav_openings'); ?></a>
			<a class="fluid_scroll" data-stop_before="48" href="#info_section"><?php $App->Lang->echoT('nav_info'); ?></a>
			<a class="fluid_scroll" data-stop_before="48" href="#faqs_section"><?php $App->Lang->echoT('nav_faqs'); ?></a>
			<a class="fluid_scroll" data-stop_before="48" href="#arriving_section"><?php $App->Lang->echoT('nav_arriving'); ?></a>
			<a class="fluid_scroll" data-stop_before="48" href="#contacts_section"><?php $App->Lang->echoT('nav_contacts'); ?></a>
		</nav>
		<section id="openings_section" class="center_width">
			<h1 class="page_title" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('page_title'); ?></h1>
			<p data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('page_intro',array('image' => '<img src="/imgs/layout/icons/rain-black.svg" alt="'.$App->Lang->returnT('alt_image').'">')); ?></p>

<?php if($arOpenings): ?>
			<div id="openings_slider_container" class="slider_container" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<ul id="openings_slider" class="slider reset">
<?php 
foreach($arOpenings as $O):
	echo '				<li>
							<h4>'.$O['title'].'</h4>
							<figure>
								<img src="'.$App->Lang->returnL('calendar_img',array($O['id'],$O['file_1'])).'" alt="'.$App->Lang->returnT('alt_image').'">
							</figure>
						</li>'.PHP_EOL;
endforeach;
?>
				</ul>
			</div>
			<h5 class="legenda_title" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('legenda'); ?></h5>
			<div class="legenda_text" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<h3 class="info_title open"><?php $App->Lang->echoT('open'); ?></h3>
				<p><?php $App->Lang->echoT('open_info'); ?></p>
				<!-- <p class="alert"><?php $App->Lang->echoT('open_info_alert'); ?></p> -->
			</div>
<?php endif; ?>
		</section>

		<section id="info_section" class="center_width">
			<article class="info_text" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<h3 class="title format_title mode3"><?php $App->Lang->echoT('nav_info'); ?></h3>
				<?php $App->Lang->echoT('info_text',array('unpaired_icon' => '<img src="/imgs/layout/icons/disabili.svg" alt="'.$App->Lang->returnT('alt_image').'">')); ?>
			</article>
			<div class="image" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<span class="shape_1"><img src="/imgs/content/hours-info/forma-1.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>" aria-hidden="true"></span>
				<span class="shape_2"><img src="/imgs/content/hours-info/forma-2.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>" aria-hidden="true"></span>
				<img class="figure" src="/imgs/content/hours-info/image.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
			</div>
		</section>

		<?php require ASSETS_PATH.'/html/faqs_all.php'; ?>

		<section id="arriving_section" class="center_width">
			<h3 class="title format_title mode3" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('nav_arriving'); ?></h3>
			<ul id="arriving_list" class="reset" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<li>
					<h4><?php $App->Lang->echoT('car_arriving_title'); ?></h4>
					<p><?php $App->Lang->echoT('car_arriving_text'); ?></p>
					<a class="btn rounded" href="https://www.google.it/maps/dir//Osteria+Ai+Pioppi+Via+VIII+Armata/@45.8434527,12.1460228,13z/data=!4m9!4m8!1m0!1m5!1m1!1s0x47793da259fc8ab5:0x1ecea621b5735036!2m2!1d12.181042!2d45.843458!3e0!5m1!1e2" target="_blank"><?php $App->Lang->echoT('get_directions'); ?></a>
				</li>
				<li>
					<h4><?php $App->Lang->echoT('bus_arriving_title'); ?></h4>
					<p><?php $App->Lang->echoT('bus_arriving_text'); ?></p>
					<a class="btn rounded" href="https://www.google.it/maps/dir//Osteria+Ai+Pioppi+Via+VIII+Armata/@45.8434527,12.1460228,13z/data=!3m1!4b1!4m9!4m8!1m0!1m5!1m1!1s0x47793da259fc8ab5:0x1ecea621b5735036!2m2!1d12.181042!2d45.843458!3e3!5m1!1e2" target="_blank"><?php $App->Lang->echoT('get_directions'); ?></a>
				</li>
				<li>
					<h4><?php $App->Lang->echoT('plane_arriving_title'); ?></h4>
					<p><?php $App->Lang->echoT('plane_arriving_text'); ?></p>
					<a class="btn rounded" href="https://www.google.it/maps/dir//Osteria+Ai+Pioppi+Via+VIII+Armata/@45.8434527,12.1460228,13z/data=!3m1!4b1!4m9!4m8!1m0!1m5!1m1!1s0x47793da259fc8ab5:0x1ecea621b5735036!2m2!1d12.181042!2d45.843458!3e4!5m1!1e2" target="_blank"><?php $App->Lang->echoT('get_directions'); ?></a>
				</li>
			</ul>
		</section>

		<section id="contacts_section" class="center_width">
			<h3 class="title format_title mode3" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('nav_contacts'); ?></h3>
			<div data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('contacts_text'); ?></div>
			<ul id="contacts_btns" class="reset" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<li><a class="btn rounded red mail" href="mailto:<?php echo $App->Config['company']['mail'][0]; ?>"><?php echo $App->Config['company']['mail'][0]; ?></a></li>
				<li>
					<a class="btn rounded red phone" href="callto:<?php echo $App->FixNumber->telnumberToCallableTelnumber($App->Config['company']['phone'][0]); ?>"><?php echo $App->Config['company']['phone'][0]; ?></a>
					<small><?php $App->Lang->echoT('phone_disclaimer'); ?></small>
				</li>
				<li>
					<a class="btn rounded red whatsapp" href="https://wa.me/<?php echo $App->FixNumber->telnumberToCallableTelnumber($App->Config['company']['phone'][1]); ?>">
						<?php echo $App->Config['company']['phone'][1]; ?>
					</a>
					<small><?php $App->Lang->echoT('whatsapp_disclaimer'); ?> WhatsApp</small>
				</li>
			</ul>
		</section>

	</main><!-- #page_content -->
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>