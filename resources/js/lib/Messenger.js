/**
####################################################################################
class.Messenger
------------------------------------------------------------------------------------
Sistema per gestire le comunicazioni con gli utenti.
Un oggetto Messenger viene inserito al contenitore della pagina
Le personalizzazioni css vanno effettuate da chi implementa la classe.
------------------------------------------------------------------------------------
@params
tutti i parametri servono per la gesione dell'aspetto finale della mappa
####################################################################################
*/

var Messenger = function(params){
    
    var $THIS
        ,o = {
            settings        : $.extend({
                                    use_overlay     : false             // viene utilizzato un overlay che copre tutta la pagina?
                                    ,container      : $('#contenitore') // oggetto a cui viene agganciato Messenger
                                },params)
            ,msg_box            : null                          // contenitore globale del messaggio (compreso eventuale overlay)
            ,msg_container      : null                          // contenitore del messaggio vero e proprio (con pulsante di chiusura)
            ,msg                : null                          // contenitore del testo del messaggio
            ,close_btn          : null                          // pulsante di chiusura del box
            ,t                  : null                          // elemento setTimeout
            ,is_visible         : false
            ,__construct        : function(){

                                        if($THIS.settings.container.length <= 0){
                                            console.debug('[Messenger.'+arguments.callee.name+'] Non è presente in pagina l\'oggetto container: non posso continuare nella costruzione dell\'oggetto');           
                                            return false;
                                        }

                                        // creo il pulsante di chiusura con il listener per il click
                                        $THIS.close_btn         =   $('<button>'+arTrads['chiudi']+'</button>').attr('id','messenger_close_btn').on('click',function(){ $THIS.closeMessenger(); }); 

                                        // creo il box per il testo
                                        $THIS.msg               =   $('<div />').attr('id','messenger_msg');    

                                        // creo il conenitore del messaggio
                                        $THIS.msg_container     =   $('<div />').attr('id','messenger_msg_container').append($THIS.close_btn).append($THIS.msg);
                                        
                                        // creo il box globale e assegno l'eventuale classe se ha l'overlay
                                        $THIS.msg_box = $('<div />').attr('id','messenger_box').append($THIS.msg_container).appendTo($THIS.settings.container);
                                        if($THIS.settings.use_overlay == true){
                                            $THIS.msg_box.addClass('has_overlay');
                                        }


                                    }

            ,closeMessenger     : function(){
                                        clearTimeout($THIS.t);
                                        $THIS.t = setTimeout(function(){
                                            $THIS.msg.empty();
                                        },500);
                                        $THIS.msg_box.removeClass('visible');
                                        $THIS.is_visible = false;
                                        $(document).trigger('messenger_closed');
                                    }

            ,showMessenger      : function(msg){
                                        clearTimeout($THIS.t);
                                        if(typeof msg != 'undefined' && msg.length){
                                            $THIS.msg.html(msg);
                                            $THIS.msg_box.addClass('visible');
                                            $THIS.is_visible = true;
                                            $(document).trigger('messenger_opened');
                                        }
                                    }

            ,setClass           : function(c){
                                        $THIS.msg_container.removeClass();
                                        if(typeof c != 'undefined'){
                                            $THIS.msg_container.addClass(c);
                                        }
                                        return $THIS;
                                    }

            ,getClass           : function(c){

                                        if(typeof(c) != 'undefined'){
                                            return $THIS.msg_container.hasClass(c);
                                        } else {
                                            return $THIS.msg_container.attr('class');
                                        }

                                    }
        };

    $THIS = o;
    o.__construct();

    return o;
};