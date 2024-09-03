<?php
/**
 * @package AtrioShop
 * @author Emanuele Fornasier, Jacopo Viscuso, Mauricio Cabral
 */
namespace Ecommerce;

use Ecommerce\Order;
use App as GlobalApp;

/**
 * Racchiude logica transazionale di invio notifiche e operazioni
 * contestuali per un ordine eseguito.
 */
class OrderNotificationRoutine
{

    
    /**
     * @var Order
     */
    protected $order;
    
 
    /**
     * @param DataAccess $DataAccess
     * @param array $paymentConfig configurazione dipendente dal metodo di pagamento
     */
    public function __construct(
       Order $Order
    ) {
        $this->order = $Order;
    }


    /**
     * Esecuzione
     */
    public function handle()
    {

        //$this->notifyAdmin();

        return $this->notifyCustomer();

    }

 
    /**
     * Avvisa admin per ordine
     */
    // public function notifyAdmin($output = false) {
  
    //     $this->LangManager->loadTrads("cart_global,cart_detail,cart_mail");


    //     /**
    //      * Carico i dati dell'ordine
    //      */
    //     $Customer = $this->order->getUserData();
    //     $Payment = $this->order->getPayment();
    //     $arOrderData = $this->order->getOrderData();

    //     /**
    //      * crea riepilogo
    //      */
    //     $infoBody = $this->infoBody();

    //     /**
    //      * body specifico per admin
    //      */
    //     $mailBodyAd = '';
    //     switch($Payment['stato']):
    //         case 'Failed':
    //         case 'Denied':
    //             $MailSubjectAd  = 'TENTATIVO DI ORDINE NON ANDATO A BUON FINE - '.App::r()->Config['site']['name'];
    //             $mailBodyAd     = '<div class="section_block"><div class="section section_no_margin">&Egrave; stato tentato un ordine dal sito ma non è andato a buon fine. <br>
    //                                     Vedi i dettagli dell\'ordine: <a href="'.App::r()->Config['site']['url'].'/area_amministrazione/func/function_update_item.php?id_item='.$arOrderData['id'].'&amp;n=ordini_sospesi'.'"><strong>entra nell&rsquo;area amministrazione</strong></a></div></div>'.$infoBody;
    //             $this->MailBuilder->arMailInfo["MAIL_TITLE"] = '';
    //             break;
    //         default: // a buon fine

    //             $MailSubjectAd  = 'NUOVO ORDINE - '.App::r()->Config['site']['name'];
    //             $mailBodyAd     = '<div class="section_block"><div class="section section_no_margin">&Egrave; arrivato un nuovo ordine dal sito. <br>
    //                                     Vedi i dettagli dell\'ordine: <a href="'.App::r()->Config['site']['url'].'/area_amministrazione/func/function_update_item.php?id_item='.$arOrderData['id'].'&amp;n=ordini_attivi"><strong>entra nell&rsquo;area amministrazione</strong></a></div></div>'.$infoBody;

    //             $this->MailBuilder->arMailInfo["MAIL_TITLE"] = 'NUOVO ORDINE';
    //         endswitch;
    
        
    //     /**
    //      * Imposto body in MailBuilder
    //      */
    //     $this->MailBuilder->setMailBody($mailBodyAd);


    //     /**
    //      * configurazione indirizzi
    //      */
    //     $PHPMailer = App::r()->create("PhpMailer");
    //     $PHPMailer->AddAddress(App::r()->Config['site']['mail_orders'], App::r()->Config['site']['name']);


    //     /**
    //      * Configuro PhpMailer
    //      */
    //     $userName = $Customer['nome'].' '.$Customer['cognome'];
    //     $PHPMailer->From = App::r()->Config['site']['mail_noreply'];
    //     $PHPMailer->FromName = $userName;
    //     $PHPMailer->Subject = strip_tags($MailSubjectAd);
    //     $PHPMailer->Body = $this->MailBuilder->createMail();
    //     $PHPMailer->AltBody = strip_tags($MailSubjectAd);
        

    //     /**
    //      * Esecuzione
    //      */
    //     if ($output == true) {
    //         return $PHPMailer->Body;
    //     }
    //     return $this->NotifierService->notifyDispatch($PHPMailer, 'order_notification_admin_'.$arOrderData['id']);



    // }
    

    /**
     * Notifica utente.
     * Si serve di webservice
     */
    public function notifyCustomer($output = false) {

        $locale = GlobalApp::r()->Lang->lgSuff;

        $client = GlobalApp::r()->create("WsClient");

        $endpoint = 'api/orders/'.$this->order->ordine_code.'/confirmation-mail/'.$locale;
        if($output == true) {
            $endpoint .= '/render';
        }

        $response = $client->request('GET', $endpoint);

        $response = $response->getBody();
        
        return $response;

    }


}