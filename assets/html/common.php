<?php
$App->store("Page", function ($SD_PAGE = NULL) use($App) {
    
    $Page = new Html\Page(array(
        "minimize"         =>      $App->Config['modules']['minimize']
        ,"js_defer"        =>      1
        
        // percorsi assoluto sul filesystem
        ,"css_output_path"  =>      ASSETS_PATH.'/css'
        ,"js_output_path"   =>      ASSETS_PATH.'/js'
       
        // url di base per file minimizzati
        ,"css_output_url"  =>      BASE_URL.'/css'
        ,"js_output_url"   =>      BASE_URL.'/js'
        
    ));  

    $Page->setLang($App->Lang->lgSuff);
    $Page->SD_PAGE = $SD_PAGE ?: $App->Lang->getActive();  # se non è già stata impostata, imposto la chiave del link attivo come SD_PAGE

    return $Page;

});


if($App->Config['modules']['structured_data'] === true):

    // Dati strutturati e Microdati
    foreach ((include ASSETS_PATH.'/sd/organization.json.php') as $MD):
        $App->Page->addMicrodata($MD);
    endforeach;

    if($App->Page->SD_PAGE):
        if(file_exists(ASSETS_PATH.'/sd/'.PAGEKEY.'.json.php')):
            $App->Page->addMicrodata((include ASSETS_PATH.'/sd/'.$App->Page->SD_PAGE.'.json.php'));
        else:
            trigger_error('['.__FILE__.'] File <code>'.ASSETS_PATH.'/sd/'.$App->Page->SD_PAGE.'.json.php</code> inesistente. Se la pagina non necessita di dati strutturati specifici, ignorare questo avvertimento.',E_USER_NOTICE);
        endif;
    else:
        trigger_error('['.__FILE__.'] <code>$SD_PAGE</code> non definita. Se la pagina non necessita di dati strutturati specifici, ignorare questo avvertimento.',E_USER_NOTICE);
    endif;
    $App->Page->microdataToPage();
    
