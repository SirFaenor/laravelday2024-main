
var Boot = function(params){

    var $THIS 
        ,o = {
            settings        : $.extend({
                                    disabled      : {} // oggetto con i nomi di funzioni eventualmente disabilitate
                                    ,autoStart  : true
                                },params)
            ,_className     : this.constructor.name
            ,__construct    : function(){
                                    if($THIS.settings.autoStart === true){
                                        $THIS.startBootSequence();
                                    }
                                }


            /**
            *   Eseguo le funzioni predisposte per il boot nel caso non le abbia disabilitate
            *   @param void
            *   @return void
            */
            ,startBootSequence: function(){
                                    for (var j in $THIS.sequence){
                                        if(!$THIS.settings.disabled.hasOwnProperty(j)){
                                            switch(typeof $THIS.sequence[j]){
                                                case 'function': 
                                                    $THIS.sequence[j]();
                                                    break;
                                                case 'object':
                                                    if($THIS.sequence[j].hasOwnProperty('__construct')){
                                                        $THIS.sequence[j].__construct();
                                                    };
                                                    break;
                                            }
                                        }
                                    }
                                }

            /**
            *   la sequenza di funzioni da eseguire come boot
            */
            ,sequence       : {
                                // proptotyping di alcune funzioni
                                ElementsPrototyping: function(){

                                    // return keys of an object for browsers not supporting Object.keys
                                    if (!Object.keys) {
                                        Object.keys = (function () {
                                            'use strict';
                                            var hasOwnProperty = Object.prototype.hasOwnProperty
                                                ,hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString')
                                                ,dontEnums = [
                                                    'toString',
                                                    'toLocaleString',
                                                    'valueOf',
                                                    'hasOwnProperty',
                                                    'isPrototypeOf',
                                                    'propertyIsEnumerable',
                                                    'constructor'
                                                ]
                                                ,dontEnumsLength = dontEnums.length;

                                            return function (obj) {
                                                if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
                                                    throw new TypeError('Object.keys called on non-object');
                                                }

                                                var result=[]
                                                    ,prop, i;

                                                for (prop in obj) {
                                                    if (hasOwnProperty.call(obj, prop)) {
                                                        result.push(prop);
                                                    }
                                                }

                                                if (hasDontEnumBug) {
                                                    for (i = 0; i < dontEnumsLength; i++) {
                                                        if (hasOwnProperty.call(obj, dontEnums[i])) {
                                                            result.push(dontEnums[i]);
                                                        }
                                                    }
                                                }
                                                return result;
                                            };
                                        }());
                                    };

                                    // implements array indexOf for browser not supporting it
                                    if (!Array.prototype.indexOf) {
                                        Array.prototype.indexOf = function(searchElement, fromIndex) {
                                            var k;
                                            if (this == null) throw new TypeError('"this" is null or not defined');

                                            var o = Object(this);

                                            // 2. Let lenValue be the result of calling the Get
                                            //    internal method of o with the argument "length".
                                            // 3. Let len be ToUint32(lenValue).
                                            var len = o.length >>> 0;

                                            // 4. If len is 0, return -1.
                                            if (len === 0){ return -1; }

                                            // 5. If argument fromIndex was passed let n be
                                            //    ToInteger(fromIndex); else let n be 0.
                                            var n = +fromIndex || 0;

                                            if (Math.abs(n) === Infinity){ n = 0; }

                                            // 6. If n >= len, return -1.
                                            if (n >= len) return -1;

                                            // 7. If n >= 0, then Let k be n.
                                            // 8. Else, n<0, Let k be len - abs(n).
                                            //    If k is less than 0, then let k be 0.
                                            k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

                                            // 9. Repeat, while k < len
                                            while (k < len) {
                                                // a. Let Pk be ToString(k).
                                                //   This is implicit for LHS operands of the in operator
                                                // b. Let kPresent be the result of calling the
                                                //    HasProperty internal method of o with argument Pk.
                                                //   This step can be combined with c
                                                // c. If kPresent is true, then
                                                //    i.  Let elementK be the result of calling the Get
                                                //        internal method of o with the argument ToString(k).
                                                //   ii.  Let same be the result of applying the
                                                //        Strict Equality Comparison Algorithm to
                                                //        searchElement and elementK.
                                                //  iii.  If same is true, return k.
                                                if (k in o && o[k] === searchElement) { return k; }
                                                k++;
                                            }
                                            return -1;
                                        };
                                    };

                                    // search for a given value in an object; returns true id value is found, false otherwise
                                    if(!Object.hasOwnProperty('hasOwnValue')){
                                        Object.defineProperty(Object.prototype, 'hasOwnValue',{
                                            value : function(val) {
                                                    for(var prop in this) {
                                                        if(this.hasOwnProperty(prop) && this[prop] === val) {
                                                            return prop;   
                                                        }
                                                    }
                                                    return false;
                                            }
                                            ,enumerable : false
                                        });
                                    };

                                    // puro js per ottenere la posizione di un elemento rispetto alla pagina
                                    if(!Object.hasOwnProperty('elemOffset')){
                                        Object.defineProperty(Object.prototype, 'elemOffset',{
                                            value: function(){
                                                if (this == null){ 
                                                    throw new TypeError('['+$THIS._className+'.ElementsPrototyping.'+arguments.callee.name+'] "this" is null or not defined'); 
                                                }
                                                var rect        = this.getBoundingClientRect()
                                                    ,bodyElt    = document.body;

                                                return {
                                                          top   : (rect.top + bodyElt.scrollTop)
                                                          ,left : (rect.left + bodyElt.scrollLeft)
                                                        };
                                            }
                                            ,enumerable: false
                                            ,writable: false
                                        });
                                    };

                                }


                                // rimozione dell'avviso noscript
                                ,RemoveAlertNoscript: function(){
                                    var alert_noscript = document.getElementById('alert_noscript');
                                    if(alert_noscript) alert_noscript.parentNode.removeChild(alert_noscript);
                                }


                                // rimuovo l'autocomplete dal menu a pulsante
                                ,ToggleBtnsReset: function(){
                                    $('.menu_toggler').attr('autocomplete',0);
                                }


                                // gestione vecchi browsers
                                ,OldBrowsersAlert: function(){

                                    $('html').addClass('js');

                                    if (!Modernizr.svg) { // uso il supporto svg come discriminante perché IE8 non supporta svg
                                        var $_browser_alert = $("<div class='browser_alert' />");
                                        var $_browser_alert_content = $("<div />");

                                        $_browser_alert.css({
                                            width       : '100%'
                                            ,background : '#000000'
                                            ,zIndex     : 1000
                                            ,position   : 'relative'
                                        });

                                        $_browser_alert_content.css({
                                            color       : '#FFFFFF'
                                            ,padding    : '10px 0 10px 0'
                                            ,fontSize   : '11px'
                                            ,lineHeight : '15px'
                                            ,textAlign  : 'center'
                                        }).html(arTrads.alert_browser);

                                        $_browser_alert_content.appendTo($_browser_alert);
                                        $_browser_alert.prependTo('body');
                                    };
                                }


                                // Gestione cookies
                                ,CookieAlert: function(){

                                    // Quando mostro la fascia sui cookies, per evitare che questa copra gli elementi sottostanti
                                    // sposto l'elemento adiacente la fascia dell'altezza della fascia

                                    var $_alert_cookies = document.getElementById('alert_cookies');         // elemento che contiene l'avvertimento sui cookies
                                    if($_alert_cookies){

                                        var measure = {                                                     // dimensioni dell'elemento adiacente
                                                        dimension    : 'margin-top'                         // nome della dimensione
                                                        ,value       : 0                                    // valore della dimensione
                                                    }
                                            ,animatObj = {};                                                // oggetto jquery animate

                                        var r = new RegExp(/cookie_alert\.php(\?.*)?/);                     // espressione regolare per verificare la destinazione
                                        var cookie_accepted = 0;
                                        var blocco = 0;


                                        $(window).on('resize',function(){
                                            var h = parseInt($($_alert_cookies).outerHeight(true),10);                          // ottengo l'altezza dell'avvertimento sui cookies
                                            measure.value = parseInt($($_alert_cookies).next().css(measure.dimension),10);  // ottengo la misura della dimensione dell'elemento vicino (lo aumenterò dell'altezza della fascia)
                                            //$($_alert_cookies).next().css(measure.dimension,h);            
                                        });
                                        // faccio sparire la fascetta dei cookies allo scroll
                                        $(window).on('scroll',function(){                                        
                                            if(window.windowScrollTop > 10 && !cookie_accepted && !blocco){
                                                blocco = 1;
                                                $('#banner_cookie_site_ok').trigger('click');
                                            }
                                        });


                                        $(document).on('click','a',function(e){                             // al click su un link

                                                e.preventDefault();
                                                var dest        = this.href;
                                                var noredirect  = $(this).hasClass('noredirect');
                                                blocco = 1;

                                                if(new RegExp(/\/cookies/).test(dest) || cookie_accepted == 1){        // se la pagina di destinazione è cookies allora non faccio niente e reindirizzo l'utente
                                                    blocco = 0;
                                                    if(!noredirect){ window.location = dest; };

                                                } else {
                                                    $.get('/cookie_alert.php?c=1&is_ajax=1',function(data){     // altrimenti invio l'accettazione dei cookies
                                                        blocco = 0;
                                                        if(parseInt(data,10) != 1){ // se ho esito negativo annullo
                                                            return false; 
                                                        } else { 
                                                            cookie_accepted = 1; 
                                                        };

                                                        animatObj[measure.dimension] = 0;       
                                                        $($_alert_cookies).addClass('accepted');
                                                        setTimeout(function(){
                                                            $_alert_cookies.remove();
                                                            $_alert_cookies = null;

                                                            if(!noredirect && dest && 'string' == typeof dest){   // se posso reindirizzo
                                                                window.location = dest;
                                                            };
                                                        },500);

                                                    });                
                                                }

                                        });

                                    }

                                }


                                // verifico che il device sia touch o meno
                                ,IsTouchDevice: function(){
                                    if('ontouchstart' in window || navigator.maxTouchPoints){
                                        $('html').addClass('touch').removeClass('no-touch');
                                    } else {
                                        $('html').addClass('no-touch').removeClass('touch');
                                    }
                                }


                                // fallback SVG
                                ,SVGFallback: function(){
                                    if (!Modernizr.svg) {
                                        $('img[src$=".svg"]').each(function() {
                                            var re = new RegExp('^\/immagini_layout\/','g');
                                            var src = 'undefined' != typeof $(this).data('fallback') ? $(this).data('fallback') : $(this).attr('src').replace(re,'/immagini_layout/svg_fallback/').replace(/\.svg$/, '.png');  // l'inzio dell'indirizzo e l'estensione 
                                            $(this).attr('src', src);
                                        });
                                    }
                                }


                                // informazioni sulla larghezza della finestra
                                ,DisplayWindowWidth: function(){
                                    if(window.developmentMode){
                                        var $_WW = $('<div />').css({
                                            padding     : 20
                                            ,background : 'rgba(255,255,255,.5)'
                                            ,position   : 'fixed'
                                            ,bottom     : 0
                                            ,right      : 0
                                            ,zIndex     : 1000
                                        });

                                        $(window).on('resize',function(){
                                            $_WW.text($(this).width())
                                        }).resize();

                                        $_WW.prependTo('body');
                                    }
                                }


                                // gestione pulsante scroll su
                                ,FluidScrollListener: function(){

                                    $('.fluid_scroll',$(document)).on('click',function(e){

                                        e.preventDefault();

                                        $THIS.utils.animateScroll(this);

                                    })

                                }


                                // gestione dell'hover nei dispositivi touch
                                ,HoverManager: function(){
                                    $(document).on('touchstart','.touch .touch_hover,.has_tooltip', function(e){
                                        e.stopPropagation();
                                        e.stopImmediatePropagation();
                                        $(this).toggleClass('hover');
                                    });
                                }


                                // Gestione del pulsante Torna su
                                ,ManageTornaSu: function(){
                                    var TornaSuBtn = document.getElementById('torna_su_btn');
                                    window.addEventListener('scroll',function(){                                        
                                        if(window.windowScrollTop > window.windowInnerHeight/2){
                                            $(TornaSuBtn).addClass('show_btn');
                                        } else {
                                            $(TornaSuBtn).removeClass('show_btn');
                                        }
                                    },false);

                                    // se non ho il pulsante avverto
                                    if(TornaSuBtn === null){
                                        console.log('['+$THIS._className+'.'+arguments.callee.name+'] Non è presente un pulsante "torna_su_btn" nella pagina o è sbaglito l\'identificativo');
                                    }
                                }


                                ,ManageFancyApps: function(){


                                    $('.ej_com_popup').on('click',function(e){
                                        e.preventDefault();

                                        var $_alert_cookies = document.getElementById('alert_cookies');

                                        if('undefined' == typeof $.fancybox || $_alert_cookies){
                                            return false;
                                        };

                                        var group = $(this).attr('rel');
                                        var index = $(this).parent('li').index();
                                        if(index && index >= 0){
                                            options.index = index;
                                        };
                                        if(group){
                                            $.fancybox.open($('*[rel="'+group+'"]'));
                                        } else {
                                            var dest = this.href;                                            
                                            $.fancybox.open({src: dest});
                                        }

                                    });

                                }


                                // gestisco la chiusura di un sottomenu all'apertura di un altro
                                ,SubmenuManager: function(){
                                    $('#pages_menu .menu_toggler').on('change',function(e){
                                        $('#pages_menu .menu_toggler:checked').not($(this)).prop('checked',false);
                                    });
                                }


                            }

            ,utils      : {

                                animateScroll   : function(linkObj){

                                    var stop_before    = $THIS.utils.getScrollStopBefore(linkObj)
                                        dest_top       = $THIS.utils.getScrollDestination(linkObj)
                                        ;

                                    $('html,body').animate({scrollTop: (dest_top-stop_before)+'px'});                       // scrollo

                                }

                                ,getScrollDestination: function(linkObj){
                                    var dest = 'undefined' != typeof linkObj.href && linkObj.href.indexOf('#') != -1 ? linkObj.href.split('#').pop() : null;   // recupero il tag di destinazione in pagina

                                    switch(true){
                                        case (dest === null):
                                            console.log('['+$THIS._className+'.'+arguments.callee.name+'] Non è indicata la destinazione o il link è verso l\'esterno e non ha senso lo scroll fluido');
                                            break;
                                        case (dest !== null && !dest.length):   // il caso in cui stia cercando di raggiungere la cima della pagina
                                            break;
                                        case ('undefined' == typeof document.getElementById(dest)):
                                            console.log('['+$THIS._className+'.'+arguments.callee.name+'] Non è presente alcun elemento nella pagina con id '+dest);
                                            break;
                                        case (document.getElementById(dest) === null):
                                            console.log('['+$THIS._className+'.'+arguments.callee.name+'] Non è presente alcun elemento nella pagina o è sbaglito l\'identificativo');
                                            break;
                                    };

                                    return dest.length == 0 ? 0 : parseFloat($('#'+dest).offset().top,10); // se la

                                }

                                ,getScrollStopBefore: function(linkObj){
                                    return 'undefined' != typeof linkObj.dataset.stop_before ? parseInt(linkObj.dataset.stop_before,10) : 0;

                                }

                            }

        };

    $THIS = o;
    o.__construct();

    return o;

}

