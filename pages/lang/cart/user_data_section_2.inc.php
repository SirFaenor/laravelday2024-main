
<!-- RITIRO/SPEDIZIONE ORDINE -->


<!-- METODO PAGAMENTO -->


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
                        
                        <a rel="nofollow" target="_blank" href="#"><?php $App->Lang->echoT("terms") ?></a>
                        /
                        <a rel="nofollow" target="_blank" href="#"><?php $App->Lang->echoT("nav_privacy") ?></a>
                    </label>
                    <input <?php echo $Form->getValue("marketing_checkbox") == 'Y' ? 'checked' : '' ?> value="Y" form="user_form" type="checkbox" name="marketing_checkbox" id="marketing_checkbox" class="input_checkbox" aria-required="true" required data-input_label="<?php $App->Lang->echoT('label_marketing'); ?>"> 
                    <label class="label_checkbox required" for="marketing_checkbox">
                        <?php $App->Lang->echoT('privacy_vendita_disclaimer'); ?> 
                        <a rel="nofollow" target="_blank" href="#"><?php $App->Lang->echoT("label_marketing") ?></a>
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