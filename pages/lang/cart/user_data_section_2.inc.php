
<!-- RITIRO/SPEDIZIONE ORDINE -->
<?php
$totExp = count($App->Cart->getExpeditions());
if($totExp > 1):
?>
        <form id="cart_subsection_shipment" class="cart_subsection uk-margin-large-bottom" action="<?php $App->Lang->echoL('cart_shipment'); ?>" autocomplete="off">

<?php

$title = $App->Lang->returnT('consegna');

echo '          <h3 class="cart_subsection_title">'.$title.'</h3>'.PHP_EOL;

foreach($App->Cart->getExpeditions() as $k => $E):

    echo '          <p id="item_select_expedition_'.$E['code'].'" class="item_select expedition item_select_total_'.$totExp.'">
                        <input type="radio" name="expedition" id="expedition_'.$E['code'].'"'.($E['code'] == $selectedExpedition['code'] ? ' checked' : '').' value="'.$E['code'].'">
                        <label class="title label_radio" for="expedition_'.$E['code'].'">
                            '.$E['title'].'
                            | <span class="price">'.($E['prezzo_cart'] <= 0 ? $App->Lang->returnT('gratis') : $App->FixNumber->numberToCurrency($E['prezzo_cart'])).'</span>
                        </label>
                        '.(
                            strlen($E['descr']) 
                            ? ' <span class="info_btn flip h small has_tooltip bottom">
                                    <abbr title="'.$App->Lang->returnT('informations').'">i</abbr>
                                    <span class="custom_tooltip">'.nl2br($E['descr']).'</span>
                                </span>'
                            : ''
                            ).'
                    </p>'.PHP_EOL;
endforeach;
    echo '          
                    <button id="update_cart_expedition" class="update_cart btn rounded yellow" type="submit" data-role="ecomm_btn" data-type="submit">
                        '.$App->Lang->returnT('update_cart').'
                    </button>'.PHP_EOL;
?>


        </form>

<?php 
endif; 
?>


<!-- METODO PAGAMENTO -->
<?php
$totPayment = count($App->Cart->getPaymentMethods());
if($totPayment > 1):
?>
        <form id="cart_subsection_payment" class="cart_subsection uk-margin-large-bottom" action="<?php $App->Lang->echoL('cart_payment'); ?>" autocomplete="off" data-reload-time="1500">

<?php 

$title = $totPayment > 1 ? $App->Lang->returnT('scegli_pagamento') : $App->Lang->returnT('pagamento');

echo '          <h3 class="cart_subsection_title">'.$title.'</h3>'.PHP_EOL;

foreach($App->Cart->getPaymentMethods() as $k => $P):
    $checked = $App->Cart->getSelectedPaymentMethod()['id'] == $P['id'] ? ' checked' : '';

echo '          <p id="item_select_payment_'.$P['code'].'" class="item_select payment item_select_total_'.$totPayment.($P['is_braintree'] === true ? ' braintree' : '').'">
                    <input type="radio" name="payment" id="payment_'.$P['code'].'"'.$checked.' value="'.$P['code'].'">
                    <label class="title label_radio" for="payment_'.$P['code'].'">
                        '.$P['title'].'
                        '.($P['prezzo'] != 0 ? ' | <span class="price">'.$App->FixNumber->numberToCurrency($P['prezzo']).'</span>' : '').'
                    </label>
                    '.(
                        strlen($P['descr']) 
                        ? ' <span class="info_btn flip h small"">
                                <abbr class="custom_tooltip" title="'.nl2br($P['descr']).'">i</abbr>
                            </span>'
                        : ''
                        ).'
                </p>'.PHP_EOL;

endforeach;
echo '          <button id="update_cart_payment" class="update_cart btn rounded yellow" type="submit" data-role="ecomm_btn" data-type="submit">
                    '.$App->Lang->returnT('update_cart').'
                </button>'.PHP_EOL;
?>
        </form>
