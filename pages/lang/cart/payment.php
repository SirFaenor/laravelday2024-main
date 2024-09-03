<?php 
/**
 * Imposta il metodo di pagamento in base alla selezione dell'utente
 */
$App->Lang->loadTrads("cart_global,form,cart_user_data");
$App->BrowserQueue->noSave();

$response   = array(
                'result' => 0
                ,'msg' => ''
                ,"needsreload"  => false
            );
$is_ajax    = !empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1 ? true : false;
$code       = !empty($_GET['payment']) ? $App->FixString->fix($_GET['payment']) : NULL;


/**
 * Impostazione pagamento
 */
$r = $App->Cart->selectPaymentMethod($code);
$response['result'] = $r ? 1 : 0;
$response['msg'] = $response['result'] == 0
                    ? '<strong>'.$App->Lang->returnT('warning').'</strong><br> '.$App->Lang->returnT('update_cart_error')
                    : $App->Lang->returnT('pagamento_change_ok');


/**
 * risposta ajax
 */
if($is_ajax === true):
    echo json_encode($response);
    exit;
endif;


/**
 * risposta normale
 */
if($response['result'] == 0):
    throw new \AtrioTeam\Box\Exceptions\BadRequestException($response['msg']);
endif;

$redirect = $App->Lang->returnL('cart_detail');
header('Location: '.$redirect);
exit;
