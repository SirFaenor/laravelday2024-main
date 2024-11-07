<?php
/**
 * ###########################################################################
 * class.Cart
 * ---------------------------------------------------------------------------
 * Gestione del carrello 
 * Sezioni principali
 * -> 	gestione impostazionio del carrello
 * 	- metodi di pagamento 
 * 	- spese di spedizione
 * -> 	gestione prodotti
 * 	- aggiunta
 * 	- rimozione
 * 	- conteggio
 * ->	importo del carrello
 * 	- calcolo il totale del carrello
 * 	- ritorno il totale del carrello
 * -> 	codice sconto
 * 	- imposto un codice sconto
 * 
 * ###########################################################################
 */

namespace Ecommerce;

use Custom\Ecommerce\Cartable;
use Exception;

abstract class Cart {
    
    /**
     * trait per recupero dati di una nazione
     */
    use \Utility\Traits\NationData;
    
    /**
     * trait per recupero dati di una provincia
     */
    use \Utility\Traits\ProvinceData;

	protected $Da;
	protected $Lang;
	protected $User;
	protected $Ecomm;
	protected $Currency;
	protected $FixNumber;
	protected $FixString;

	protected $arPaymentMethods;		// i metodi di pagamento disponibili
	protected $selectedPaymentMethod;	// il metodo di pagamento selezionato

	protected $arExpeditions;			// i tipi di spedizione disponibile
	protected $selectedExpedition;		// il tipo di spedizione selezionata

	protected $Discount;				// lo sconto attualmente attivo per il carrello

	protected $arProducts 	= array();	// i prodotti nel carrello 
	protected $arOrderData 	= NULL;		// i dati dell'ordine

	protected $prodAmount 	= 0;		// importo del carrello contando solo i prodotti (no spese spedizione, no spese pagamento, no sconto)
	protected $totalAmount 	= 0;		// importo del carrello (prodotti + spese spedizione + spese pagamento - sconto)

	protected $orderID 		= NULL;		// id dell'ordine
	protected $emptyCartData= false;	// svuoto il carrello nel distruttore
	public 	  $paymentTry	= '';		// registro l'url dei tentativi di pagamento per consentire un solo nuovo tentativo automatico

	public $maxPaymentHits = 3;		// numero massimo di tentativi di pagamento, dopodiché invio alla pagina help
	public $paymentHits = 0;			// registro il numero di volte che un utente può tentare il pagamento


	public function __construct($Da, $Lang,\Site\User $User,\Ecommerce\Ecommerce $Ecomm, \Utility\Currency $Currency,\Utility\FixNumber $FixNumber,\Utility\FixString $FixString){

		$this->Da 		= $Da;
		$this->Lang		= $Lang;
		$this->User		= $User;
		$this->Ecomm	= $Ecomm;
		$this->Currency	= $Currency;
		$this->FixNumber = $FixNumber;
		$this->FixString = $FixString;

		if(isset($_SESSION['Cart']) && count($_SESSION['Cart'])):
			// se la categoria utente registrata è diversa da quella attuale svuoto il carrello
			if(isset($_SESSION['Cart']['userCat']) && $_SESSION['Cart']['userCat'] != $this->User->getid_cat()):
				$this->emptyCart();
			else:
				foreach($_SESSION['Cart'] as $k => $v):
                    $this->{$k} = $v;
				endforeach;
			endif;
		endif;

		$this->calcTotalAmount();
	}


	public function __destruct(){

		if($this->User->emptyCartData && $this->User->emptyCartData === true ):
			$this->emptyCartData = true;
		endif;

		if(isset($_SESSION['Cart']['userCat']) && $_SESSION['Cart']['userCat'] != $this->User->getid_cat()):
			$this->emptyCartData = true; # controllo che non sia cambiata la categoria dell'utente
		endif;

		if($this->emptyCartData !== true):
			$_SESSION['Cart']['orderID']				= $this->orderID;
			$_SESSION['Cart']['paymentTry']				= $this->paymentTry;
			$_SESSION['Cart']['paymentHits']			= $this->paymentHits;
			$_SESSION['Cart']['userCat'] 				= $this->User->getid_cat();
			$_SESSION['Cart']['arProducts'] 			= $this->arProducts;
			$_SESSION['Cart']['arOrderData']			= $this->arOrderData;
			$_SESSION['Cart']['selectedExpedition']		= $this->selectedExpedition;
			$_SESSION['Cart']['selectedPaymentMethod']	= $this->selectedPaymentMethod;
			if($this->User->allowed('USA_CODICE_SCONTO')):
				$_SESSION['Cart']['Discount'] 				= $this->Discount;
			endif;
		else:
			unset($_SESSION['Cart']);
		endif;

	}


