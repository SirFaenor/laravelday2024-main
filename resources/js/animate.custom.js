
$(function() {
    
    /**
     * Animazione elementi pagina
     * attributi disponibili per comportmenti custom
     * data-as-animation: tipo di animazione (v. animate.css)
     * data-as-delay: ritardo di esecuzione
     * data-as-offset: offset per scatenare l'animazione (v. docs waypoint.js)
     * data-as-duration: durata dell'animazione
     *
     * @docs per offset http://imakewebthings.com/waypoints/api/offset-option/
     * @docs per animazioni https://daneden.github.io/animate.css/
     */

    var status = null;
    var opacity = 1;
    var delay = 0;
    var duration = 0;
    var options =  {};
    $(window).on('resize',function(){
        var new_status = window.windowWidth < 768 ? 'mobile' : 'desktop';
        if(status != new_status){
            status = new_status;
            $(document).trigger('animation_status_changed');
        }
    });


    $(document).trigger('animation_status_changed');

    $(document).on('animation_status_changed',function(){

        if(Modernizr.cssanimations && Modernizr.csstransforms && Modernizr.opacity && Modernizr.csstransitions){    // se supporto le animazioni, le trasformazioni, l'opacità e le transizioni allora mostro le animazioni
            $("[data-animscroll]").each(function() {
                if(!$(this).attr("data-as-animation")) {
                    $(this).attr("data-as-animation", "fadeInUp");
                };

                switch(status){
                    case 'desktop':
                        opacity = 0;
                        delay = $(this).attr("data-as-delay") ? $(this).attr("data-as-delay") : 0;
                        duration = $(this).attr("data-as-duration") ? $(this).attr("data-as-duration") : 500;
                        options = {
                                            offset      : ($(this).attr("data-as-offset") ? $(this).attr("data-as-offset") : '90%')
                                            ,handler    : function(direction) {
                                                $(this.element).addClass('animated '+$(this.element).attr('data-as-animation'));
                                            }
                                        };
                        break;

                    default:
                        opacity = 1;
                        delay = 0;
                        duration = 0;
                        options = {
                                            offset      : 0
                                            ,handler    : function(direction) {
                                                this.destroy();
                                            }
                                        };
                };

                $(this)
                    .css("opacity", opacity)
                    .css("animation-delay", delay)
                    .css("animation-duration", duration)
                    .waypoint(options);
            });
        }

    });


});