<?php 
endif; 
?>


<!-- RIEPILOGO ORDINE -->
        <div class="cart_subsection" id="cart_subsection_summary">
            <h3 class="cart_subsection_title"><?php $App->Lang->echoT('cart_summary_list_title'); ?></h3>
            <!-- <button type="button" class="btn clicked" aria-controls="#cart_all_wrapper">
                <span><?php $App->Lang->echoT('cart_summary_list_title') ?></span>
            </button> -->

            <div id="cart_all_wrapper">
<!-- RIEPILOGO PRODOTTI -->
<?php 
        foreach($arGroupedByCat as $catId => $data):

            if(!$data["items"]) {
                continue;
            }

            echo '          <table class="cart_table">
                                <thead>
                                    <tr>
                                        <th class="title" colspan="2">'.$data['info']['title'].'</th>
                                        <th class="price">'.$App->Lang->returnT('prezzo').'</tH>
                                        <th class="subtotal">'.$App->Lang->returnT('subtotal').'</tH>
                                    </tr>
                                </thead>
                                <tbody>
                                '.PHP_EOL;
            
            foreach($data['items'] as $item):
                echo '              <tr>
                                        <td class="qta"><strong>'.$item['qta'].'</strong> x </td>
                                        <td class="title">
                                            '.$item["title"].' <br>
                                            </td>
                                        <td class="price">
                                            '.$App->Currency->print((float)$item["prezzo"]).'
                                        </td>
                                        <td class="subtotal">
                                            '.$App->Currency->print((float)$item["subtotal"]).'
                                        </td>
                                    </tr>'.PHP_EOL;
    
            endforeach;
            echo '              </tbody>
                            </table>'.PHP_EOL;
        endforeach;    

        echo '              <table class="cart_table">
                                <tbody>'.PHP_EOL;
        /**
         * totale iva
         */
        if (!$App->User->allowed("IVA_INCLUSA")) :
            echo '                  <tr class="cart_item">
                                        <td class="img_and_price" colspan="2">'.$App->Lang->returnT("vat",array('italy_only' => (!$App->User->allowed("CALC_IVA_IN_PRICE") ? ' - '.$App->Lang->returnT('italy_only') : ''))).'</td> 
                                        <td class="price">'.$App->FixNumber->numberToCurrency($App->Cart->getItemsTotalVatAmount()).'</td>
                                    </tr>'.PHP_EOL;
        endif;
    
        /**
         * spedizione
         */
        if($App->Cart->getSelectedExpedition()['needs_shipping'] == 'Y'):
            echo '                  <tr class="cart_item small">
                                        <td class="img_and_price" colspan="2">'.$App->Lang->returnT("shipping").': '.$selectedExpedition['title'].'</td>
                                        <td class="price">'.($selectedExpedition['prezzo_cart'] != 0 ? $App->FixNumber->numberToCurrency($selectedExpedition['prezzo_cart']).($selectedExpedition['valore'] == 'P' ? ' ('.preg_replace('/\.00$/','',$selectedExpedition['prezzo']).'%)' : '') : $App->Lang->returnT("gratis") ).'</td>
                                    </tr>'.PHP_EOL;
        endif;
        
        /**
         * sconto
         */
        if($Discount = $App->Cart->getDiscount()):
            echo '                  <tr class="cart_item small">
                                        <td class="img_and_price" colspan="2">'.$App->Lang->returnT('discount_code').': '.$Discount['codice'].' <small>(<a class="remove hover_und" data-role="ecomm_btn" href="'.$App->Lang->returnL("cart_discount_code_remove").'">'.$App->Lang->returnT("remove").'</a>)</small></td>
                                        <td class="price">- '.$App->FixNumber->numberToCurrency($Discount['importo_finale']).'</td>
                                    </tr>'.PHP_EOL;
        endif;
    
        
        /**
         * pagamento
         * (per ultimo, in quanto il prezzo viene calcolato sul totale ordine dopo aver applicato eventuale codice sconto)
         */
        if ($App->Cart->getSelectedPaymentMethod()['prezzo'] > 0) :
            echo '                  <tr class="cart_item small">
                                        <td class="img_and_price" colspan="2">'.$App->Lang->returnT("payment").': '.$App->Cart->getSelectedPaymentMethod()['title'].'</td>
                                        <td class="price">'.$App->FixNumber->numberToCurrency($App->Cart->getSelectedPaymentMethod()['prezzo']).'</td>
                                    </tr>'.PHP_EOL;
        endif;

        echo '                  </tbody>
                            </table>'.PHP_EOL;
