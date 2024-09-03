/**
####################################################################################
class.Cart
------------------------------------------------------------------------------------
Gestisce le funzioni di carrello
------------------------------------------------------------------------------------
@params
tutti i parametri servono per la gesione dell'aspetto finale della mappa

@depends
    MESSENGER -> per le comunicazioni con l'utente viene utilizzata
    l'istanza MESSENGER della classe Messenger
####################################################################################
*/

var MyCart = function(params){
    var $THIS
        ,o = {

            settings            : $.extend({
                                        btns            : '*[data-role="ecomm_btn"]'        // identificativo per i pulsanti che gestisocno le azioni legate carrello
                                        ,reload_time_ok : null                              // tempo di attesa prima di ricaricare la pagina dopo che è completata un'azione con successo
                                        ,reload_time_ko : null                              // tempo di attesa prima di ricaricare la pagina dopo che è completata un'azione senza successo
                                        ,development    : 0
                                        ,refreshViewCallback : null                              // callback per aggiornamento dell'html in seguito alle azioni
                                        ,reloadOnMessengerClosed : false                                                 // detiene stato corrente per decidere se ricaricare pagina o meno alla chiusura di MESSENGER
                                        ,beforeSend: function() {}
                                        ,afterSend: function() {}
                                    },params)
            ,btns               : null
            ,currentBtn         : null                                                      // il pulsante attualmente cliccato
            ,currentPage        : null
            ,GAECManager        : null                                                      // istanza della class GoogleAnalyticsECManager per l'invio dei dati a Google Analytics
            ,useGAECManager     : false
            ,reloadOnMessengerClosed: false
            ,__construct        : function(){

                                        $THIS.btns = $THIS.settings.btns;
                                        
                                        // definisco i tempi di reload
                                        $THIS.settings.reload_time_ok = $THIS.settings.reload_time_ok !== null ? $THIS.settings.reload_time_ok : (SETTINGS.reload_time_ok ? SETTINGS.reload_time_ok : 3000);
                                        $THIS.settings.reload_time_ko = $THIS.settings.reload_time_ko !== null ? $THIS.settings.reload_time_ko : (SETTINGS.reload_time_ko ? SETTINGS.reload_time_ko : 3000);

                                        $THIS.reloadOnMessengerClosed = $THIS.settings.reloadOnMessengerClosed;

                                        $(document).on("click", $THIS.btns, function(e){

                                            e.preventDefault();

                                            $THIS.currentBtn = $(this);

                                            if($THIS.GAECManager !== null && 'undefined' != typeof $THIS.GAECManager){
                                                $THIS.GAECManager.currentPage = $THIS.currentBtn.data('page') ? $THIS.currentBtn.data('page') :  'Dettaglio prodotto';;
                                            }

                                            switch($THIS.currentBtn.data('type')){          // a seconda del tipo di pulsante imposto il metodo da usare
                                                case 'submit':
                                                    $THIS.submitData();
                                                    break;
                                                case 'spinner':
                                                    $THIS.setNewValue();
                                                    break;
                                                default:    // link
                                                    $THIS.followLink();
                                                    break;
                                            };
                                        });

                                        var $_inputs = $($THIS.btns).siblings('input');

                                        if ($THIS.reloadOnMessengerClosed) {
                                            $(document).on('messenger_closed',function(){
                                                $THIS.reloadPage(1000);
                                            });
                                        };
                                    }

            ,submitData         : function(){

                                        var $_form  = $THIS.currentBtn.attr('form') ? $("#"+$THIS.currentBtn.attr('form').toString().replace(/^#/,'')) : $THIS.currentBtn.closest('form');
                                        var method  = 'undefined' != typeof $_form.attr('method') ? $_form.attr('method').toLowerCase() : 'get';
                                        var data    = $_form.serialize()+'&is_ajax=1';
                                        var action  = $_form.attr('action');
                                        ;

                                        $THIS.sendData(action,method,data,"submit");

                                    }

            ,setNewValue        : function($input){

                                        var $_input = $input || $THIS.currentBtn.siblings('input');
                                        
										// recupero la disponilità, se presente
										var disp = 'undefined' != typeof $_input[0].dataset.max_sellable ? parseInt($_input[0].dataset.max_sellable,10) : 9999;

										// valore attualmente inserito nel campo
                                        var v = parseInt($_input.val(),10) > 0 ? parseInt($_input.val(),10) : 0;
                                        var ov = v;


                                        if(null != $THIS.currentBtn){
                                            var to_add = $THIS.currentBtn.hasClass('sum') ? 1 : -1;

                                            ov = v+to_add <= 0 ? 0 : v+to_add;
                                            if(ov == v){
                                                return false;
                                            }
                                        }

										// rimanenza disponibile sulla base del mio campo di input 
										var r 	= disp - ov;

										// eventuale nuovo valore che compare se eccedo la quantità disponibile a magazzino
										var nv 	= ov <= 0 										// imposto il nuovo valore 
													? 0 
													: (
														r <= 0 									// se la rimanenza è < 0 allora il valore è la disponibilità
														? disp
														: ov
													);

                                            // imposto il valore
								        $_input.val(nv);

                                        if(r < 0){
                                            $THIS.raiseError(window.arTrads.error_max_sellable);
                                            return false;
                                        }

										// genero l'evento che ho concluso l'aggiornamento
										$(document).trigger('cart_changed', $_input);
                                    }       


            ,followLink         : function(){

                                        var url = $THIS.currentBtn.attr('href')+($THIS.currentBtn.attr('href').indexOf('?') != -1 ? '&is_ajax=1' : '?is_ajax=1');
                                        $THIS.sendData(url,'get',null,"followLink");

                                    }

            ,sendData           : function(url,method,data,source){
                                        var ajaxObj = {
                                                            url : url
                                                            ,type : method
                                                            ,data : data
                                                            ,dataType : 'JSON'
                                                            ,beforeSend: function(){
                                                                $THIS.settings.beforeSend();
                                                            }
                                                            ,success : function(resp){
                                                                $THIS.useGAECManager = resp.hasOwnProperty('data') && 'object' == typeof resp.data && $THIS.GAECManager !== null ? true : false;
                                                                if($THIS.useGAECManager === true){
                                                                    $THIS.sendDataToGa(resp.data);
                                                                };
                                                                if(resp.result != 1){
                                                                    
                                                                    $THIS.raiseError(resp.msg);
                                                                }

                                                            }
                                                            ,error : function(a,b,c){
                                                                //console.debug(a,b,c);
                                                                $THIS.raiseError();
                                                            }
                                                            ,complete   : function(resp){
                                                                
                                                                // memorizzato in fase di raccolta dell'evento
                                                                sourceObj = $THIS.currentBtn;
                                                                eventType = source;

                                                                $THIS.settings.afterSend(sourceObj, eventType, resp.responseJSON);
                                                            }
                                                        };

                                        if(data === null){
                                            delete ajaxObj.data;
                                        }

                                        $.ajax(ajaxObj);

                                    }

            ,raiseError         : function(msg){

                                        MESSENGER.setClass('error');
                                        if(typeof msg != 'undefined' && msg.length > 0){
                                            MESSENGER.showMessenger(msg);
                                        } else {
                                            MESSENGER.showMessenger(arTrads['update_cart_error']);
                                        }

                                        setTimeout(function(){
                                            MESSENGER.closeMessenger();
                                        },$THIS.settings.reload_time_ko);

                                    }

            ,reloadPage         : function(t){
                                        if(typeof t == 'undefined'){ t = $THIS.settings.reload_time_ok; }

                                        if($THIS.useGAECManager === true){
                                            $THIS.useGAECManager = false;
                                            $(document).on('gaHitCompleted',function(){
                                                setTimeout(function(){ window.location.reload(); },t)
                                            });
                                        } else {
                                            setTimeout(function(){ window.location.reload(); },t)
                                        }
                                    }

            ,sendDataToGa       : function(data){

                                        try {
                                            $THIS.GAECManager.populateCart(data);
                                        } catch(e){
                                            $THIS.exceptionCatcher(e);
                                        };
                                    }

            ,exceptionCatcher   : function(e){
                                        
                                        if(console){ console.debug('[MyCart'+(this.caller != null ? '.'+this.caller+'()' : '')+']',e); }
                                        if($THIS.settings.development == 1){
                                            alert('error');
                                        }

                                    }

            /**
             * aggiorna vista, chiama callback impostato in setting
             * @param eventType tipo interno dell'evento (submitData, followLink,..)
             */
            /*,refreshView        : function(eventType) {

                                        // se non ho callback rimuovo il loading e mi fermo
                                        if (typeof $THIS.settings.refreshViewCallback != 'function') {
                                            return;
                                        }

                                        // memorizzato in fase di raccolta dell'evento
                                        sourceObj = $THIS.currentBtn;
                                        
                                        var eventType = eventType || null;

                                        // inoltro alla funzione di callback
                                        // - target originario dell'evento
                                        // - tipo di evento 
                                        // - loader in corso (per lasciare a callback la decisione su quando rimuoverlo)
                                        return $THIS.settings.refreshViewCallback(sourceObj, eventType);
                                }*/

        };

    $THIS = o;
    o.__construct();
    return o;
};
