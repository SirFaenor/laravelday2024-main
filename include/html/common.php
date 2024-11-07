<?php
$App->store("Page", function ($SD_PAGE = NULL) use($App) {
    
    $Page = new Html\Page(array(
        "minimize"         =>      $App->Config['modules']['minimize']
        ,"js_defer"        =>      1
        
    ));  

    $Page->setLang($App->Lang->lgSuff);
    $Page->SD_PAGE = $SD_PAGE ?: $App->Lang->getActive();  # se non è già stata impostata, imposto la chiave del link attivo come SD_PAGE

    return $Page;

});


/*
* risorse standard comuni a tutte le pagine
*/
$App->Page->includeCss(BASE_URL.'/css/build/style.css');
$App->Page->includeJs(BASE_URL.'/js/build/vendor.js');
$App->Page->includeJs(BASE_URL.'/js/build/main.js');


# SCRIPT BASE DA INSERIRE PRIMA DEL BODY
$App->Page->appendHead(<<<HTML
<script type="text/javascript">
//<!--

    // definisco un evento di fallback in caso di errore
    try {
        window.ErrorGetLast = new Event('error_get_last');
    } catch(exception){
        window.ErrorGetLast = document.createEvent('Event');
        window.ErrorGetLast.initEvent('error_get_last', true, true);
    }

    /* nascondo l'html per poi mostrarlo in modo che non si avverta sfarfallio per la comparsa/scomparsa alert noscript */
    var htmlTag = document.getElementsByTagName('html')[0]
        ,html_loading_class = 'loading';
    htmlTag.className = 'js';
    reHtmlLoadingClass = new RegExp('[\s]*'+html_loading_class+'[\s]*');
    htmlTag.className += ' '+html_loading_class;
    htmlTag.className += ' no-touch';

    try {
        window.windowScroll = new Event('scroll');
    } catch(exception){
        window.windowScroll = document.createEvent('Event');
        window.windowScroll.initEvent('scroll', true, true);        
    }

    try {
        window.windowResize = new Event('resize');
    } catch(exception){
        window.windowResize = document.createEvent('Event');
        window.windowResize.initEvent('resize', true, true);        
    }


    // funzione di caricamento della pagina
    setTimeout(function(){
        htmlTag.className = htmlTag.className.replace(reHtmlLoadingClass,'');
    },500);


    // variabili che mi servono in altri contesti
    window.developmentMode  = {$App->Config["errors"]["debug_mode"]};
    window.arTrads          = {$App->Lang->getTrads(true)};


    // creo delle variabili globali per le informazion più usate
    window.windowScrollTop      = window.pageYOffset;
    window.windowInnerHeight    = parseInt(window.innerHeight,10);
    window.windowWidth          = parseInt(window.innerWidth,10);
    window.addEventListener('scroll',function(){
        window.windowInnerHeight = parseInt(window.innerHeight,10);
        window.windowScrollTop = window.pageYOffset | document.body.scrollTop;
    },false);
    window.addEventListener('resize',function(){
        window.windowInnerHeight    = parseInt(window.innerHeight,10);
        window.windowWidth          = parseInt(window.innerWidth,10);
        window.windowScrollTop      = window.pageYOffset | document.body.scrollTop;
    },false);

//-->
</script>

HTML
);
# FINE SCRIPTS BASE DA INSERIRE PRIMA DEL BODY


/**
 * Utilita mobile
 */
$jsLocal = (int)IS_LOCAL_SERVER;
$App->Page->addJs(<<<JAVASCRIPT

    window.isLocalServer        = {$jsLocal};
    window.isBrowserWebkit      = /webkit/i.test(navigator.userAgent);


    window.SETTINGS = {             // impostazioni generali del sito utilizzabili nelle diverse funzioni e classi
        reload_time_ok  : 3000      // tempo di attesa prima di ricaricare la pagina dopo che è completata un'azione con successo
        ,reload_time_ko : 3000      // tempo di attesa prima di ricaricare la pagina dopo che è completata un'azione senza successo
    };

    
    // genero degli eventi per gli elementi in ascolto (aspetto in modo tale da caricare eventuali altri script)
    setTimeout(function(){
        try {
            $(window).trigger('scroll').trigger('resize');
        } catch(e){
            console.log(e);
            window.dispatchEvent(windowScroll);
            window.dispatchEvent(windowResize);
        }
    },200);


    // all'apertura del menu blocco lo scroll della pagina, in modo che se il menu è più grande del display possa scrollare solo il menu
    var main_menu_toggler = document.getElementById('main_menu_toggler');
    if(main_menu_toggler){    
        main_menu_toggler.addEventListener('change',function(e){
            document.getElementById('main_wrapper').style.height = this.checked ? '100%' : 'auto';
        });
    };



    // menu in pagina, scroll
    if($('#page_content .page_menu').length > 0){

        var top_trigger = parseInt($('#page_content .page_menu').offset().top,10)
            ,method = 'addClass';
        $(window).on('resize',function(){
            top_trigger = parseInt($('#page_content .page_menu').offset().top,10)
        });
        $(window).on('scroll',function(){
            if(window.windowScrollTop > top_trigger && !$('#page_content .page_menu').hasClass('fixed')){ $('#page_content .page_menu').addClass('fixed'); }
            if(window.windowScrollTop <= top_trigger && $('#page_content .page_menu').hasClass('fixed')){ $('#page_content .page_menu').removeClass('fixed'); }
        });
    };


    // tooltip
    (function initTooltip(){
        $( ".custom_tooltip" ).tooltip({
        });
    })();

JAVASCRIPT
);


$icon_v = '';   # versione delle icone

$App->Page->link(array("rel" => "shortcut icon", "href"=> BASE_URL."/imgs/layout/favicons/favicon.ico".$icon_v));
$App->Page->link(array("rel" => "icon", "sizes" => "16x16 32x32 64x64", "href"=> BASE_URL."/imgs/layout/favicons/favicon.ico".$icon_v));

$App->Page->meta(array("name" => "viewport", "content" => "width=device-width,user-scalable=yes"));




