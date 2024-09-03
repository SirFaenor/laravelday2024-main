<?php 
/**
 * SCRIPT PER LA GESTIONE DEL CAMBIO DEL METODO DI SPEDIZIONE
 */
$App->Lang->loadTrads("cart_global,form,cart_user_data");
$App->BrowserQueue->noSave();

$response   = array(
                'result' => 0
                ,'msg' => ''
                ,"needsreload"  => false // di default quando viene aggiornata la spedizione ricarico la pagina
            );
$is_ajax    = !empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1 ? true : false;
$code 		= !empty($_GET['expedition']) ? $_GET['expedition'] : NULL;


/**
 * Impostazione spedizione
 */
$r = $App->Cart->selectExpedition($code);
$response['result'] = $r ? 1 : 0;
$response['msg'] = $response['result'] == 0
                    ? '<strong>'.$App->Lang->returnT('warning').'</strong><br> '.$App->Lang->returnT('update_cart_error')
                    : $App->Lang->returnT('spedizione_change_ok');


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
