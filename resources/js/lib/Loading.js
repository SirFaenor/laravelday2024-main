/**
 * @author Jacopo Viscuso
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2019-03-14
 */

/**
 * BarPageTransition class
 * 
 * Gestisce l'effetto di transizione pagina tramite la comparsa di barre come http://eij.ca/en/case-studies
 * 
 * Dipendenze
 * necessita di un css il cui url va impostato con un parametro
 * 
 */


var Loading = function(params){
    
    var $THIS
        ,o = {
            settings        : $.extend({
                                    animationDuration: 1000									// durata dell'animazione di ingresso
                                },params)

            ,loadingObj     : null

            /**
             * Verifico l'esistenza di tutti gli elementi necessari al funzionamento della classe
             */
            ,__construct    : function(){

                                    $THIS.loadingObj = document.createElement('div');
                                    $THIS.loadingObj.className = 'loading_popup loading';
                                    $THIS.loadingObj.style.width    = '100%';
                                    $THIS.loadingObj.style.height   = '100%';
                                    $THIS.loadingObj.style.display  = 'none';
                                    $THIS.loadingObj.style.position = 'fixed';
                                    $THIS.loadingObj.style.top      = '0px';
                                    $THIS.loadingObj.style.left     = '0px';
                                    $THIS.loadingObj.style.zIndex   = '1000';
                                    $THIS.loadingObj.style.opacity  = '0';
                                    $THIS.loadingObj.style.transition="opacity "+($THIS.settings.animationDuration/1000)+"s";

                                    var theBody = document.querySelector('body');
                                    theBody.insertBefore($THIS.loadingObj, theBody.firstChild);

            					}


           	,transitionIn		: function(callback){

                                    $THIS.loadingObj.style.display = 'block';
                                    $THIS.loadingObj.style.opacity = '.9';
                                    if(callback){
                                        setTimeout(function(){
                                            callback();
                                        },$THIS.settings.animationInDuration);
                                    };


           						}

           	,transitionOut		: function(callback){

                                    $THIS.loadingObj.style.opacity = '0';
                                    setTimeout(function(){
                                        $THIS.loadingObj.style.display = 'none';
                                        if(callback){ callback(); }
                                    },$THIS.settings.animationDuration);

           						}

        };

    $THIS = o;
    o.__construct();

    return o;
};