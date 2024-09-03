<?php
/*
 *	Recupero le informazioni su di un ordine
 *
*/

namespace Ecommerce;

use DateInterval;
use DateTime;
use Exception;

class Order {


	protected $arOrderData 		= array();		// dati della tabella ordine inerenti unicamente l'ordine
	protected $arUserData 		= array();		// dati della tabella ordine_cliente inerenti unicamente l'ordine
	protected $arExpeditionData	= array();		// dati della tabella ordine_spedizione inerenti unicamente l'ordine
	protected $arPaymentData	= array();		// dati della tabella ordine_pagamento inerenti unicamente l'ordine
	protected $arProducts 		= array();		// prodotti dell'ordine
	protected $Discount 		= array();		// sconto dell'ordine
	protected $arCustomOptions 	= array();		// custom options

	/**
	 * Elenco voci in ordine
	 */
	protected $arItems = [];

	protected $Da;									// oggetto DataAccess
	protected $Lang;								// oggetto LangManager

	protected $dataLoaded 		= false;


	/**
	 * @param $fullLoad carica tutti i dati correlati (cliente, spedizione, etc..)
	 */
	public function __construct($Da, $Lang, $column = 'id', $value = NULL,$force = false, $fullLoad = true){

		if($column && $value === NULL):
			throw new \Exception('['.__METHOD__.']  Manca il valore dell\'ordine da recuperare',E_USER_ERROR);
		endif;

		$this->Da 		= $Da;
		$this->Lang 	= $Lang;

		if ($column) {
			$this->loadOrder($column,$value,$force, $fullLoad);
		}

	}


	/**
	* 	__call()
	* 	overloading per ottenere o impostare elementi dell'array arOrderData
	*	get+valore da ottenere per ottenere un valore di arOrderData
	*	set+valore da impostare per inserire un valore in arOrderData
	*	@param string $key : parametro da ottenere -> le prime tre lettere definiscono l'azione da intraprendere (set | get)
	*	@param string $val : eventuale valore da impostare
	*	@return
	*		se get il valore corrispondente alla chiave cercata , false altrimenti
	*		se set true
	*		false in tutti gli altri casi
	*/
	public function __call($key,$args = array('')){
		$method = substr($key,0,3);
		$search = substr($key,3);
		$val 	= is_array($args) && count($args) > 0 ? $args[0] : '';
		
		switch($method):
			case 'get': 
				if(!array_key_exists($search, $this->arOrderData)) {
					throw new Exception("Proprietà non esistente [$search]");
				}
				return $this->arOrderData[$search];

			case 'set': return $this->arOrderData[$search] = $val; break;
			default: 
				throw new Exception("Metodo non valido [{$method}]");
		endswitch;
	}



	#########################################################
	#
	#	CARICAMENTO DATI ORDINE
	#
	#########################################################


	/**
	* 	loadOrder()
	* 	recupera un ordine dal db e salva i dati nell'array
	*	@param string $column : colonna del db
	*	@param string $value : valore 
	*	@return bool
	*/
	protected function loadOrder($column,$value,$force = false , $fullLoad = true){
		$arOrderColumns = $this->Da->getColumns('ordine');

		if(array_search($column,$arOrderColumns) === false):
			throw new \Exception('['.__METHOD__.'] La colonna <code>'.$column.'</code> non esiste nella tabella ordine', E_USER_ERROR);
		endif;

		$r = $this->Da->customQuery(
			"SELECT 
			*
			,DATE_FORMAT(data2,'%d.%m.%Y %H:%i:%s') AS data2_formatted
			FROM ordine 
			WHERE ".$column." = ?".($force === false ? " AND deleted = 'N'" : '')
			,array($value)
		);

		if(!$r) {
			throw new \Exception('['.__METHOD__.'] Nessun ordine con <code>'.$column.' = '.$value.'</code>', E_USER_ERROR);
		}

		if(count($r) > 1) {
			throw new \Exception('['.__METHOD__.'] Recuperato più di un ordine con <code>'.$column.' = '.$value.'</code>', E_USER_ERROR);
		}

		$this->arOrderData = current($r);	// imposto i dati dell'ordine
		if($fullLoad === true) :
			$this->fetchUser();// recupero i prodotti
			$this->fetchCartElements();// recupero i prodotti
			$this->fetchPayment();// recupero i dati del pagamento
			$this->fetchExpedition();// recupero i dati dell spedizione
			$this->fetchDiscount();// recupero il codice sconto
			$this->fetchCustomOptions();// recupero le opzioni custom
		endif;
	
		$this->dataLoaded = true;
		
	}


