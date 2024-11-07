<?php
require_once __DIR__.'/../include/php/init/main.php';
require_once __DIR__.'/../include/php/init/container.php';
require_once __DIR__.'/../include/php/init/cart.php';

$orderId = $App->getFromRequest("order_id");
if(!$orderId){
    exit("Missing order id");
}

$send = $App->getFromRequest("send") == 1 ? true : false;

$Order = new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $orderId);


/**
 * Routine in seguito a completamento ordine
 */
$OrderNotifierRoutine = new Ecommerce\OrderNotificationRoutine($Order);

echo $OrderNotifierRoutine->notifyCustomer( ! $send);