	/**
	* 	emptyCart()
	* 	svuota il carrello
	* 	@param void
	* 	@return void
	*/
	public function emptyCart(){
		unset($_SESSION['Cart']);
		if(isset($_SESSION['nvpReqArray'])):	// se presente svuoto la sessione con i dati paypal
			unset($_SESSION['nvpReqArray']);
		endif;
		if(isset($_SESSION['TOKEN'])):	// se presente svuoto la sessione con i dati paypal
			unset($_SESSION['TOKEN']);
		endif;
		$this->emptyCartData = true;
	}




	#########################################################
	#
	#	METODI DI PAGAMENTO
	#
	#########################################################


	/**
	* 	setPaymentMethods()
	* 	popola l'array con i metodi di pagamento consentiti
	* 	@param void
	* 	@return void
	*/
	abstract public function setPaymentMethods();

	/**
	* 	addPaymentMethod()
	* 	aggiunge un metodo di pagamento al carrello
	*	@param string $key : chiave da assegnare al metodo di pagamento
	*	@param object $PaymentMethod : oggetto contenente tutte le informazioni sul metodo di pagamento
	*	@return void
	*/
	public function addPaymentMethod($key,$PaymentMethod){
		$this->arPaymentMethods[$key] = $PaymentMethod;
	}


	/**
	* 	getPaymentMethods()
	* 	ritorna i metodi di pagamento possibili
	*	@param void
	*	@return array arPaymentMethods
	*/
	public function getPaymentMethods(){
		return $this->arPaymentMethods;
	}


	/**
	* 	selectPaymentMethod()
	* 	imposto un metodo di pagamento
	*	@param string key : chiave per il metodo di pagamento desiderato
	*	@return void
	*/
	public function selectPaymentMethod($key){
        

        if(!isset($this->arPaymentMethods[$key])):
            throw new \Exception('['.__METHOD__.'] Si cerca di impostare un metodo di pagamento <strong>'.$key.'</strong> inesistente; metodi di pagamento disponibili: <pre>'.print_r($this->arPaymentMethods,1).'</pre>', E_USER_ERROR);
        endif;

		$this->selectedPaymentMethod = $this->arPaymentMethods[$key];
   
        return $this->selectedPaymentMethod;
	
    }


	/**
	* 	getSelectedPaymentMethod()
	* 	ritorna il metodo di pagamento selezionato
	*	@param void
	*	@return object $selectedPaymentMethod
	*/
	public function getSelectedPaymentMethod(){
		return $this->selectedPaymentMethod;
	}


	/**
	* 	clearPaymentMethods()
	* 	cancello tutti i metodi di pagamento
	*	@param void
	*	@return void
	*/
	public function clearPaymentMethods(){
		$this->arPaymentMethods = NULL;
		$this->selectedPaymentMethod = NULL;
	}



	#########################################################
	#
	#	SPEDIZIONI
	#
	#########################################################


	/**
	* 	setExpeditions()
	* 	popola l'array con i tipi di spedizioni
	* 	@param void
	* 	@return void
	*/
	protected abstract function setExpeditions();


    /**
    *   addExpedition()
    *   aggiunge un tipo di spedizione al carrello
    *   @param string $key : chiave da assegnare al metodo di pagamento
    *   @param object $Expedition : oggetto contenente tutte le informazioni sul metodo di pagamento
    *   @return void
    */
    public function addExpedition($key,$Expedition){
        $this->arExpeditions[$key] = $Expedition;
    }
    
    /**
    *   removeExpedition()
    *   rimuove un tipo di spedizione dal carrello
    *   @param string $key : chiave da assegnare al metodo di pagamento
    *   @return void
    */
    public function removeExpedition($key){
        if (array_key_exists($key, $this->arExpeditions)) {unset($this->arExpeditions[$key]); return; }
        return true;
    }


	/**
	* 	getExpeditions()
	* 	ritorna i tipi di spedizione possibili
	*	@param void
	*	@return array arExpeditions
	*/
	public function getExpeditions(){
		return $this->arExpeditions;
	}


	/**
	* 	selectExpedition()
	* 	imposto un tipo di spedizione
	*	@param string key : chiave per il metodo di pagamento desiderato
	*	@return void
	*/
	public function selectExpedition($key){
		if(isset($this->arExpeditions[$key])):
			return $this->selectedExpedition = $this->arExpeditions[$key];
		else:
			throw new \Exception('['.__METHOD__.'] Si cerca di impostare un tipo di spedizione <strong>'.$key.'</strong> inesistente; tipi di spedizione disponibili: <pre>'.print_r($this->arExpeditions,1).'</pre>', E_USER_ERROR);
			return false;
		endif;
	}


	/**
	* 	getSelectedExpedition()
	* 	ritorna il metodo di spedizione selezionato
	*	@param void
	*	@return object selectedExpedition
	*/
	public function getSelectedExpedition(){
		return $this->selectedExpedition;
	}


