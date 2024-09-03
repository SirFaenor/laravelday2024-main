<?php
/**
 * Verifica store abilitato
 */

/**
 * Recupero i prodotti da mostrare
 */
$ProductsRepository = $App->create('ProductsRepository');
$arProductsToShow = $ProductsRepository->loadInCartDetail([], $App->Lang->lgId);
$arGroupedByCat = array();


/**
 * Divido i prodotti in categorie
 */
if($arProductsToShow):
    $cats = $App->create("ProductCatsRepository")->loadBySql("AND T.id_item_sup <> 0 AND TL.lang = ? ORDER BY T.position ASC", [$App->Lang->lgId]);
    if($cats) {
        foreach ($cats as $key => $cat) {
    
            $arGroupedByCat[$cat->id] = array(
                'info'   => array('title' => $cat->title)
                ,'items' => array()
            );
    
            foreach($arProductsToShow as $P):
                if($P->catId != $cat->id):
                    continue;
                endif;
    
                $arGroupedByCat[$P->catId]['items'][] = $P;
            endforeach;
        }
    }
endif;


/**
 * Ricezione "ok procedi" dal layer mobile di spiegazione 
 * della procedura.
 * Recupero la sessione corrente, se ho appena accettato la valorizzo a 1
 */
$modal_intro_text_accepted = !empty($_SESSION['modal_intro_text_accepted']) ? $_SESSION['modal_intro_text_accepted'] : 0;
if (!empty($_GET['modal_intro_text_accepted']) && $_GET['modal_intro_text_accepted'] == 1) {
    $modal_intro_text_accepted = 1;
}
$_SESSION['modal_intro_text_accepted'] = $modal_intro_text_accepted;


/**
 * Pagina riepilogo carrello
 */
require(ASSETS_PATH.'/html/common.php');
$App->Lang->setActive("cart_detail");
$App->Lang->loadTrads("products_global,cart_global,cart_detail");

$App->Page->title($App->Lang->returnT("meta_title"));
$App->Page->description($App->Lang->returnT("meta_description"));
$App->Page->includeCss(BASE_URL.'/css/build/cart.css?v='.time());
$App->Page->alternates($App->Lang->getAllAlternates());

$App->Page->addJS(<<<JAVASCRIPT
    $(document).ready(function(){


        /* auto update al cambio quantità
        ********************************************************************/
        var t = new Number();
        
        // "cart_changed" lanciato anche da pulsanti spinner in Cart.js
        $(this).on('cart_changed',function(e,obj){
            clearTimeout(t);

            $(obj).parents(".input_widget").addClass("loading");

            t = setTimeout(function(){
                $('#update_cart_prods').trigger('click');
            }, 200);
        });

        $('#page_content').on('blur','.qta_pr',function(){
        
            if ($(this).val() == $(this).data("current-value") ) {return;}
            
            $(this).data("current-value", $(this).val());

            let widget = $(this);
            
            $(document).trigger('cart_changed', widget);
        
        });

        $('#page_content').on('keyup','.qta_pr',function(){
            if ($(this).val() == $(this).data("current-value") ) {return;}            
            $(this).data("current-value", $(this).val());
            let widget = $(this);

            clearTimeout(t);
            t = setTimeout(function(){
                $(document).trigger('cart_changed', widget);
            },500);
        });


        /* cambio il tempo di reload
        ********************************************************************/
        window.Cart.settings.reload_time_ok = 3000;
        
        // integrazione tab per cambio categorie mobile
        $("#cart_table_tab_switcher li label").eq(0).addClass("active");
        $("#cart_table_tab_switcher li label").on("click", function() {
            $("#cart_table_tab_switcher label.active").removeClass("active");
            $(this).addClass("active");
        });

        $("#cart_table_tab_switcher").sticky({topSpacing:0});

    });


    
JAVASCRIPT
);

$App->Page->addClass('page_cart page_cart_detail negative order_available_'.(int)STORE_AVAILABLE);
$App->Page->open();
?>
<div id="main_wrapper">

