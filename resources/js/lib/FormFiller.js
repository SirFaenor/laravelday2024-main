/**
####################################################################################
FormFiller
------------------------------------------------------------------------------------
Salva in sessione i dati di un form in modo da non perderli in caso di reload.
La funzione è puramente di servizio , quindi non da ritorni o dialoga con l'utente
------------------------------------------------------------------------------------
@params
tutti i parametri servono per la gesione del servizio
####################################################################################
*/

var FormFiller = function(params){

    var $THIS
        ,o      = {

            settings            : $.extend({
                                        urlDataRegister     : null
                                        ,objForm            : null
                                        ,autoRegisterMode   : 1 // se attivato invierà il salvataggio per ogni cambiamento nel valore degli input
                                        ,formRegisterMode   : 1 // 0, ad ogni cambiamento aggiornerà tutti i campi (utile in caso di autocompilazione), 1 aggiornerà solamente il campo cambiato
                                    },params)
            ,formInputs         : null
            ,trigger            : null      // l'elemento che scatena la chiamata ajax
            ,blocco             : 0         // evito il doppio evento
            ,__construct        : function(){
                                        if($THIS.settings.urlDataRegister === null || $THIS.settings.objForm === null){
                                            throw "[FormFiller.__construct] Manca sia l'url dove salvare i dati sia i form di cui ascoltare gli input.";
                                        };

                                        if(!$THIS.settings.objForm.length){
                                            throw "[FormFiller.__construct] L'oggetto objForm non è presente nella pagina.";
                                        };

                                        $THIS.loadInputs().listenInputs();
                                        
                                    }

            ,loadInputs         : function(){
                                        try {
                                            $THIS.formInputs = $THIS.settings.objForm instanceof jQuery ? $THIS.settings.objForm[0].elements : $THIS.settings.objForm.elements;
                                            return $THIS;
                                        } catch(e){
                                            console.debug(e);
                                            return $THIS;
                                        }
                                    }
            
            ,listenInputs       : function(){
                                        if($THIS.formInputs && $THIS.settings.autoRegisterMode == 1){
                                            $($THIS.formInputs).on('change',function(e){

                                                if($THIS.blocco){
                                                    return false;
                                                }
                                                switch(this.type.toLowerCase()){
                                                    case 'fieldset':
                                                        return false;
                                                        break;
                                                }

                                                $THIS.blocco = 1;

                                                $THIS.trigger = this.name;
                                                $THIS.sourceObj = $(this);
                                                switch($THIS.settings.formRegisterMode){
                                                    case 0:
                                                        $THIS.registerAllValues(); 
                                                        break;
                                                    default:
                                                        $THIS.registerValue(this); 
                                                }
                                                
                                            })
                                        }


                                    }

            ,registerValue      : function($input){
                                        if(!$input){
                                            throw "[FormFiller.registerValue] Manca l'input di cui registrare il valore";
                                        }
                                        var field_type = $input.type.toLowerCase();
                                        switch (field_type) {
                                            case "checkbox":
                                            case "radio":
                                            case "select":
                                                if(e.type == 'change'){ 
                                                    $THIS.sendData($input.name+'='+$input.value);
                                                }
                                                break;
                                            case "file": 
                                            case "password": 
                                                break;
                                            default:
                                                if(e.type == 'blur'){ 
                                                    $THIS.sendData($input.name+'='+$input.value);
                                                }
                                                break;
                                        }

                                        $THIS.blocco = 0;
                                        
                                    }

            ,registerAllValues  : function(){
                                        var sendData = $THIS.settings.objForm instanceof jQuery ? $THIS.settings.objForm.serialize() : $($THIS.settings.objForm).serialize();
                                        $THIS.sendData(sendData);
                                        $THIS.blocco = 0;
                                    }

            ,sendData           : function($formData){

                                        if(!($formData && $formData.length)){
                                            throw "[FormFiller.sendData] Nessun dato da inviare";
                                        };

                                        $(document).trigger('form_filler_start',$THIS.trigger);

                                        $.ajax({
                                            url         : $THIS.settings.urlDataRegister
                                            ,data       : $formData
                                            ,type       : 'POST'
                                            ,dataType   : 'json'
                                            ,success    : function(r){
                                                $THIS.blocco = 0;
                                                $(document).trigger('form_filler_success',[$THIS.trigger,$THIS.sourceObj]);
                                            }
                                            ,error      : function(a,b,c){
                                                $THIS.blocco = 0;
                                                $(document).trigger('form_filler_error',[$THIS.trigger,$THIS.sourceObj]);
                                                if(console){
                                                    console.debug(a,b,c);
                                                }
                                            }
                                        });
                                    }

        }

    $THIS = o;
    o.__construct();
    return o;
    
}