	/**
	* 	clearExpedition()
	* 	cancello tutti i tipi di spedizione
	*	@param void
	*	@return void
	*/
	public function clearExpedition(){
		$this->arExpeditions = NULL;
		$this->selectedExpedition = NULL;
	}



	#########################################################
	#
	#	PRODOTTI
	#
	#########################################################


	/**
	* 	addProduct()
	* 	aggiungo un prodotto all'array arProducts
	*	@param array Product : un prodotto
	*	@param int qta : la quantità aggiunta
	*	@param bool add: true o false; se ho già nel carrello un prodotto con quell'id aggiungo o sovrascrivo la quantità
	*	@return void
	*/
	public abstract function addItem($cartKey, \Custom\Ecommerce\Cartable $Item,$qta = 1,$add = false,$is_present = false);


	/**
	* 	removeProduct()
	* 	rimuovo un prodotto dall'array arProducts
	*	@param string id_pr : id del prodotto
	*	@return true o false
	*/
	public function removeProduct($type, $id_pr){
		if(!$id_pr):
			return false;
		endif;
		unset($this->arProducts[$id_pr]);
		return true;
	}


	/**
	* 	countProducts()
	* 	ritorna il numero di prodotti disponibili nel carrello
	*	@param void
	*	@return int : numero prodotti nel carrello
	*/
	public function countProducts(){
		return count($this->arProducts);
	}


	/**
	* 	getProducts()
	* 	ritorna i prodotti disponibili nel carrello
	*	@param void
	*	@return array : tutti i prodotti presenti nel carrello
	*/
	public function getProducts(){
		return $this->arProducts;
	}


	/**
	* 	clearProducts()
	* 	rimuovo tutti i prodotti dal carrello
	*	@param void
	*	@return void
	*/
	public function clearProducts(){
		$this->arProducts = array();
	}



	#########################################################
	#
	#	CALCOLO IMPORTO DEL CARRELLO
	#
	#########################################################


	/**
	* 	calcItemsTotalAmount()
	* 	calcola il totale dei prodotti
	*	@param void
	*	@return void
	*/
	protected function calcItemsTotalAmount(){

		// sommo i prodotti
		$this->totalAmount = 0;
		$this->prodAmount = 0;
		if(count($this->arProducts)):
			foreach($this->arProducts as $P):
				if($this->User->allowed('CALC_IVA_IN_PRICE')):
					$this->prodAmount += $P['subtotal'];
					$this->totalAmount += $P['subtotal'];						
				else:
					$this->prodAmount += $P['subtotal_no_iva'];
					$this->totalAmount += $P['subtotal_no_iva'];						
				endif;
			endforeach;
		endif;

	}


	/**
	* 	calcTotalAmount()
	* 	calcola il totale del carrello (comprensivo di spese di spedizione, sconti, ...)
	*	@param void
	*	@return void
	*/
	protected function calcTotalAmount(){

		$this->calcItemsTotalAmount();

	}


	/**
	* 	getTotalAmount()
	* 	ritorna il totale del carrello
	*	@param void
	*	@return float $this->totalAmount : totale del carrello
	*/
	public function getTotalAmount(){
		return $this->totalAmount;
	}


	/**
	* 	getProdAmount()
	* 	ritorna il totale del carrello solo dei prodotti
	*	@param void
	*	@return float $this->prodAmount : totale del carrello senza i prodotti
	*/
	public function getProdAmount(){
		return $this->prodAmount;
	}


	/**
	* 	allowedToCheckout()
	* 	condizioni percui è possibile o meno procedere al checkout
	*	@param void
	*	@return bool true | false
	*/
	public function allowedToCheckout(){
		return $this->getProdAmount() >= $this->User->getmin_cart() ? true : false;
	}


	/**
	* 	calcSpedBaseAmount()
	* 	ritorno l'importo che mi serve per calcolare le spese di spedizione
	*	@param void
	*	@return float importo
	*/
	protected function calcSpedBaseAmount(){
		throw new \Exception('['.__METHOD__.'] Impostare il calcolo del prezzo base per le scontistiche di spedizione attraverso l\'estensione della classe', E_USER_WARNING);
	}



	#########################################################
	#
	#	SCONTI
	#
	#########################################################


	/**
	* 	setDiscount()
	* 	aggiungo uno sconto al carrello
	*	@param string $code : codice sconto
	*	@return array 
	*				result 		=> true | false
	*				resp_code 	=> codice di risposta per impostare il messaggio 
	*/
	public function setDiscount($code = NULL){
		$this->clearDiscount();
		return $this->checkDiscount($code);
	}