endif;




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

    // Loading
    try {
        window.LOADING = new Loading({animationDuration: 500});
    } catch(e){
        console.log('Inizializzazione di Loading.js fallita',e);
    };

    // sequenza di boot
    window.BS = new Boot({disabled: {HoverManager : true}});

    window.MESSENGER = new Messenger({container: $("body")}); // sistema per gestire le comunicazioni a video con l'utente

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


    // FAQS

    // apro la prima categoria
    $($('#faqs_section .faqs_btns .btn.active').attr('href')).slideDown();

    // cambio categoria
    $('#faqs_section .faqs_btns .btn').on('click',function(e){
        e.preventDefault();
        if($(this).hasClass('active')){ return false; }
        var dest = $(this).attr('href');

        $('#faqs_section .faqs_btns .btn.active').removeClass('active');
        $('#faqs_section .faqs_collection:visible').slideUp();

        $(this).addClass('active');
        $(dest).slideDown();

    });

    // apro la domanda
    $('.faqs_collection li .openclose_btn,.faqs_collection li .question').on('click',function(){
        var is_opened = $(this).hasClass('opened');
        $('.faqs_collection li .openclose_btn.opened,.faqs_collection li .question').removeClass('opened');
        $('.faqs_collection li .answer:visible').slideUp();
        if(!is_opened){
            $(this).closest('li').children('.openclose_btn').addClass('opened').siblings('.question').addClass('opened').siblings('.answer').slideDown();
        };
    });

    if($('#topic_faqs_section #faqs_slider').length > 0){
        $('#topic_faqs_section #faqs_slider').slick({
            infinite: false
            ,speed: 300
            ,slidesToShow: 1
            ,adaptiveHeight: true
            ,prevArrow: '<button type="button" data-role="none" class="nav prev on-black slick-prev">&laquo; {$App->Lang->returnT('nav_prev')}</button>'
            ,nextArrow: '<button type="button" data-role="none" class="nav next on-black slick-next">{$App->Lang->returnT('nav_next')} &raquo;</button>'
            ,dots: false
        });
    };

    // logo che scrolla 
    var logoOriginal = $('#logo');
    var logoCloned = logoOriginal.clone();                            // clono il logo
    logoCloned.addClass('cloned').prependTo($('#main_wrapper'));    // lo aggancio al main wrapper
    var coords = {x: 0, y: 0};      // coordinate dove posizionare il logo clonato
    $(window).on('resize',function(){
        coords.x = parseInt(logoOriginal.offset().left,10);
        coords.yBase = parseInt(logoOriginal.offset().top,10);
        coords.y = parseInt((coords.yBase-$('#page_header').offset().top),10); // calcolo la posizione del logo sulla base dell'header, altrimenti nel caso di avvisi rischia di rimanere a metà pagina
        logoCloned.css({ top: coords.yBase+'px',left: coords.x+'px'})
    });
    var onscroll_limit = 150         // limite per attivazione / disattivazione classe onscroll
        ,onscrollMethod = 'addClass'
        ;
    $(window).on('scroll',function(){
        if(window.windowScrollTop < onscroll_limit){
            $('#logo').removeClass('onscroll').css('top',coords.yBase+'px');
        } else {
            $('#logo').addClass('onscroll').css('top',coords.y+'px');
        };
    });


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


    // inizializzo il carrello

    /**
     * Gestione funzioni carrello
     */
    window.Cart = new MyCart({
        reloadOnMessengerClosed: false
        ,reload_time_ok  : window.SETTINGS.reload_time_ok      // tempo di attesa prima di ricaricare la pagina dopo che è completata un'azione con successo (se c'è callback viene gestito da quest'ultimo)
        ,reload_time_ko : window.SETTINGS.reload_time_ko      // tempo di attesa prima di ricaricare la pagina dopo che è completata un'azione senza successo (se c'è callback viene gestito da quest'ultimo)
        ,loading : null
        ,beforeSend : function() {

            if($("body").is(".page_cart_detail")) {
                return;
            }
            
            window.LOADING.transitionIn();
        }
        
        /**
         * Callback eseguito in caso di successo della prima chiamata di aggiornamento 
         * @param sourceObj elemento html su cui si è agito
         * @param eventType tipo di evento (followLink,...)
         */
        ,afterSend : function(sourceObj, eventType, cartResponse) {
                        
            // fermo in caso di errore
            if(!cartResponse || 'object' != typeof cartResponse){
                return window.LOADING.transitionOut();
            }
                
            // se ho un contatore di elementi, lo aggiorno
            if(cartResponse.hasOwnProperty('itemscounter')){
                if($('#to_cart_btn .cart_icon').find('.cart_counter').length){
                    $('.cart_counter').text(cartResponse.itemscounter);
                } else {
                    $('<sup class="cart_counter">'+cartResponse.itemscounter+'</sup>').prependTo($('#to_cart_btn .cart_icon'));
                }
            };
        

            /**
             * Se click su "Logout"
             */
            if (sourceObj.data("event-name") && sourceObj.data("event-name") == 'logout') {
                return setTimeout(function() {
                    window.location.reload();
                }, 300);
            }

           
            if(cartResponse.result == 1){
                $(document).trigger('cart_updated');
            };
            

            /**
             * Se devo ricaricare
             */
            if(cartResponse.hasOwnProperty('needsreload') && cartResponse.needsreload === true){
                MESSENGER.setClass("success");
                MESSENGER.showMessenger(cartResponse.msg);

                setTimeout(function() {
                    window.location.reload();
                }, 1500);
                return;
            }


            /**
             * Negli altri casi (aggiunta, rimozione, codice sconto...)
             * ricarica in aj l'url corrente ma aggiorna nella pagina solo le parti interessate 
             * a seconda di dove mi trovo
             */
            $.ajax({
                url: window.location 
                ,dataType: 'html'
                ,beforeSend : function() {
                }
                ,success: function (response) {

                    var responseContents = $(response).contents();
                    var messenger_close_time = 3000;
                    MESSENGER.setClass(cartResponse.result == 1 ? "success" : "error").showMessenger(cartResponse.msg);
                    

                    /**
                     * Dettaglio carrello
                     */
                    if ($("body").is(".page_cart_detail")) {
                        $("#cart_wrapper .refresh").html(responseContents.find("#cart_wrapper .refresh").html()); 

                        // ripristino visibilità della tab corrente
                        var dest = $("#cart_table_tab_switcher label.active").attr('for');
                        $('#' + dest).prop("checked", true);

                    } 


                    /**
                     * Checkout (dati utente)
                     */
                    if ($("body").is(".page_cart_user_data")) {
                        $("#cart_subsection_summary").html(responseContents.find("#cart_subsection_summary").html());  
                        $("#cart_subsection_submit").html(responseContents.find("#cart_subsection_submit").html());  

                    }


                    /**
                     * comune (riepilogo carrello in header)
                     */ 
                    $("#pages_menu .cart_detail").html(responseContents.find("#pages_menu .cart_detail").html());  


                    /**
                     * Chiudo messenger
                     */
                    setTimeout(function() {
                        MESSENGER.closeMessenger();
                    }, window.SETTINGS.reload_time_ok);

                }
                ,error: function() {
                    MESSENGER.setClass('error').showMessenger(arTrads['update_cart_error']);
                }
            }).always(function(){
                window.LOADING.transitionOut();
            });
        }

    });
    
    
    /**
     * Tabs
     */
    (function () {

        // cambio categoria
        $('.tabber .btn').on('click',function(e){
            e.preventDefault();
            if($(this).hasClass('active')){ return false; }

            // scambia classi attive pulsante
            $('.tabber .btn.active').removeClass('active');
            $(this).addClass('active'); 

            // scambia tab
            var dest = $(this).attr('href');
            $(dest).addClass("to-open");
            $('.tabber-content:not(.to-open').slideUp();
            $(dest).slideDown().removeClass("to-open");


        });

    })();
