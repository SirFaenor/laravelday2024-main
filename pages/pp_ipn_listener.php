<?php
/**
 * Script di ricezione notifiche IPN Paypal
 */
require_once __DIR__.'/../include/php/lib/packages/atrioteam/paypal/AbstractIpnHandler.php';
require_once __DIR__.'/../include/php/lib/packages/atrioteam/paypal/IpnHandler.php';
use AtrioTeam\Paypal\IpnHandler;

/**
 * Verifica chiamata
 */
$Ipn = new IpnHandler($_REQUEST);
$response = $Ipn->processData();
if ($response !== true):
	//throw new Exception("[ERRORE PP IPN - processData()] Responso PP : ".$response, E_USER_ERROR);
endif;


/**
 * recupero id ordine
 */
$orderId = $Ipn->getData("invoice");


/**
 * Log chiamata (prima di verifica ordine per non incorrere in eccezione se l'ordine non viene trovato)
 */
$logId = $App->Da->insert([
    'table'     => 'ordine_log_ipn'
    ,'data'     => [
        'id_ordine' => $orderId,
        'txn_id' => $Ipn->getData("txn_id"),
        'payer_email' => $Ipn->getData("payer_email"),
        'payment_status' => $Ipn->getData("payment_status"),
        'pending_reason' => $Ipn->getData("pending_reason"),
        'full_content' => $Ipn->serializeData(),
        'raw' => json_encode($_REQUEST),
    ]
]);


/**
 * Recupero ordine
 */
try {

    $Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $orderId, false, true);

} catch (Exception $e) {

    /**
     * Azzero id ordine
     */
    $App->Da->update([
        'table' => 'ordine_log_ipn'
        ,"data" => [
            "id_ordine" => 0
        ]
        ,"cond" => "WHERE id = :id"
        ,"params" => ["id" => $logId]
    ]);

    /**
     * blocco esecuzione
     */
    throw new Exception("[PP IPN LISTENER] Errore recupero dati ordine: arriva il responso da PP ma sembra che l'ordine non esista a db; ID_ORDINE: ".$orderId ,E_USER_ERROR);
}


/**
 * Raccolgo dati per aggiornamento ordine
 */
$arUpdate = array();
$arUpdate['ordine_code'] = $Order->getordine_code();
$arUpdate['note'] = $Ipn->getData("pending_reason");
$arUpdate['PP_TRANSACTIONID'] = $Ipn->getData("txn_id");
$arUpdate['PP_ORDERTIME'] = $Ipn->getData("payment_date");
$arUpdate['PP_CURRENCYCODE'] = $Ipn->getData("mc_currency");
$arUpdate['PP_FEEAMT'] = $Ipn->getData("mc_fee");
$arUpdate['PP_TAXAMT'] = $Ipn->getData("tax");
$arUpdate['PP_PAYERID'] = $Ipn->getData("payer_id");
$arUpdate['PP_PAYERSTATUS'] = $Ipn->getData("payer_status");
$arUpdate['PP_EMAIL'] = $Ipn->getData("payer_email");
$arUpdate['PP_MC_GROSS'] = $Ipn->getData("mc_gross");
$arUpdate['PP_REFUND_MC_GROSS'] = $Ipn->getData("refund_mc_gross");


/**
 * Il controllo dello stato del pagamento avviene direttamente in fase di rientro nel sito da 
 * parte dell'acquirente (pages/lang/cart/order_pp_2.php).
 * Spostato da manu in data 2022/05/09.
 * Qui facciamo solo l'aggiornamento dei dati per eventuali controlli da parte di
 * admin e l'invio della mail di conferma al cliente.
 */
$Order->updatePayment($arUpdate);


/**
 * Invia mail di conferma ordine al cliente
 */
if($Ipn->getData("payment_status") == 'Completed'):
    
    $App->Lang->loadTrads("cart_global,cart_mail,user,errors,cart_help");

    $OrderNotifierRoutine = new Ecommerce\OrderNotificationRoutine($Order, $App->Da, $App->Lang, $App->create("Mailer"), $App->create("NotifierService"), $App->CalculatorService);
    $OrderNotifierRoutine->notifyCustomer();

endif;


/**
 * Risposta ok
 */
exit("1");