	/**
	* 	clearDiscount()
	* 	rimuovo i codici sconto
	*	@param void
	*	@return void
	*/
	public function clearDiscount(){
		$this->Discount = NULL;
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
                if (isset($this->arOrderData[$search])) {
                    return $this->arOrderData[$search];
                }
                if ($search == 'orderID') {
                    return $this->orderID;
                }

                throw new \Exception('['.__METHOD__.'] METODO NON PREVISTO! method: <code>'.$method.'</code> search: <code>'.$search.'</code>, val: <code>'.$val.'</code>, key: <code>'.$key.'</code>, args: <code>'.print_r($args,1).'</code>');

			case 'set': return $this->arOrderData[$search] = $val; break;
			default: 	throw new Exception("Metodo non esistente! [$key]");
		endswitch;
	}


	/**
	* 	setCustomData()
	*	imposto un valore da inserire in un arreay custom di arOrderData
	*	@param string $area: di che area voglio impostare il valore? (User,Expedition, ...)
	*	@param string $key : parametro da impostare
	*	@param string $val : eventuale valore da impostare
	*	@return
	*		se get il valore corrispondente alla chiave cercata , false altrimenti
	*		se set true
	*		false in tutti gli altri casi
	*/
	public function setCustomData($area,$key,$val){

		if(!$area || !$key):
			throw new \Exception('['.__METHOD__.'] Manca un parametro alla funzione: area: '.$area.'; key: '.$key,E_USER_ERROR);
		endif;

		if(!isset($this->arOrderData[$area])):
			$this->arOrderData[$area] = array();
		endif;

		return $this->arOrderData[$area][$key] = $val;

	}


	/**
	* 	getCustomData()
	*	chiave per ottenere un valore di arOrderData['cliente']
	*	@param string $area: di che area voglio recuperare il valore? (User,Expedition, ...)
	*	@param string $key : parametro da ottenere
	*	@return il valore corrispondente alla chiave cercata , false altrimenti
	*/
	public function getCustomData($area,$key){

		if(!$area || !$key):
			throw new \Exception('['.__METHOD__.'] Manca un parametro alla funzione: area: '.$area.'; key: '.$key,E_USER_ERROR);
		endif;

		return isset($this->arOrderData[$area]) && isset($this->arOrderData[$area][$key]) ? $this->arOrderData[$area][$key] : false;

	}


	/**
	* 	destroy()
	* 	rimuovo completamente l'elemento da arData
	*	@param string $key : parametro del campo da resettare
	*	@return bool
	*/
	public function destroy($key){

		if(isset($this->arOrderData[$key])):
			unset($this->arOrderData[$key]);
		endif;
		return true;
	}


	/**
	* 	getOrderData()
	* 	ritorna tutti i dati dell'ordine
	*	@param void
	*	@return array : tutti i dati dell'ordine
	*/
	public function getOrderData(){
		return $this->arOrderData;
	}


	/**
	* 	saveOrder()
	* 	salvo i dati dell'ordine
	*	@param void
	*	@return bool true o false
	*/
	public abstract function saveOrder();


	/**
	* 	generateCode()
	* 	genera un codice univoco per quest'ordine
	*	@param void
	*	@return string $code
	*/
	protected function generateCode(){
		$code = NULL;
		// Genero codice
		do {
			$code = strtoupper($this->FixString->codeGenerator(7,'ln','ddsssss'));
			if ($this->Da->countRecords(array(
				"table" => "ordine"
				,"cond" => "WHERE ordine_code = ?"
				,"params"	=> array($code)
			)) > 0):
				$code = NULL;
			endif;
		} while (!$code);

		return $code;
	}


	/**
	* 	saveUserData()
	* 	genera un codice univoco per quest'ordine
	*	@param void
	*	@return bool true o false
	*/
	protected function saveUserData(){

		return;

	}


	/**
	* 	saveExpeditionData()
	* 	salva i dati legati alla spedizione: sia il tipo di spedizione scelta che eventuale indirizzo alternativo
	*	@param void
	*	@return bool true o false
	*/
	protected function saveExpeditionData(){

		return;
	}


	/**
	* 	savePaymentData()
	* 	salva i dati legati alla spedizione
	*	@param void
	*	@return bool true o false
	*/
	protected function savePaymentData(){

		return;
	}


	/**
	* 	saveCartElements()
	* 	salvo i prodotti o altri elementi del carrello (come i pacchetti convenienza)
	*	@param void
	*	@return bool true o false
	*/
	protected function saveCartElements(){

		return $this->saveItems();

	}


	/**
	* 	saveItems()
	* 	salva i prodotti
	*	@param array : i prodotti da salvare
	*	@return bool true o false
	*/
	protected function saveItems($Items = NULL){

		return;
	}


	
	/**
	 * Verifica se elemento è presente in carrello
	 */
	public function isItemInCart($cartKey)	
	{	
		return array_key_exists($cartKey, $this->arProducts);
	}
}