	/**
	* 	isLoaded()
	* 	verifica se l'ordine è stato caricato
	*	@param void
	*	@return bool true o false
	*/
	public function isLoaded(){
		return $this->dataLoaded;
	}


	/**
	* 	fetchCartElements()
	* 	recupera gli elementi di un ordine
	*	@param void
	*	@return bool true o false
	*/
	protected function fetchCartElements(){
        $this->arItems = $this->Da->getRecords(array(
            'table'     => 'ordine_prodotto'
			,'cond'     => 'WHERE ordine_code = ?'
			,'params'	=> array($this->arOrderData['ordine_code'])
        ));
		if(!$this->arItems) {
			throw new Exception("Prodotti mancanti per ordine {$this->arOrderData['ordine_code']}");
		}
		return true;
	}


	/**
	* 	fetchUser()
	* 	recupera i prodotti di un ordine
	*	@param void
	*	@return bool true o false
	*/
	protected function fetchUser(){
		$this->arUserData = $this->Da->getSingleRecord(array(
			'table'		=> 'ordine_cliente'
			,'cond'     => 'WHERE ordine_code = ?'
			,'params'	=> array($this->arOrderData['ordine_code'])
		));
		if(!$this->arUserData) {
			throw new Exception("Dati utente mancanti per ordine {$this->arOrderData['ordine_code']}");
		}
		return true;
	}


	/**
	* 	fetchPayment()
	* 	recupera i dati sul pagamento
	*	@param void
	*	@return bool true o false
	*/
	protected function fetchPayment(){
		$this->arPaymentData = $this->Da->getSingleRecord(array(
			'table'		=> 'ordine_pagamento'
			,'cond'     => 'WHERE ordine_code = ?'
			,'params'	=> array($this->arOrderData['ordine_code'])
		));
		if(!$this->arPaymentData) {
			throw new Exception("Dati pagamento mancanti per ordine {$this->arOrderData['ordine_code']}");
		}
		return true;

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
		if(!$this->arExpeditionData) {
			throw new Exception("Dati spedizione mancanti per ordine {$this->arOrderData['ordine_code']}");
		}
		return true;

	}


	/**
	* 	fetchDiscount()
	* 	recupera il codice sconto usato in un determinato ordine
	*	@param void
	*	@return bool true o false
	*/
	protected function fetchDiscount(){
		$this->Discount = $this->Da->getSingleRecord(array(
			'table'		=> 'ordine_codice_sconto'
			,'cond'     => 'WHERE ordine_code = ?'
			,'params'	=> array($this->arOrderData['ordine_code'])
		));
		
		return true;
	}


	/**
	* 	fetchCustomOptions()
	* 	recupera le impostazioni custom
	*	@param void
	*	@return bool true o false
	*/
	protected function fetchCustomOptions(){
		$this->arCustomOptions = $this->Da->getRecords(array(
			'table'		=> 'ordine_opzioni_aggiuntive'
			,'cond'     => 'WHERE ordine_code = ?'
			,'params'	=> array($this->arOrderData['ordine_code'])
		));
		return true;
	}



	#########################################################
	#
	#	RITORNO DATI ORDINE 
	#
	#########################################################


