<?php declare(strict_types=1);
require_once __DIR__.'/../include/php/init/main.php';

use PHPUnit\Framework\TestCase;


/**
 * Vari test di aggiornamento dello stato di un ordine
 */
final class OrderUpdateTest extends TestCase
{
    
    protected function loadAnOrder()
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

        return new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $orderId, false, false);

    }


    protected function reload($orderId) 
    {   
        global $App;

        return new \Custom\Ecommerce\Order($App->Da, $App->Lang,'id', $orderId, false, false);

    }

    /**
     * Conferma un ordine
     */
    public function testConfirm(): void
    {   

        $Order = $this->loadAnOrder();
        $Order->confirm();

        $OrderCheck = $this->reload($Order->getid());
        
        
        $this->assertEquals($OrderCheck->getid(), $Order->getid());
        $this->assertEquals($OrderCheck->getconferma_ordine(), 'Y');
               
    }


    /**
     * Fallimento pagamento
     */
    public function testFailPayment(): void
    {   

        $Order = $this->loadAnOrder();
        
        $Order->failPayment();

        $OrderCheck = $this->reload($Order->getid());
        
        $this->assertEquals($OrderCheck->getPayment()["stato"], 'Failed');
               
    }

    /**
     * Annullamento pagamento
     */
    public function testCancelPayment(): void
    {   

        $Order = $this->loadAnOrder();
        
        $Order->cancelPayment();

        $OrderCheck = $this->reload($Order->getid());
        
        $this->assertEquals($OrderCheck->getPayment()["stato"], 'Canceled-Reversal');
               
    }


    /**
     * RImborso pagamento
     */
    public function testRefundPayment(): void
    {   

        $Order = $this->loadAnOrder();
        
        $Order->refundPayment();

        $OrderCheck = $this->reload($Order->getid());
        
        $this->assertEquals($OrderCheck->getPayment()["stato"], 'Refunded');
        $this->assertEquals($OrderCheck->getstato(), 'refunded');
        $this->assertNotNull($OrderCheck->getdata5());
        
               
    }


    /**
     * Conferma pagamento
     */
    public function testConfirmPayment()
    {
        $Order = $this->loadAnOrder();
        
        $Order->confirmPayment();

        $OrderCheck = $this->reload($Order->getid());
        
        $this->assertEquals($OrderCheck->getPayment()["stato"], 'Completed');

    }

    /**
     * Pagamento in progress
     */
    public function testProgressPayment()
    {
        $Order = $this->loadAnOrder();
        
        $Order->progressPayment();

        $OrderCheck = $this->reload($Order->getid());
        
        $this->assertEquals($OrderCheck->getPayment()["stato"], 'In-Progress');

    }


    /**
     * Errore conferma pagamento paypal
     */
    public function testPaypalPaymentError()
    {
        $Order = $this->loadAnOrder();
        
        $Order->paypalPaymentError();

        $OrderCheck = $this->reload($Order->getid());
        
        $this->assertEquals($OrderCheck->getPayment()["stato"], 'Denied');

    }

    


}