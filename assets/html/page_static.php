<?php 
try {
    global $App;
    $trads_key = $trads_key ?: 'errors';
    $App->Lang->loadTrads($trads_key);

    $meta_title = $App->Lang->returnT('meta_title');
    $home_link  = $App->Lang->returnL('homepage');
    $page_title = $App->Lang->returnT('page_title');
    $page_text  = $App->Lang->returnT('page_text');
    $back       = $App->Lang->returnT('back');
    $nav_homepage= $App->Lang->returnT('home');

    $site_name  = $App->Config['site']['name'];
    $site_code  = $App->Config['site']['code'];

    $footer     = ' <address>
                            '.$App->Config['company']['name_short'].' - '.
                                $App->Config['company']['address'].' - '.
                                $App->Config['company']['zip'].' '.$App->Config['company']['city'].' ('.$App->Config['company']['region_abbr'].') - <abbr title="'.$App->Lang->returnT('label_telefono').'">'.$App->Lang->returnT('label_telefono_abbr').'</abbr> <a class="tel" href="callto:'.$App->FixNumber->telnumberToCallableTelnumber($App->Config['company']['phone'][0]).'">'.$App->Config['company']['phone'][0].'</a> - <a href="mailto:'.$App->Config['company']['mail'][0].'">'.$App->Config['company']['mail'][0].'</a> - <abbr title="'.$App->Lang->returnT('label_piva').'">'.$App->Lang->returnT('label_piva_abbr').'</abbr> '.$App->Config['company']['data']['vat'].'
                    </address>'.PHP_EOL;

    $lgSuff = $App->Lang->lgSuff;


} catch(Exception $e){

    $meta_title = isset($meta_title) ? $meta_title : 'Errore';
    $home_link  = isset($home_link) ? $home_link : '/';
    $page_title = isset($page_title) ? $page_title : 'Oops... si è verificato un errore';
    $page_text  = isset($page_text) ? $page_text : 'Si è verificato un errore nel caricamento della pagina, per favore riprova. <br>I dettagli dell\'errore sono stati inviati all\'amministratore del sistema.';
    $back       = isset($back) ? $back : 'back';
    $nav_homepage = isset($nav_homepage) ? $nav_homepage : 'Home';

    $site_name = isset($site_name) ? $site_name : 'Osteria ai Pioppi';
    $site_code = isset($site_code) ? $site_code : 'AIPIOPPI';
    $lgSuff = isset($lgSuff) ? $lgSuff : 'it';

    $footer = '';
}
?>


<?php if (!IS_AJAX_REQUEST): ?>
    <!DOCTYPE html>
    <html lang="<?php echo $lgSuff; ?>">
    <head>
    <meta charset="utf-8">
    <title><?php echo $meta_title; ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=0.5,maximum-scale=3,user-scalable=yes">
    <link href="/css/base.css" type="text/css" rel="stylesheet">
    <link href="/fonts/cera-pro/stylesheet.css" type="text/css" rel="stylesheet">
    <link href="/css/pages/text.css" type="text/css" rel="stylesheet">
    </head>
    <body class="text-page">


<div id="main_wrapper">
    <nav id="page_header" class="center_width clearfix">
        <h2 id="logo">
            <a href="<?php echo $home_link; ?>">
                <img src="/imgs/layout/<?php echo $site_code; ?>-logo.svg" alt="<?php echo $site_name; ?>">
            </a>
        </h2>
    </nav>

    <main id="page_content" class="center_width xs center">

            <h1 class="format_title mode3"><?php echo $page_title; ?></h1>
<?php endif; # FINE !IS_AJAX_REQUEST ?>




            <p>
<?php
/**
 * se ho un messaggio specifico
 * allora lo uso per restituire l'errore specifico.
 */
if ($App->has("ResponseMessage")): echo $App->ResponseMessage;
else: echo $page_text; 
endif;
?>  
            </p>

<?php if(!isset($is_nav_allowed) || $is_nav_allowed !== false): ?>
            <p>
                <a href="javascript:history.back();">&laquo; <?php echo $back; ?></a>
                / <a href="<?php echo $home_link; ?>"><?php echo $nav_homepage; ?></a>
            </p>
<?php endif; ?>




<?php if (!IS_AJAX_REQUEST): ?>
    </main>

    <div id="page_footer" class="center_width">
        <footer id="pf_bottom" class="center">
            <?php echo $footer; ?>
        </footer>
    </div>
</div>
</body>
</html>

<?php endif; ?>