JAVASCRIPT
);


/**
 * Google Analytics
 */
$App->Page->appendHead(<<<HTML
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$App->Config['google']['analytics']['key']}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', '{$App->Config['google']['analytics']['key']}');
</script>
HTML
);


$icon_v = '';   # versione delle icone

$App->Page->link(array("rel" => "shortcut icon", "href"=> BASE_URL."/imgs/layout/favicons/favicon.ico".$icon_v));
$App->Page->link(array("rel" => "icon", "sizes" => "16x16 32x32 64x64", "href"=> BASE_URL."/imgs/layout/favicons/favicon.ico".$icon_v));

if($App->Config['modules']['multi_favicon'] === true):

    $App->Page->link(array("rel" => "apple-touch-icon", "sizes" => "180x180", "href"=> BASE_URL."/imgs/layout/favicons/apple-touch-icon.png".$icon_v));
    $App->Page->link(array("rel" => "image/png", "sizes" => "32x32", "href"=> BASE_URL."/imgs/layout/favicons/favicon-32x32.png".$icon_v));
    $App->Page->link(array("rel" => "image/png", "sizes" => "194x194", "href"=> BASE_URL."/imgs/layout/favicons/favicon-194x194.png".$icon_v));
    $App->Page->link(array("rel" => "image/png", "sizes" => "192x192", "href"=> BASE_URL."/imgs/layout/favicons/android-chrome-192x192.png".$icon_v));
    $App->Page->link(array("rel" => "image/png", "sizes" => "16x16", "href"=> BASE_URL."/imgs/layout/favicons/favicon-16x16.png".$icon_v));

    //$App->Page->link(array("rel" => "manifest", "href"=> BASE_URL."/imgs/layout/favicons/site.webmanifest".$icon_v));
    $App->Page->link(array("rel" => "mask-icon", "color"=>$App->Config['site']['color'], "href"=> BASE_URL."/imgs/layout/favicons/safari-pinned-tab.svg".$icon_v));


    $App->Page->meta(array("name"=>"msapplication-TileColor", "content"=>$App->Config['site']['color']));
    $App->Page->meta(array("name"=>"msapplication-TileImage", "content"=>BASE_URL."/imgs/layout/favicons/mstile-144x144.png".$icon_v));
    $App->Page->meta(array("name"=>"msapplication-config", "content"=>BASE_URL."/imgs/layout/favicons/browserconfig.xml".$icon_v));
    $App->Page->meta(array("name"=>"theme-color", "content"=>$App->Config['site']['color']));

endif;

$App->Page->meta(array("name" => "viewport", "content" => "width=device-width,user-scalable=yes"));


if (empty($_COOKIE["cookie_alert"])):
    $App->Page->prependHtml('<div id="alert_cookies" role="dialog"><div>'.$App->Lang->returnT("alert_cookies",array('cookie_setter' => '/cookie_alert.php')).'</div></div>'."\n");
endif;


# eventuali avvisi

if($App->warning):
    $App->Page->prependHtml('<div id="warning" style="color: '.$App->warning['color_text'].'; background: '.$App->warning['color_bg'].'">'.($App->warning['fullPageUrl'] ? '<img src="'.$App->warning['fullPageUrl'].'" alt="">' : '').'<span class="warning_text">'.$App->warning['title'].'</span></div>'.PHP_EOL);
endif;

$App->Page->appendHead(<<<HTML
<!-- Facebook Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '456381321839316');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=456381321839316&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->

HTML
);