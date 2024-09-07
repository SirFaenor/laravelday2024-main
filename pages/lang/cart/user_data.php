<?php
/**
 * Pagina riepilogo carrello
 */
require(HTML_INCLUDE_PATH.'/common.php');


/**
 * Check e redirect disponibilità carrello
 */
redirectStoreUnavailable();


if($App->Cart->countItems() <= 0):
    $App->redirect($App->Lang->returnL('cart_detail'),'302');
endif;

$App->Lang->setActive("cart_detail");
$App->Lang->loadTrads("products_global,cart_global,cart_detail,cart_user_data");


/**
 * Form
 */
$xml = $App->User->getid_cat() == 2  ? 'cart_user_profile_b2b_fields' : 'cart_user_profile_fields';
$Form = $App->create('Form',array('form_name' => 'cart_user_data','path_to_xml_file' => HTML_INCLUDE_PATH.'/xml/'.$xml.'.xml'));


/**
 * Precompilo form con eventuali dati preesistenti
 */
if(isset($_SESSION['form_filler']) && isset($_SESSION['form_filler']['cart_user_data']) && is_array($_SESSION['form_filler']['cart_user_data'])):
    foreach($_SESSION['form_filler']['cart_user_data'] as $ff_name => $ff_val):
        $Form->setValue($ff_name,$ff_val);
    endforeach;
endif;


/**
 * Divido i prodotti nel carrello in categorie
 */
$arGroupedByCat = [];
$cats = $App->create("ProductCatsRepository")->loadBySql("AND T.id_item_sup <> 0 AND TL.lang = ? ORDER BY T.position ASC", [$App->Lang->lgId]);
foreach ($cats as $key => $cat) {

    $arGroupedByCat[$cat->id] = array(
        'info'   => array('title' => $cat->title)
        ,'items' => array()
    );

    $showTableField = false;

    foreach($App->Cart->getItems() as $item):
       
        $P = $App->create("ProductsRepository")->loadById($item["id"]);

        // memorizzo l'appartenenza dei prodotti alla categoria pietanze 
        // per poi forzare campi aggiuntivi nel form di checkout
        if(in_array($P->catId, [1,2])) {
            $showTableField = true;
        }


        if($P->catId != $cat->id):
            continue;
        endif;

        $arGroupedByCat[$P->catId]['items'][] = $item;
    endforeach;
}


/**
 * HTML
 */
$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));
$App->Page->includeCss(BASE_URL.'/css/build/cart.css?v='.time());

$App->Page->alternates($App->Lang->getAllAlternates());