?>
            </div>

            </div><!-- #cart_subsection_summary -->

            <div class="cart_subsection">
                <p class="privacy_info info">
                <input autocomplete="off" <?php echo $Form->getValue("privacy") == 'Y' ? 'checked' : '' ?> value="Y" form="user_form" type="checkbox" name="privacy" id="privacy_checkbox" class="input_checkbox" aria-required="true" required data-input_label="<?php $App->Lang->echoT('label_privacy'); ?> + <?php $App->Lang->echoT('terms'); ?>"> 
                    <label class="label_checkbox required" for="privacy_checkbox">
                        <?php $App->Lang->echoT('privacy_vendita_disclaimer'); ?> 
                        
                        <a rel="nofollow" target="_blank" href="<?php $App->Lang->echoL("terms") ?>"><?php $App->Lang->echoT("terms") ?></a>
                        /
                        <a rel="nofollow" target="_blank" href="<?php $App->Lang->echoL("privacy") ?>"><?php $App->Lang->echoT("nav_privacy") ?></a>
                    </label>
                    <?php echo $Form->getValue("marketing_checkbox") ?>
                    <input <?php echo $Form->getValue("marketing_checkbox") == 'Y' ? 'checked' : '' ?> value="Y" form="user_form" type="checkbox" name="marketing_checkbox" id="marketing_checkbox" class="input_checkbox" aria-required="true" required data-input_label="<?php $App->Lang->echoT('label_marketing'); ?>"> 
                    <label class="label_checkbox required" for="marketing_checkbox">
                        <?php $App->Lang->echoT('privacy_vendita_disclaimer'); ?> 
                        <a rel="nofollow" target="_blank" href="<?php $App->Lang->echoL("privacy") ?>"><?php $App->Lang->echoT("label_marketing") ?></a>
                    </label>
<?php
if($App->Cart->hasAlcohol()) :
?>
                    <br><br>
                    <input <?php echo $Form->getValue("alcohol") == 'Y' ? 'checked' : '' ?> value="Y" form="user_form" type="checkbox" name="alcohol" id="alcohol_checkbox" class="input_checkbox" aria-required="true" required data-input_label="<?php $App->Lang->echoT('label_alcohol'); ?>"> 
                    <label class="label_checkbox required" for="alcohol_checkbox">
                        <?php $App->Lang->echoT('label_alcohol'); ?> 
                    </label>

<?php
endif;
?>
                <div>
            </div>
            
            
            <div class="cart_subsection" id="cart_subsection_submit">
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
                        if($App->Cart->getSelectedPaymentMethod()["instant_payment"] == 'Y') {
                            $label = $App->Lang->returnT('proceed_payment');
                           
                        } else {
                            $label = $App->Lang->returnT('finalize_order');
                        }
                    ?>
                    <button class="next btn rounded yellow" id="go_to_payment" type="submit">
                        <span> <?php echo $label ?></span>
                    </button>
                    <div id="cart_js_required">
                        <?php $App->Lang->echoT("cart_js_required") ?>
                    </div>
                    <?php
                    else :
                    ?>
                    <p class="center cart_disclaimer"> <?php echo $allowedToCheckoutMsg ?></p>
                    <?php
                    endif;
                    ?>
                </div>
            </div>

</div>