	/**
	* 	getOrderData()
	* 	ritorna tutti i dati dell'ordine
	*	@param void
	*	@return array : tutti i dati dell'ordine
	*/
	public function getOrderData(){
		return $this->dataLoaded === true ? $this->arOrderData : array();
	}


	/**
	* 	getUserData()
	* 	ritorna tutti i dati dell'utente legato all'ordine
	*	@param void
	*	@return array : tutti i dati dell'ordine
	*/
	public function getUserData(){
		return $this->dataLoaded === true ? $this->arUserData : array();
	}


	/**
	* 	getItems()
	* 	ritorna tutti i prodotti
	*	@param void
	*	@return array : tutti i dati dell'ordine
	*/
	public function getItems(){
		return $this->dataLoaded === true ? $this->arItems : array();
	}


	/**
	* 	getPayment()
	* 	ritorna tutti i dati del pagamento legato all'ordine
	*	@param void
	*	@return array : tutti i dati dell'ordine
	*/
	public function getPayment(){

		if(!$this->arPaymentData) {
			$this->fetchPayment();
		}

		return  $this->arPaymentData;
	}


	/**
	* 	getExpedition()
	* 	ritorna tutti i dati della spedizione legato all'ordine
	*	@param void
	*	@return array : tutti i dati dell'ordine
	*/
	public function getExpedition(){

		if(!$this->arExpeditionData) {
			$this->fetchExpedition();
		}

		return $this->arExpeditionData;
	}


	/**
	* 	getDiscount()
	* 	ritorna tutti i dati dell'ordine
	*	@param void
	*	@return array : tutti i dati dell'ordine
	*/
	public function getDiscount(){
		return $this->dataLoaded === true ? $this->Discount : NULL;
	}


	/**
	* 	getCustomOptions()
	* 	ritorna tutti i dati dell'ordine
	*	@param void
	*	@return array : tutti i dati dell'ordine
	*/
	public function getCustomOptions(){
		return $this->dataLoaded === true ? $this->arCustomOptions : array();
	}


	/**
	 * ***********************************************************************************
	 * ***********************************************************************************
	 * METODI DI SALVATAGGIO DI INFORMAZIONI SPECIFICHE 
	 * ***********************************************************************************
	 * ***********************************************************************************
	 */

	
    /**
     * Conferma (arrivo a fine procedura) di un ordine in carrello.
     * Per la conferma di pagamento c'è metodo separato confirmPayment
     * (per metodi esterni che sia affidano a notifica asincrona)
     */
    public function confirm() {

		// confermo ordine
        $this->Da->updateRecords(array(
            'table' => 'ordine'
            ,'data' => [
                "conferma_ordine" => 'Y'
                ,"conferma_utente" => 'Y'
                ,"data2" => date('Y-m-d H:i:s') // data arrivo a fine procedura
            ]
            ,"cond" => "WHERE id = :id"
            ,"params" => ["id" => $this->arOrderData["id"]]
        ));
        
        return true;
    }


	/**
     * Imposta pagamento in corso
     */
    public function progressPayment($extraData = array()) {
        
        $this->Da->updateRecords([
            "table" => "ordine_pagamento"
            ,"data" => array_merge($extraData, [
                "stato" => "In-Progress"    
            ])
            ,"cond" => "WHERE id_ordine = :id_ordine"
            ,"params" => ["id_ordine" => $this->arOrderData["id"]]
        ]);        
    } 


	/**
     * Imposta pagamento fallito
     */
    public function failPayment($extraData = array()) {
        
        $this->Da->updateRecords([
            "table" => "ordine_pagamento"
            ,"data" => array_merge($extraData, [
                "stato" => "Failed"    
            ])
            ,"cond" => "WHERE id_ordine = :id_ordine"
            ,"params" => ["id_ordine" => $this->arOrderData["id"]]
        ]);        
    } 


