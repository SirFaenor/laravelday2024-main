<?php
/**
 * Output dettaglio ordine
 * Viene usato da ordine completato, da pagina dettaglio ordine
 * e da OrderNotificationRoutine per costruire mail di notifica
 */

use Custom\Ecommerce\Order;

?>
<div id="order_detail_widget">     

        <div class="recap_section">
            <header class="recap_header">                
                <h3 class="order_title">
                    <?php echo $App->Lang->returnT('codice_ordine') ?>
                </h3>
            </header>
            <p>
                <?php echo $Order->ordine_code; ?>
            </p>
            <p class="margin-top">
                <img src="<?php echo $App->create("qrEndpoint", $Order);?>" alt="Qr code">
            </p>
        </div>

        <div class="recap_section">
            <header class="recap_header">                
                <h3 class="order_title">
                    <?php echo $App->Lang->returnT('data_ordine') ?>
                </h3>
            </header>
            <p>
                <?php 
                    echo $Order->getConfirmDate('d.m.Y H:i:s');
                ?>
                <br>
                (Scadenza : <?php echo $Order->getExpirationDate('d.m.Y H:i:s'); ?>)
            </p>
        </div>

        <div class="recap_section">
            <header class="recap_header">
                <h3 class="cap_text"><?php $App->Lang->echoT('your_data'); ?></h3>
            </header>
            <p>
                <?php 
                    echo    $Order->getUserData()['nome'].' '.$Order->getUserData()['cognome'].' <br>
                            '.$Order->getUserData()['email'].' <br>
                            '.$Order->getUserData()['telefono'].PHP_EOL;
                ?>
            </p>
        </div>
                
        <!-- <div class="recap_section -top">
            <header class="recap_header">
                <h3 class="cap_text"><?php $App->Lang->echoT('dati_spedizione'); ?></h3>
            </header>
            <p>
                <?php 
                    echo    $Order->getExpedition()['nome'].' '.$Order->getExpedition()['cognome'].' <br>
                            '.$Order->getExpedition()['indirizzo'].', '.$Order->getExpedition()['civico'].'<br>
                            '.$Order->getExpedition()['cap'].' '.$Order->getExpedition()['citta'].' ('.$Order->getExpedition()['sigla_provincia'].') <br>
                            '.$Order->getExpedition()['nazione'].' <br>
                            '.$Order->getExpedition()['telefono'].PHP_EOL;
                ?>
            </p>
            <?php 
                if($Order->getUserData()['company_invoice_request'] == 'Y'):
                    echo '  <p>
                                '.$App->Lang->returnT('label_azienda').': '.$Order->getUserData()['ragione_sociale'].' <br>
                                '.$App->Lang->returnT('label_piva').': '.$Order->getUserData()['piva'].' <br>
                                '.$App->Lang->returnT('label_sdi').': '.$Order->getUserData()['sdi'].' <br>
                                '.$App->Lang->returnT('label_pec').': '.$Order->getUserData()['pec'].'
                            </p>'.PHP_EOL;
                endif;
            ?>
        </div> -->
        <?php 
            if($Order->getnote()):
                echo '      <div class="recap_section -top">
                                <header class="recap_header">
                                    <h3 class="cap_text">'.$App->Lang->returnT('label_note').'</h3>
                                </header>
                                <p>'.nl2br($Order->getnote()).'</p>
                            </div>'.PHP_EOL;
            endif;
        ?>
        <div class="recap_section">
            <header class="recap_header">
                <h3 class="cap_text"><?php $App->Lang->echoT('pagamento'); ?></h3>
            </header>
            <p>
                <?php echo $Order->getPayment()['title']; ?>
            </p>
        </div>
         
        <div class="recap_section">
            <header class="recap_header">
                <h3 class="cap_text"><?php $App->Lang->echoT('purchased_items'); ?></h3>
            </header>
            <div class="recap_section-items">
<?php
        $ProductsRepository = $App->create('ProductsRepository');
        foreach($Order->getItems() as $PRT):

            $Product = $ProductsRepository->loadById($PRT['id_item']);
            

            if($Product->subtitle):
                $product_subtitle = '<p class="item_info ">'.$Product->subtitle.'</p>';
            else:
                $arProductSubtitle = [];
                $product_subtitle = $arProductSubtitle 
                                        ? '<p class="item_info ">'.implode(' &ndash; ',$arProductSubtitle).'</p>'
                                        : '';
            endif;
            echo '          <div class="cart_item">
                                
                                    <div class="qta">'.$PRT['quantita'].' x</div> 
                                    <div class="title">
                                        <p class="item_name">'.$Product->title.'</p>
                                        '.$product_subtitle.'
                                    </div>
                                    <p class="price">
                                        '.($PRT['prezzo2'] < $PRT['prezzo'] ? '<del>'.$Product->currency->numberToCurrency($PRT['prezzo']).'</del>' : '').'
                                        '.$App->CalculatorService->formatPrice($PRT['prezzo2']).'
                                    </p>

                            </div>'.PHP_EOL;

    endforeach;
?>
            </div>
        </div>

        <div class="recap_section">
            <header class="recap_header">
                <h3><?php $App->Lang->echoT('subtotal'); ?></h3>
            </header>
            <div class="recap_section-items">
                <div class="cart_item">
                    <div class="qta"> </div> 
                    <div class="title"> </div> 
                    <div class="price"><?php echo $App->Currency->print($Order->totale) ?> </div> 
                </div>
            </div>
        </div>

        <div class="recap_section">
            <header class="recap_header">
                <h3><?php $App->Lang->echoT('stato_ordine'); ?></h3>
            </header>

            <p><strong><?php echo $App->Lang->returnT("label_order_status_".$Order->stato) ?></strong></p>

            <p class="margin-top">
            <?php
                /**
                 * Valorizzare la variaible $refundLink nello script controller
                 */
                if($Order->refundRequestEnabled() == true && !empty($refundLink)) {
            ?>
                <a class="btn rounded yellow" href="<?php echo $refundLink ?>"><?php $App->Lang->echoT("richiedi_rimborso") ?></a>
            <?php
                } else {
                    //$App->Lang->echoT("order_refund_disabled");
                }
            ?>
            </p>
        </div>

</div>
<style type="text/css">
        #order_detail_widget {
            padding: 5vw 0;
            font-size: 16px;
            text-align: left;
         }
        
        #order_detail_widget .recap_section {
            line-height: 1.5;
        }
        #order_detail_widget .recap_section + .recap_section {margin-top: 50px;}
        
        #order_detail_widget .recap_section .recap_header {
            border-bottom: 1px solid #323232;
            margin-bottom: 1em;
        }
        
        
        #order_detail_widget .recap_section .recap_header h3 {
            margin: 0;
            font-size: 1.25em;
            color: #323232;
        }
        
        #order_detail_widget .recap_section p {margin: 0; font-size: 1em;}
        
        #order_detail_widget .recap_section-items .cart_item {
            display: flex;
        }
        
        #order_detail_widget .recap_section-items .cart_item + .cart_item {margin-top: 1em;}
        
        #order_detail_widget .recap_section-items .cart_item > * {
            padding: 0 1em;
        }
        #order_detail_widget .recap_section-items .cart_item .qta {width: 15%;}
        #order_detail_widget .recap_section-items .cart_item .title {width: 75%;}
        #order_detail_widget .recap_section-items .cart_item .price {width: 20%; text-align: right;}
        
        #order_detail_widget .recap_section #cart_subsection_subtotal {
            border-bottom: 0
        }

        #order_detail_widget p.margin-top {margin-top: 2em;}
        
        
</style>