    <div id="page_footer" class="center_width">

        <nav id="pf_menu" data-animscroll data-as-delay="0" data-as-animation="fadeIn">
            <a href="#"><?php $App->Lang->echoT('nav_videogallery'); ?></a>
            <a href="#"><?php $App->Lang->echoT('nav_advices'); ?></a>
            <a href="<?php echo $App->Config['google']['maps']['link']; ?>" target="_blank"><?php $App->Lang->echoT('nav_reach_us'); ?></a>
        </nav>

        <?php require __DIR__.'/social_links.php'; ?>

        <footer id="pf_bottom" class="clear clearfix">

            <address data-animscroll data-as-delay="600ms" data-as-animation="fadeIn">
                <strong class="company"><?php echo $App->Config['company']['name_short']; ?></strong>
            </address> 
            <a class="credits" href="#"><?php $App->Lang->echoT('nav_credits'); ?></a>
            <p class="legal_info_container" data-animscroll data-as-delay="900ms" data-as-animation="fadeIn">
                <span><abbr title="<?php $App->Lang->echoT('label_piva'); ?>"><?php $App->Lang->echoT('label_piva_abbr'); ?></abbr> <?php echo $App->Config['company']['data']['vat']; ?> </span>
                <a class="privacy" href="#"><?php $App->Lang->echoT('privacy'); ?></a>
				<!-- <a class="privacy" href="#"><?php $App->Lang->echoT('terms'); ?></a> -->
                <a class="legal_info" href="#"><?php $App->Lang->echoT('legal_info'); ?></a> 
                <a class="cookies" href="#"><?php $App->Lang->echoT('cookies'); ?></a> 
                <a class="sitemap" href="#"><?php $App->Lang->echoT('sitemap_user'); ?></a>
            </p>

  
        </footer>

        <div id="credits_box">
            <button data-fancybox-close class="close_btn"><span class="sr-only"><?php $App->Lang->echoT('close'); ?></span></button>
        </div>

    </div> <!-- #page_footer -->

