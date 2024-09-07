<?php 
    $activeLinkKey = $App->Lang->getActive();   // recupero la chiave attiva
    $arCartButtonClasses = array();
    if($activeLinkKey == 'cart_detail'):
        $arCartButtonClasses[] = 'active';
    endif;
    if($App->Cart->countItems() > 0):
        $arCartButtonClasses[] = 'cart_not_empty';
    endif;
?>
        <div id="page_header">
            <input type="checkbox" id="main_menu_toggler" aria-hidden="true" autofocus>
            <label for="main_menu_toggler" aria-controls="main_menu" role="button" id="main_menu_toggler_label">
                <span class="sr-only"><?php $App->Lang->echoT('aria_label_toggle_menu_custom',array('menuName' => '')); ?></span>
            </label>

            <?php $logo_tag = $activeLinkKey == 'homepage' ? 'h1' : 'h2'; ?>
            <<?php echo $logo_tag; ?> id="logo">
                <a class="format_title mode1" href="<?php $App->Lang->echoL('homepage'); ?>">
                        <img 
                            src="/imgs/layout/logo.svg" 
                            alt="<?php echo $App->Config['site']['name']; ?>" title="<?php echo $App->Config['site']['name']; ?>">
                </a>
            </<?php echo $logo_tag; ?>>

            <div id="top_menu">
                <?php require __DIR__.'/social_links.php'; ?>
                <?php require __DIR__.'/lang_menu.php'; ?>
            </div>

            
            <a id="buy_online"<?php echo count($arCartButtonClasses) > 0 ? ' class="'.implode(' ',$arCartButtonClasses).'"' : ''; ?> href="<?php $App->Lang->echoL('cart_detail'); ?>">
                <span><?php $App->Lang->echoT('nav_order_online'); ?></span>
            </a>
           

            <nav id="main_menu" data-animscroll data-as-delay="0" data-as-animation="fadeIn">

<!-- 
NAVIGAZIONE PRINCIPALE
-->                

                <ul id="pages_menu" class="reset">
                    <li class="homepage"><a<?php echo $activeLinkKey == 'homepage' ? ' class="active"' : ''; ?> href="#"><?php $App->Lang->echoT('home'); ?></a></li>
                    <li class="park"><a<?php echo $activeLinkKey == 'park' ? ' class="active"' : ''; ?> href="#"><?php $App->Lang->echoT('nav_park'); ?></a></li>
                    <li class="tavern"><a<?php echo $activeLinkKey == 'tavern' ? ' class="active"' : ''; ?> href="#"><?php $App->Lang->echoT('nav_tavern'); ?></a></li>
                    <li class="story"><a<?php echo $activeLinkKey == 'story' ? ' class="active"' : ''; ?> href="#"><?php $App->Lang->echoT('nav_story'); ?></a></li>
                    <li class="hours_info"><a<?php echo $activeLinkKey == 'hours_info' ? ' class="active"' : ''; ?> href="#"><?php $App->Lang->echoT('nav_hours_info'); ?></a></li>
                    <li class="cart"><a<?php echo count($arCartButtonClasses) > 0 ? ' class="'.implode(' ',$arCartButtonClasses).'"' : ''; ?> href="<?php $App->Lang->echoL('cart_detail'); ?>"><span><?php $App->Lang->echoT('nav_order_online'); ?></span></a></li>
                </ul>
                

                <?php require __DIR__.'/lang_menu.php'; ?>
                <?php require __DIR__.'/social_links.php'; ?>

            </nav>

        </div>

        <div id="alert_noscript" role="alert">
            <?php $App->Lang->echoT('alert_noscript'); ?> 
        </div>
