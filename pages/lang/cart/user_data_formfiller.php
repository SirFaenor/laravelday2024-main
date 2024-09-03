<?php
$App->Lang->loadTrads("products_global,cart_global,cart_detail,form,cart_user_data");

$response = array('result' => 0, 'msg' => '');

// di base imposto il valore dei dati custom   su no
$App->Cart->setCustomData('ordine_spedizione','custom_data','N');
$App->Cart->setCustomData('ordine_cliente','company_invoice_request','N');
$_SESSION['form_filler']['cart_user_data']['spedizione_custom_data'] = 'N';
$_SESSION['form_filler']['cart_user_data']['company_invoice_request'] = 'N';
$_SESSION['form_filler']['cart_user_data']['takeaway'] = 'N';
$_SESSION['form_filler']['cart_user_data']['marketing_checkbox'] = 'N';
$_SESSION['form_filler']['cart_user_data']['privacy'] = 'N';
$_SESSION['form_filler']['cart_user_data']['alcohol'] = 'N';
if(isset($_POST) && count($_POST) > 0):
    foreach ($_POST as $name => $value):
        $_SESSION['form_filler']['cart_user_data'][$name] = $value;
                
        switch($name):
            case 'id_provincia':
                $App->Cart->setCustomData('ordine_cliente','id_provincia',$value);
                break;
            case 'id_nazione':
                $App->Cart->setCustomData('ordine_cliente','id_nazione',$value);
                break;
            case 'spedizione_custom_data':
                $App->Cart->setCustomData('ordine_spedizione','custom_data',$value);

                # se sto attivando la spedizione custom imposto di base la spedizione sulla nazione di fatturazione
                if($value == 'Y'):
                    $App->Cart->setCustomData('ordine_cliente','sped_id_nazione',$App->Cart->getCustomData('ordine_cliente','id_nazione'));
                    $App->Cart->setCustomData('ordine_cliente','sped_id_provincia',$App->Cart->getCustomData('ordine_cliente','id_provincia'));
                else:
                    $App->Cart->setCustomData('ordine_cliente','sped_id_nazione',NULL);
                    $App->Cart->setCustomData('ordine_cliente','sped_id_provincia',NULL);
                endif;
                break;
            case 'company_invoice_request':
                $App->Cart->setCustomData('ordine_cliente','company_invoice_request',$value);
                break;
            case 'sped_id_provincia':
                $App->Cart->setCustomData('ordine_spedizione','id_provincia',$value);
                break;
            case 'sped_id_nazione':
                $App->Cart->setCustomData('ordine_spedizione','id_nazione',$value);
                break;
            case 'shipping_address_id':
                $App->Cart->setCustomData('ordine_spedizione','id_address',$value);
                break;
        endswitch;

    endforeach;
    $response = array('result' => 1, 'msg' => 'Dati impostati correttamente');
else:
    $response['msg'] = 'Nessun dato inviato';
endif;

echo json_encode($response);
exit;


