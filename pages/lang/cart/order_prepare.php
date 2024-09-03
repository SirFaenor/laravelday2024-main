<?php
/**
 * Script di reindirizzamento in base a metodo di pagamento
 */

$App->BrowserQueue->noSave();

switch($selectedPayment = $App->Cart->getSelectedPaymentMethod()['code']):
    case 'NEXI':
        $redirectUrl = $App->Lang->returnL('cart_order_nexi_1');
        break;
    case 'BRAINTREE_CREDIT_CARD':
    case 'BRAINTREE_PP_EXPRESS_CHECKOUT':
        $redirectUrl = $App->Lang->returnL('cart_order_bt_2');
        break;
    case 'PP_EXPRESS_CHECKOUT':
    case 'PP_CREDIT_CARD':
        $redirectUrl = $App->Lang->returnL('cart_order_pp_1');
        break;
    case 'PAYWAY':
        $redirectUrl = $App->Lang->returnL('cart_order_payway_1');
        break;
        default:
        throw new Exception("Invalid payment method [$selectedPayment]");
endswitch;


/**
 * Redirect
 */
$App->redirect($redirectUrl);