<?php require ASSETS_PATH.'/html/page_header.php'; ?>
<main id="page_content">
    <section id="intro_section" data-animscroll data-as-delay="200ms" data-as-animation="fadeIn">
        
<?php
/**
 * Diff. contenuti a seconda di ordine attivo o meno
 */
if(STORE_AVAILABLE === true) :
?>
        <h1 class="page_title center_width"><?php $App->Lang->echoT('page_title'); ?></h1>
        <p class="intro_text center_width s">
            <?php $App->Lang->echoT('page_intro_1'); ?><br>
            <?php $App->Lang->echoT('page_intro_2'); ?><br>
            <?php $App->Lang->echoT('page_intro_3'); ?><br>
            <a href="<?php echo $App->Lang->returnL('advices') ?>"><?php echo $App->Lang->returnT('page_intro_advices_link') ?> &Gt;</a>
        </p>

        <ul class="order_steps reset">
            <li class="active"><span><?php $App->Lang->echoT('order_steps_order_composition'); ?></span></li>
            <li><span><?php $App->Lang->echoT('order_steps_insert_data'); ?></span></li>
            <li><span><?php $App->Lang->echoT('grazie'); ?></span></li>
        </ul>
<?php
else :
?>
        <!-- <p class="center_width s"><?php $App->Lang->echoT('page_intro_order_unavailable'); ?></p> -->
        <p class="center_width s">
            <strong>
            <?php echo $App->Lang->lgId == 1 ? 'Ordine online disabilitato. Si prega di ordinare in cassa.<br> Grazie.' : 'Online order is disabled. Please order at the desk.<br> Thank you.' ?>
            </strong>
        </p>
<?php
endif;
?>

    </section>
    <div class="content_section center_width w">
        <div id="cart_wrapper" class="clearfix">
<?php
/**
 * Diff. contenuti a seconda di ordine attivo o meno
 */
if(STORE_AVAILABLE === true) :
?>            
            <p class="center"><?php $App->Lang->echoT('cart_intro'); ?></p>
<?php
endif;


if($arGroupedByCat) :
?>

            <ul id="cart_table_tab_switcher">
<?php
foreach($arGroupedByCat as $catId => $data):
?>
                <li><label for="cart_tab_<?php echo $catId ?>" class="btn red"><?php echo $data['info']['title'] ?></label></li>
<?php
endforeach;
?>
           </ul>
<?php
endif;
?>


            <div class="refresh">
