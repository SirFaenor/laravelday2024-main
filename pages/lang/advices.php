<?php
require(ASSETS_PATH.'/html/common.php');
$App->Lang->loadTrads('tavern,advices');
$App->Lang->setActive('advices');

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));

$App->Page->alternates($App->Lang->getAllAlternates());

$App->Page->addClass($App->Lang->getActive());

$App->Page->open();

?>
<div id="main_wrapper">

	<?php require ASSETS_PATH.'/html/page_header.php'; ?>
	<main id="page_content">
		<div class="content_header">
            <header>
                <h1 class="page_title" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('page_title'); ?></h1>
                <p class="subtitle" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('page_subtitle'); ?></p>
            </header>
			<figure class="content_header_bg" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
				<picture>
					<source srcset="/imgs/content/advices/poster-mobile.jpg" media="(max-width: 680px)">
					<img src="/imgs/content/advices/poster.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
				</picture>
			</figure>
		</div>
		
		<a class="fluid_scroll goto_btn" href="#hours_section" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
			<img src="/imgs/layout/arrow.svg" alt="<?php $App->Lang->echoT('go_to',array('object' => $App->Lang->returnT('site_sections'))); ?>">
		</a>

        <section id="hours_section" class="center_width center">
            <h3 class="title format_title mode3" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('hours_section_title'); ?></h3>
            <p class="intro" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('hours_section_intro'); ?></p>
            <div id="hours_graphs" class="reset">
                <h4 data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('hours_graph_title'); ?></h4>
                <figure data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
                    <img src="/imgs/content/advices/grafico1.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                    <figcaption><?php $App->Lang->echoT('saturday'); ?></figcaption>
                </figure>
                <figure data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
                    <img src="/imgs/content/advices/grafico2.jpg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                    <figcaption><?php $App->Lang->echoT('sunday_holydays'); ?></figcaption>
                </figure>
            </div>
        </section>

        <section id="arriving_section" class="clearfix center_width center">
            <h3 class="title red format_title mode3" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('arriving_section_title'); ?></h3>
            <p class="intro" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn"><?php $App->Lang->echoT('arriving_section_intro'); ?></p>
            
            <div class="intro_2">
                <img src="/imgs/content/advices/img1.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                <p class="title"><?php $App->Lang->echoT('arriving_step_1_title'); ?></p>
                <a href="<?php $App->Lang->echoL('hours_info'); ?>"><?php $App->Lang->echoT('arriving_step_1_text'); ?></a>
                <p><?php $App->Lang->echoT('arriving_scegli'); ?></p>
            </div>

        </section>

            <?php require ASSETS_PATH.'/html/order_info_widget.php' ?>

            
            <?php
            /**
             * Commento a favore di widget comune
             */
            if(1==2) :
            ?>
            <div class="arriving_steps arriving_steps-red reset" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
                <div class="title-wrap">
                    <img class="title-img" src="/imgs/content/advices/img3.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                    <h4 class="title btn red"><?php $App->Lang->echoT('ordine_classico'); ?></h4>
                </div>    
                <ol>    
                    <li class="step_2">
                        <img src="/imgs/content/advices/img2.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                        <div>
                            <h4><?php $App->Lang->echoT('arriving_step_2_title'); ?></h4>
                            <p><?php $App->Lang->echoT('arriving_step_2_text'); ?></p>
                        </div>
                    </li>
                    <li class="step_3">
                        <img src="/imgs/content/advices/img3.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                        <div>
                            <h4><?php $App->Lang->echoT('arriving_step_3_title'); ?></h4>
                            <p><?php $App->Lang->echoT('arriving_step_3_text',array('link' => $App->Lang->returnL('download_doc',array('osteria-ai-pioppi-menu.pdf')))); ?></p>
                        </div>
                    </li>
                    <li class="step_4">
                        <img src="/imgs/content/advices/img4.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                        <div>
                            <h4><?php $App->Lang->echoT('arriving_step_4_title'); ?></h4>
                            <p><?php $App->Lang->echoT('arriving_step_4_text'); ?></p>
                        </div>
                    </li>
                    <li class="step_5">
                        <img src="/imgs/content/advices/img5.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                        <div>
                            <h4><?php $App->Lang->echoT('arriving_step_5_title'); ?></h4>
                            <p><?php $App->Lang->echoT('arriving_step_5_text'); ?></p>
                        </div>
                    </li>
                    <!-- <li class="step_6">
                        <img src="/imgs/content/advices/img6.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                        <div>
                            <h4><?php $App->Lang->echoT('arriving_step_6_title'); ?></h4>
                            <p><?php $App->Lang->echoT('arriving_step_6_text'); ?></p>
                        </div>
                    </li> -->
                </ol>
            </div>

            <div class="arriving_steps arriving_steps-blu reset" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
                <div class="title-wrap">
                    <img class="title-img" src="/imgs/content/tavern/ordering_section_2_step_1.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                    <h4 class="title btn blu"><?php $App->Lang->echoT('ordine_online'); ?></h4>
                </div>
                <ol>    
                    <li class="step_1">
                        <img class="" src="/imgs/content/tavern/ordering_section_2_step_1.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                        <div>
                            <h4><?php $App->Lang->echoT('arriving_online_step_1_title'); ?></h4>
                            <p><?php $App->Lang->echoT('arriving_online_step_1_text'); ?></p>
                        </div>
                    </li>
                    <li class="step_2">
                        <img src="/imgs/qrcode-link-ordine.svg" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                        <div>
                            <h4><?php $App->Lang->echoT('arriving_online_step_2_title'); ?></h4>
                            <p><?php $App->Lang->echoT('arriving_online_step_2_text'); ?></p>
                        </div>
                    </li>
                    <li class="step_3">
                        <img src="/imgs/content/advices/img5.png" alt="<?php $App->Lang->echoT('alt_image'); ?>">
                        <div>
                            <h4><?php $App->Lang->echoT('arriving_online_step_3_title'); ?></h4>
                            <p><?php $App->Lang->echoT('arriving_online_step_3_text'); ?></p>
                        </div>
                    </li>
                </ol>
            </div>
            <?php endif; ?>

        <?php require ASSETS_PATH.'/html/faqs_all.php'; ?>

	</main><!-- #page_content -->
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>
</div>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>