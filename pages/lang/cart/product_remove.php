<?php 
$App->Lang->loadTrads("cart_global,form");
$App->BrowserQueue->noSave();


/**
 * Parametri
 */
$is_ajax    = !empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1 ? true : false;
$cart_id    =  $App->FixString->fix($_GET['cart_id']);
$response = array(
    'result' => 0
    ,'msg' => ''
    ,'itemscounter' => count($arCartItems)
    ,"needsreload"  => false
);


/**
 * Rimuovo prodotto
 */
$App->Cart->removeItem($cart_id);

$response['result'] = 1;
$response['msg'] = $App->Lang->returnT('cart_updated');
$response['itemscounter']--;


/**
 * Risposta ajax
 */
if($is_ajax === true):
    echo json_encode($response);
    exit;
endif;


/**
 * Chiamata normale, redirect
 */
if ($response["result"] == 0) {
    throw new \AtrioTeam\Box\Exceptions\GenericErrorException(null, $response['msg']);
}
$redirect = $App->Lang->returnL('cart_detail');
header('Location: '.$redirect);
exit;