<?php
if(count($arGroupedByCat) > 0):
    /**
     * Elenco prodotti
     */
    $prod_subtotal = 0;
    echo '      <form id="cart_update" class="clearfix" action="'.$App->Lang->returnL('cart_add').'" autocomplete="off" novalidate data-reload-time="1000">
                    '.PHP_EOL;

    /* ELENCO PRODOTTI
    ***********************************************************************************************/
    $c = 0;
    foreach($arGroupedByCat as $catId => $data):
        echo '          
                        <input type="radio" name="cart_tab" id="cart_tab_'.$catId.'" value="'.$catId.'" '.(++$c == 1 ? 'checked' : '').'>
                        <div class="cart_table_tab"><table class="cart_table">
                            <thead>
                                <tr>
                                    <th class="title">'.$data['info']['title'].'</th>
                                    <th class="price">'.$App->Lang->returnT('prezzo').'</tH>
                                    <th class="qta">'.$App->Lang->returnT('quantita').'</tH>
                                    <th class="subtotal">'.$App->Lang->returnT('subtotal').'</tH>
                                </tr>
                            </thead>
                            <tbody>
                            '.PHP_EOL;
        
        foreach($data['items'] as $Item):
            $item_id = 'pr_'.$Item->id;
            $is_in_cart = $App->Cart->getItem($item_id);

            /**
             * Se è esaurito, inserisco nota e lo rimuovo dal carrello, se c'è
             */
            $sellableNote = '';
            if(!$Item->isSellable()) {
                $sellableNote = $App->Lang->returnT("label_esaurito");
                if($is_in_cart) {$App->Cart->removeItem($item_id);}
            }

            
            echo '              <tr>
                                    <td class="title">
                                        '.$Item->title.' 
                                        '.($Item->subtitle ? '<br><small>'.$Item->subtitle.'</small>' : '').'
                                        '.($sellableNote ? '<br><strong>'.$sellableNote.'</strong>' : '').'
                                    </td>
                                    <td class="price">
                                        '.$Item->getPrice().'
                                    </td>
                                    <td class="qta">
                                        '.(
                                            STORE_AVAILABLE === true && $Item->isSellable()
                                            ?
                                        '<div class="input_widget input_number_widget">
                                            <span class="spinner sum" data-role="ecomm_btn" data-type="spinner">+</span>
                                            <span class="spinner subtract" data-role="ecomm_btn" data-type="spinner">-</span>
                                            <input type="number" class="qta_pr'.($is_in_cart ? ' has_value' : '').'" 
                                                name="qta['.$item_id.']" id="qta_'.$item_id.'" value="'.($is_in_cart ? $is_in_cart['qta'] : '0').'" 
                                                '.($is_in_cart ? ' data-current_value="'.$is_in_cart['qta'].'"' : '').'
                                                required aria-required="true" min="0" onclick="this.select()"
                                                >
                                        </div>' 
                                        :
                                        '-'
                                        ).'
                                    </td>
                                    <td class="subtotal">
                                        '.($is_in_cart ? $App->Currency->print((float)$is_in_cart['subtotal']) : '').'
                                        <input type="hidden" name="is_update['.$item_id.']" value="'.($is_in_cart ? 1 : 0).'">
                                        <input type="hidden" name="removeOnZero['.$item_id.']" value="1">
                                    </td>
                                </tr>'.PHP_EOL;

        endforeach;
        echo '              </tbody>
                        </table></div>'.PHP_EOL;
    endforeach;
    echo '              <button id="update_cart_prods" class="btn rounded yellow update_cart" type="submit" data-role="ecomm_btn" data-type="submit">
                            '.$App->Lang->returnT('update_cart').'
                        </button>
                </form>'.PHP_EOL;
    ?>


    <!--  
    **********************************************

    CODICI SCONTO E RIEPILOGO SPESE

    **********************************************
    -->
    <?php


    $discount_costs = '';
    if(STORE_AVAILABLE && $App->User->allowed('USA_CODICE_SCONTO') && !$Discount):
        # form per il codice sconto
        $discount_costs .= '    <form id="check_discount" action="'.$App->Lang->returnL("cart_discount_code_add").'" autocomplete="off" novalidate>
                                    <div class="codice_sconto_area">
                                        
                                        <label for="discount_code" id="discount_code_label" class="w100">'.$App->Lang->returnT('discount_code_intro').'</label> 
                                        
                                        <div>
                                            <input type="text" name="discount_code" id="discount_code" required aria-required="true">
                                            <button id="check_code" type="submit" class="btn discount_code_submit" data-role="ecomm_btn" data-type="submit">
                                                '.$App->Lang->returnT("applica").'
                                            </button>
                                        </div>
                                    </div>
                                </form>'.PHP_EOL;
    endif;

    $other_costs = '';
    
    # CODICE SCONTO
    if(STORE_AVAILABLE && $Discount = $App->Cart->getDiscount()):
        echo '          <div class="moreinfo_box">'.PHP_EOL;
        $partial_use = $Discount['valore_sconto'] == 'E' && $Discount['importo_sconto'] > $Discount['importo_finale'] ? true : false;
        $other_costs .= '           <p class="codice_sconto cart_prices clearfix">
                                        <span class="title">
                                            '.$App->Lang->returnT('discount_code').': 
                                            <strong>
                                                <span class="info_btn has_tooltip">
                                                    <abbr title="'.$App->Lang->returnT('informations').'">i</abbr>
                                                    <span class="tooltip">'.$Discount['info'].'</span>
                                                </span>
                                                '.$Discount['codice'].' 
                                                <small>(<a class="remove" data-role="ecomm_btn" href="'.$App->Lang->returnL("cart_discount_code_remove").'">'.$App->Lang->returnT("remove").'</a>)</small>
                                            </strong>
                                        </span>
                                        <span class="price'.($partial_use === true ? ' warning has_tooltip right' : '').'">- '.$App->Currency->print($Discount['importo_finale']).($partial_use === true ? '<span class="custom_tooltip">'.$App->Lang->returnT('codice_sconto_partial_use_diclaimer',array('used' => $App->Currency->print($Discount['importo_finale']),'max_amount' => $App->Currency->print($Discount['importo_sconto']))).'</span>' : '').'</span>
                                    </p>'.PHP_EOL;
    echo '</div>';
    endif;

    $other_costs = strlen($other_costs) ? '<div class="moreinfo_box">'.PHP_EOL.$other_costs.PHP_EOL.'</div>' : '';

    if(strlen($discount_costs.$other_costs)):
        echo '  <div class="moreinfo_box other_costs clearfix">'.PHP_EOL.$discount_costs.PHP_EOL.$other_costs.'</div>'.PHP_EOL;
    endif;


