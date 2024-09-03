    <div id="page_footer" class="center_width">

        <nav id="pf_menu" data-animscroll data-as-delay="0" data-as-animation="fadeIn">
            <a href="<?php $App->Lang->echoL('videogallery'); ?>"><?php $App->Lang->echoT('nav_videogallery'); ?></a>
            <a href="<?php $App->Lang->echoL('advices'); ?>"><?php $App->Lang->echoT('nav_advices'); ?></a>
            <a href="<?php echo $App->Config['google']['maps']['link']; ?>" target="_blank"><?php $App->Lang->echoT('nav_reach_us'); ?></a>
        </nav>

        <?php require __DIR__.'/social_links.php'; ?>

        <footer id="pf_bottom" class="clear clearfix">

            <address data-animscroll data-as-delay="600ms" data-as-animation="fadeIn">
                <strong class="company"><?php echo $App->Config['company']['name_short']; ?></strong> - 
                <?php 
                    echo    $App->Config['company']['address'].' - '.$App->Config['company']['city'].' ('.$App->Config['company']['region_abbr'].') - 
                            <a href="callto:'.$App->FixNumber->telnumberToCallableTelnumber($App->Config['company']['phone'][0]).'"><img class="contact_icon" src="/imgs/layout/icons/telephone.svg" alt="'.$App->Lang->returnT('label_telefono').'"> '.$App->Config['company']['phone'][0].'</a> - 
                            <a href="mailto:'.$App->Config['company']['mail'][0].'"><img class="contact_icon" src="/imgs/layout/icons/email.svg" alt="'.$App->Lang->returnT('label_email').'"> '.$App->Config['company']['mail'][0].'</a>
                            '.PHP_EOL;
                ?>
            </address> 
            <a class="credits" href="<?php $App->Lang->echoL('credits'); ?>#names" data-fancybox data-src="#credits_box" data-modal="true" data-animscroll data-as-delay="1200" data-as-animation="fadeIn"><?php $App->Lang->echoT('nav_credits'); ?></a>
            <p class="legal_info_container" data-animscroll data-as-delay="900ms" data-as-animation="fadeIn">
                <span><abbr title="<?php $App->Lang->echoT('label_piva'); ?>"><?php $App->Lang->echoT('label_piva_abbr'); ?></abbr> <?php echo $App->Config['company']['data']['vat']; ?> </span>
                <a class="privacy" href="<?php $App->Lang->echoL('privacy'); ?>"><?php $App->Lang->echoT('privacy'); ?></a>
				<!-- <a class="privacy" href="<?php $App->Lang->echoL('terms'); ?>"><?php $App->Lang->echoT('terms'); ?></a> -->
                <a class="legal_info" href="<?php $App->Lang->echoL('legal_info'); ?>"><?php $App->Lang->echoT('legal_info'); ?></a> 
                <a class="cookies" href="<?php $App->Lang->echoL('cookies'); ?>"><?php $App->Lang->echoT('cookies'); ?></a> 
                <a class="sitemap" href="<?php $App->Lang->echoL('sitemap_user'); ?>"><?php $App->Lang->echoT('sitemap_user'); ?></a>
            </p>

  
        </footer>

        <p id="advices_btn_container" data-animscroll data-as-delay="1500" data-as-animation="fadeIn">
            <a href="<?php $App->Lang->echoL('advices'); ?>" id="advices_btn">
                <?php $App->Lang->echoT('advices_btn_title'); ?>
                <small><?php $App->Lang->echoT('follow_our_advices'); ?></small>
            </a>
        </p>

        <div id="credits_box">
            <button data-fancybox-close class="close_btn"><span class="sr-only"><?php $App->Lang->echoT('close'); ?></span></button>
            <?php $App->Lang->echoT('credits_text'); ?>
        </div>

    </div> <!-- #page_footer -->

