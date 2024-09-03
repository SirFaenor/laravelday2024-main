<?php declare(strict_types=1);
require_once __DIR__.'/../include/php/init/main.php';

use AtrioTeam\AtrioShop\Checkout\OrderNotificationRoutine;
use PHPUnit\Framework\TestCase;

final class OrderNotifierTest extends TestCase
{
    
    public function testNotifyAdmin(): void
    {   
        global $App;


        /**
         * Recupero un ordine
         */
        $orderId = $App->Da->getSingleRecord([
            "table" => "ordine"
            ,"cond" => "ORDER BY RAND() LIMIT 1"
        ]);
        $orderId = $orderId["id"];
        $orderId = 28;
        $order =  new \Custom\Ecommerce\Order($App->Da,$App->Lang,'id',$orderId);

        /**
         * Notifier
         */
        $notifier =  $App->create("NotifierService", 'l');
        
        
        /**
         * Routine in seguito a completamento ordine
         */
        $OrderNotifierRoutine = new Ecommerce\OrderNotificationRoutine($order, $App->Da, $App->Lang, $App->create("Mailer"), $notifier, $App->CalculatorService);

        $OrderNotifierRoutine->notifyAdmin();
        $this->assertFileExists($App->Config["notifier"]["log_path"].'/'.$notifier->getLastMessageSubject().'.txt');
        
        $OrderNotifierRoutine->notifyCustomer();
        $this->assertFileExists($App->Config["notifier"]["log_path"].'/'.$notifier->getLastMessageSubject().'.txt');

        
    }

}