    /**
     * Imposta pagamento annullato da cliente
     */
    public function cancelPayment($extraData = array()) {
        // aggiorno pagamento
        $this->Da->updateRecords([
            "table" => "ordine_pagamento"
            ,"data" => array_merge($extraData, [
                "stato" => "Canceled-Reversal"    
            ])
            ,"cond" => "WHERE id_ordine = :id_ordine"
            ,"params" => ["id_ordine" => $this->arOrderData["id"]]
        ]);        
    }


    /**
     * Imposta pagamento rimborsato
     */
    public function refundPayment($extraData = array()) {
            
        // pagamento
        $this->Da->updateRecords([
            "table" => "ordine_pagamento"
            ,"data" => array_merge($extraData, [
                "stato" => "Refunded"    
            ])
            ,"cond" => "WHERE id_ordine = :id_ordine"
            ,"params" => ["id_ordine" => $this->arOrderData["id"]]
        ]);        
        
        // ordine
        $this->Da->updateRecords([
            "table" => "ordine"
            ,"data" => [
                "stato" => 'refunded'
                ,"data5" => date('Y-m-d H:i:s')   
            ]
            ,"cond" => "WHERE id = :id"
            ,"params" => ["id" => $this->arOrderData["id"]]
        ]);
    }


    /**
     * Conferma pagamento.
     * Per confermare pagamento separatamente, es. in caso di notifica
     * asincrona.
     * 
     * @param array $data dati ulteriori con i quali integrare le informazioni di pagamento (già mappati su db per comodità)
     * @todo validare $data in ingresso
     */
    public function confirmPayment($extraData = array()) {
        
        
        // pagamento
        $this->Da->updateRecords([
            "table" => "ordine_pagamento"
            ,"data" => array_merge($extraData, [
                "stato" => "Completed",
				"PP_ERROR_RESPONSE" => null // nel caso ci fosse un errore precedente
            ])
            ,"cond" => "WHERE id_ordine = :id_ordine"
            ,"params" => ["id_ordine" => $this->arOrderData["id"]]
        ]);
 
    }


    /**
     * Aggiornamento generico pagamento
     * @todo da migliorare, v. uso in Ipn Listener
     */
    public function updatePayment($extraData = array()) {
        $this->Da->updateRecords([
            "table" => "ordine_pagamento"
            ,"data" => $extraData
            ,"cond" => "WHERE id_ordine = :id_ordine"
            ,"params" => ["id_ordine" => $this->arOrderData["id"]]
        ]);        
    }


    /**
     * Errore di pagamento specifico per Paypal.
     * Es. di $resArray da Paypal
     * [TIMESTAMP] => 2019-06-30T11:49:22Z
     * [CORRELATIONID] => 5d2fa94ce40ea
     * [ACK] => Failure
     * [VERSION] => 2.3
     * [BUILD] => 53072421
     * [L_ERRORCODE0] => 10422
     * [L_SHORTMESSAGE0] => Customer must choose new funding sources.
     * [L_LONGMESSAGE0] => The customer must return to PayPal to select new funding sources.
     * [L_SEVERITYCODE0] => Error
     * [API_NAME] => DoExpressCheckoutPayment
     */
    public function paypalPaymentError($resArray = []) {
        
        $this->Da->updateRecords([
            "table" => "ordine_pagamento"
            ,"data" => [
                'stato' => 'Denied'
                ,'PP_ERROR_RESPONSE' => json_encode($resArray)
            ]
            ,"cond" => "WHERE id_ordine = :id_ordine"
            ,"params" => ["id_ordine" => $this->arOrderData["id"]]
        ]);        
    }
	/**
	 * ***********************************************************************************
	 * ***********************************************************************************
	 * // METODI DI SALVATAGGIO DI INFORMAZIONI SPECIFICHE 
	 * ***********************************************************************************
	 * ***********************************************************************************
	 */


	/**
	 * __get
	 */
	public function __get($name)
 	{	

		if(!array_key_exists($name, $this->arOrderData)) {
			throw new Exception("Proprietà non esistente [$name]");
		}

		return $this->arOrderData[$name];

	}



}

