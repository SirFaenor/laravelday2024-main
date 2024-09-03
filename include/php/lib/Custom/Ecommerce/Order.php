<?php 

namespace Custom\Ecommerce;

use DateInterval;
use Exception;
use DateTime;
use App as GlobalApp;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Message;
use Throwable;

class Order extends \Ecommerce\Order {

    
    /**
     * validità 2 ore, in secondi.
     * Fa anche da tempo limite per chiedere rimborso.
	 */
    const EXPIRATION_TIME = 60*60*2;
    
    /**
     * Tempo di evasione del rimborso
     * (NON tempo limite per chiedere rimborso)
     * 4gg, in ore
     */
    const REFUND_TIME = 96;

    const STATUS_NEW = 'new';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REQUEST_REFUND = 'refund-request';
    const STATUS_REQUEST_AUTOREFUND = 'autorefund-request';

    /**
     * Calcola ammontare totale dell'iva di un ordine
     * (deve essere precedentemente caricato)
     */
    public function calculateVatTotalAmount() {

        // caricare prima l'ordine
        if (! $this->isLoaded()) {
            throw new Exception("L'ordine non è ancora stato caricato.");
        }

        // ordine vuoto
        if (!count($this->arItems)) {
            return 0;
        }

        // calcolo iva
        $vat = 0;
        foreach ($this->arItems as $key => $item): 
            $vat += $item["subtotal_iva"];
        endforeach;

        return $vat;

    }


    /**
     * Caricamento ordine
     */
    protected function loadOrder($column, $value, $force = false, $fullLoad = true){
        
        parent::loadOrder($column, $value, $force, $fullLoad);

    }

   
	/**
	* 	fetchExpedition()
	* 	recupera i dati sulla spedizione custom -> nel caso della standard fare riferimento ai dati utente
	*	@param void
	*	@return bool true o false
	*/
	protected function fetchExpedition(){
		$this->arExpeditionData = $this->Da->getSingleRecord(array(
			'table'		=> 'ordine_spedizione'
			,'cond'     => 'WHERE ordine_code = ?'
			,'params'	=> array($this->arOrderData['ordine_code'])
        ));
        if($this->arExpeditionData):
            $this->arExpeditionData['source_data'] = unserialize($this->arExpeditionData['raw_src']);
        endif;
		return $this->arExpeditionData ? true : false;
	}


    protected function fetchCustomOptions()
    {
        return true;
    }


    /**
     * Imposta come richiesto rimborso
     * Si serve del webservice (se è già stato chiesto il rimborso, lancia errore)
     */
    public function requestRefund()
    {
        
        /**
         * imposto stato via api (se impostazione fallisce si genera errore)
         * @todo gestire messaggio di errore
         */
        $client = GlobalApp::r()->create("WsClient");

        try {
            $response = $client->request('POST', 'api/orders/'.$this->ordine_code.'/refund-request');

            $response = json_decode($response->getBody());  

        } catch (ClientException  $e) {

            $response = json_decode($e->getResponse()->getBody());

            throw new Exception($response->response);
        }
        
            
        return true;
    }
       

   
}