$App->Page->addJS(<<<JAVASCRIPT
    $(document).ready(function(){


        /**
         * eliminazione della situazione finale lasciata dall'utente (con layer bianco)   
         * se utente torna con il "back" del browser
         */
        $(window).on("pageshow", function() {
            LOADING.transitionOut();
        });


        /**
         * Form dati utente
         */
        window.UserForm = new FormClass({
            objForm         : $('#user_form')
            ,listenForSubmission : false
            ,tooltipDurationOnSuccess : null // null per evitare chiususa automatica del messaggio dopo invio positivo
            ,disableLoading : true //gestisco fuori
            ,showMessageOnSuccess : false //gestisco fuori
            ,validationType : 1 // server
            ,placeholderFallback: 1
        });
        UserForm.arMsgs = window.arTrads;


        /**
         * Evento finale di completamento di tutta la procedura
         * (lanciato per tutti i metodi di pagamento)
         */
        $(document).on("payment_completed", function() {
            window.location = '{$App->Lang->returnL('cart_order_prepare')}';
        });


        /**
         * GESTIONE SUBMIT DEL FORM
         */
        $(UserForm).on({
            // evento generato dopo che è stata chiamata la pagina action-parser, che ha validato il form
            // e ha registrato l'ordine salvandone l'id in sessione
            submit_completed    : function(e,msg,data){  
                       
                MESSENGER.setClass("success");
                MESSENGER.showMessenger(msg);


                /**
                 * pagamento normale, procedura già completata
                 */
                return setTimeout(function() {
                    $(document).trigger("payment_completed");
                }, 2000);

            }

            ,submit_processing  : function(e,data){

                LOADING.transitionIn(); // potrebbe già essere visibile se il submit avviene dopo procedure intermedie bt

                $('#go_to_payment').addClass('on_submit');
            }

            ,submit_error       : function(e,data){

                MESSENGER.setClass("error");
                MESSENGER.showMessenger(arTrads.msg_error);

                LOADING.transitionOut();

                $('#go_to_payment').removeClass('on_submit');

            }
        });


        /**
         * SUBMIT DEL FORM
         * al submit del form,se pagamento è braintree eseguo prima il tokenize
         */
        $(document).on("click", "#go_to_payment", function() {
            
            window.Cart.settings.reload_time_ok = null;
            

            /**
             * pagamento normale, invio form subito
             */
            UserForm.triggerSubmission();
            return;
        });

        
        /* salvataggio in automatico dei dati del form
        ********************************************************************/

        var FF = new FormFiller({
            'urlDataRegister'   : '{$App->Lang->returnL('cart_user_data_formfiller')}'
            ,'objForm'          : $('#user_form')
            ,'formRegisterMode' : 0
        });

        // nel caso delle province verifico la compatibilità con la spedizione
        var fft;
        $(document).on({
            form_filler_start       : function(e,data){
                                        $('#go_to_payment').addClass('no_submit');
                                    }
            ,form_filler_error      : function(e,data, sourceObj){
                                        $('#go_to_payment').removeClass('no_submit');
                                    }
            ,form_filler_success    : function(e,data, sourceObj){

                                        clearTimeout(fft);
                                        $('#go_to_payment').removeClass('no_submit');

                                        if (sourceObj.is(".form_reload_trigger")) {
                                            cartInfoUpdate({result: 1,msg: arTrads.data_updated})
                                        }

                                    }
        });


        /**
         * Auto update al cambio metodo di pagamento e spedizione.
         * Genera click su data-role="ecomm_btn" causando submit del form corrispondente
         */
        $(document).on('change', '.item_select input', function(){
            var n = this.name;
            window.Cart.settings.reload_time_ok = 0;
            $('#go_to_payment').addClass('no_submit');
            $('#update_cart_'+n).trigger('click');
        });

        /**
         * Mostro campo tavolo alla scelta asporto o meno
         * (mostro solo se NON asporto)
         */
        (function() {
            $(document).on('click', '#takeaway_checkbox_Y,#takeaway_checkbox_N', function(){
                if($(this).val() == 'N') {
                    $('#table_number_fieldset').slideDown();
                } else {
                    $('#table_number_fieldset').slideUp();
                }
            });

            $('input[name="takeaway"]:checked').trigger('click');
        })();


    });
JAVASCRIPT
);

$App->Page->addClass('page_cart page_cart_user_data negative');
$App->Page->open();
?>
<div id="main_wrapper">

<?php require HTML_INCLUDE_PATH.'/page_header.php'; ?>
<main id="page_content">
    <section id="intro_section" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
        <h1 class="page_title center_width"><?php $App->Lang->echoT('page_title'); ?></h1>
        <!-- <p class="intro_text center_width s"><?php $App->Lang->echoT('page_intro',array('more_info' => $App->Lang->returnL('advices'))); ?></p> -->
        <p class="intro_text center_width s">
            <?php $App->Lang->echoT('page_intro_1'); ?><br>
            <?php $App->Lang->echoT('page_intro_2'); ?><br>
            <?php $App->Lang->echoT('page_intro_3'); ?><br>
            <a href="<?php echo $App->Lang->returnL('advices') ?>"><?php echo $App->Lang->returnT('page_intro_advices_link') ?> &Gt;</a>
        </p>

        <ul class="order_steps reset">
            <li class="completed"><span><?php $App->Lang->echoT('order_steps_order_composition'); ?></span></li>
            <li class="active"><span><?php $App->Lang->echoT('order_steps_insert_data'); ?></span></li>
            <li><span><?php $App->Lang->echoT('grazie'); ?></span></li>
        </ul>
    </section>
    <div class="content_section center_width s">
        <div id="cart_wrapper" class="clearfix">
            <form id="user_form" class="default_form user_form_cat_<?php echo $App->User->getid_cat() ?>" action="<?php $App->Lang->echoL('cart_user_data_parser'); ?>" novalidate method="post" autocomplete="off">
                <?php $Form->csrf(); ?>
                
                <?php require __DIR__.'/user_data_section_1_cat_1.inc.php'; ?>

                <input type="hidden" name="id_cat" value="<?php echo $App->User->getid_cat(); ?>">
            </form>
            <div id="cart_recap" class="cart_section">

<!-- 
**********************************************

COLONNA LATERALE

**********************************************
-->
        
                <?php require __DIR__.'/user_data_section_2.inc.php'; ?>
            </div>

        </div> <!-- #cart_wrapper -->
    </div>

</main><!-- #page_content -->

<?php require HTML_INCLUDE_PATH.'/page_footer.php'; ?>

</div> <!-- #main_wrapper -->
<?php
$App->Page->close(); // chiude body e html
?>