/**
 * Diff. contenuti a seconda di ordine attivo o meno
 */
if(STORE_AVAILABLE === true) :
?>          
    <div class="clearfix cart_subsection">
        <div class="cart_total_widget">
            <p class="total_amount">   
                <span class="title">
                    <?php echo $App->Lang->returnT('subtotal') ?> 
                    <?php
                    if($App->User->allowed('IVA_INCLUSA')) :
                    ?>
                    <small>( <?php $App->Lang->echoT('iva_inclusa',array('iva' => '')) ?>)</small>
                    <?php 
                    endif;
                    ?>
                </span>
                <span class="price"> <?php echo $App->Currency->print($App->Cart->getTotalAmount()) ?></span>
            </p> 
            <?php
            $allowedToCheckoutMsg = $App->Cart->allowedToCheckout();
            if($allowedToCheckoutMsg === true):
            ?>
            <a class="next btn rounded yellow" href="<?php echo $App->Lang->returnL('cart_user_data') ?>">
                <span> <?php echo $App->Lang->returnT('proceed') ?></span>
            </a>
            <?php
            else :
            ?>
            <p class="center cart_disclaimer"> <?php echo $allowedToCheckoutMsg ?></p>
            <?php
            endif;
            ?>
        </div>
    </div>
<?php
endif;

endif; // se prodotti
?>
            </div>
        </div>
    </div> <!-- refresh -->
	</main><!-- #page_content -->
	<?php require ASSETS_PATH.'/html/page_footer.php'; ?>

</div> <!-- #main_wrapper -->


<?php
/**
 * Modal readme 
 */
if(STORE_AVAILABLE === true) :
?>
    <input type="hidden" type="checkbox" class="modal_intro_text_toggler <?= !empty($_SESSION['modal_intro_text_accepted']) ? 'checked' : '' ?>" name="modal_intro_text_toggler" autocomplete="off" >
    <div class="modal_intro_text">
        <div class="center_width">
            <p class="title page_title"><?php $App->Lang->echoT('come_funziona'); ?></p>        
            <div>
                <p class="step"><span class="number">1</span><span class="txt"><?php $App->Lang->echoT('page_intro_1'); ?></span></p>
                <p class="step"><span class="number">2</span><span class="txt"><?php $App->Lang->echoT('page_intro_2'); ?></span></p>
                <p class="step"><span class="number">3</span><span class="txt"><?php $App->Lang->echoT('page_intro_3'); ?></span></p>
            </div>

            <form action="<?php $App->Lang->echoL('cart_detail'); ?>" method="get">
                <input type="hidden" name="modal_intro_text_accepted" value="1">
                <input type="submit" class="btn rounded yellow" value="<?php $App->Lang->echoT('page_intro_confirm'); ?>">
            </form>
        </div>
    </div>
<?php
endif;
?>
<?php
$App->Page->close(); // chiude body e html
