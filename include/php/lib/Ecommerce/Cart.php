<?php
/**
###########################################################################
class.Cart
---------------------------------------------------------------------------
Gestione del carrello 
Sezioni principali
-> 	gestione impostazionio del carrello
	- metodi di pagamento 
	- spese di spedizione
-> 	gestione prodotti
	- aggiunta
	- rimozione
	- conteggio
->	importo del carrello
	- calcolo il totale del carrello
	- ritorno il totale del carrello
-> 	codice sconto
	- imposto un codice sconto

TODO INSERIRE CONDIZIONI AGGIUNTIVE (pacco regalo, ...)
TODO SALVARE CONDIZIONI AGGIUNTIVE (pacco regalo, ...)
###########################################################################
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
	* 	checkDiscount()
	* 	verifico se uno sconto è utilizzabile
    *   NB: viene chiamata anche in calcTotalAmount per ricostruire il codice sconto ed usarlo nel calcolo!!
	*	@param string $code : codice sconto; se non presente utilizza il codice sconto già inserito
	*	@return bool true o false se lo sconto può essere applicato o meno
	*/
	protected function checkDiscount($code = NULL){

		$response = array('result' => 0, 'resp_code' => 'NOT_FOUND', 'resp_msg' => '');
		
		if($code !== NULL):
			// verifico che il codice esista e sia attivo (uso LIKE BINARY PER case sensitive)
			$this->Discount = $this->Da->getSingleRecord(array(
				'table'		=> 'codice_sconto'
				,'cond'		=> 'WHERE codice LIKE BINARY :discount_code AND public = "Y" AND (NOW() BETWEEN date_start AND date_end)'
				,'params'	=> array('discount_code' => $code)
			));
		endif;

        if($this->Discount):
			
			$this->Discount['importo_finale'] = 0;				// azzero il valore dello sconto

			$is_usable = $this->checkDiscountUsage();

			if($is_usable === true):
				$response = $this->applyDiscount();
			else:
				if($is_usable !== false):
					$response['resp_code'] = $is_usable;
				endif;
			endif;

		endif; 	// fine esiste codice sconto

		if($response['result'] == 0):
			$this->clearDiscount();
		endif;

		return $response;

	}


	/**
	* 	applyDiscount()
	* 	imposto lo sconto
	*	@param void
	*	@return (bool) posso usare il codice sconto?
	*/
	protected function applyDiscount(){

		$to_return = array('result' => 0, 'resp_code' => 'NOT_FOUND', 'resp_msg' => '');

		if($this->Discount['tipo_sconto'] == 'S'):					// se il codice sconto toglie le spese di spedizione

			// calcolo le spese di spedizione
			if($this->selectedExpedition['prezzo_cart'] == 0):		// se le spese di spedizione sono a 0 annullo il codice sconto e avverto l'utente
				$to_return['resp_code'] = 'EXP_0';
			else:
				$to_return['result'] = 1;
				$to_return['resp_code'] = 'OK';
				$this->Discount['importo_finale'] = $this->selectedExpedition['prezzo_cart'];
			endif;

		else:

			if(!$this->Discount['spesa_minima'] || $this->getProdAmount() > (float)$this->Discount['spesa_minima']):
			
				// verifico se il codice sconto è associato ad uno o più prodotti
				$arAssPrt = $this->Da->getRecords(array(
					'table'		=> 'codice_sconto_rel_prodotto'
					,'columns'	=> array('id_sec')
					,'cond'		=> 'WHERE id_main = :discount_id'
					,'params'	=> array('discount_id' => $this->Discount['id'])
				));

				// se è associato a dei prodotti verifico che nel carrello esistano i prodotti per cui è associato
				if($arAssPrt):
					$this->Discount['ar_prt'] = array();		// salvo i prodotti associati
					$to_return['resp_code'] = 'KO_TO_PRODUCT';	// di base imposto il messaggio come se non avessi nessun prodotto associato nel carrello
					$apply_to_amount = 0;						// totale su cui applicare lo sconto (mi serve se ho più prodotti relazionati)
					
					foreach($arAssPrt as $AP):

						$prod_amount_in_cart = $this->isItemInCart($AP['type'].'_'.$AP['id_sec']);	// ottengo l'importo oresente nel carrello per il prodotto selezionato
						if($prod_amount_in_cart !== false):		// se nel carrello esiste il prodotto associato
							$apply_to_amount += $prod_amount_in_cart;
							$this->Discount['ar_prt'][$AP['id_sec']] = $AP['id_sec'];
						endif;							
					endforeach;

					if($apply_to_amount > 0):
						$to_return['result'] = 1;
						$to_return['resp_code'] = 'OK_TO_PRODUCT';
					endif;
					$this->Discount['importo_finale'] = $this->Discount['valore_sconto'] == 'E' 
												? ($this->Discount['importo_sconto'] > $apply_to_amount ? $apply_to_amount : $this->Discount['importo_sconto'])
												: number_format(($apply_to_amount*$this->Discount['importo_sconto']/100),2,'.','');

				else:

					$to_return['result'] = 1;
					$to_return['resp_code'] = 'OK';
					$this->Discount['importo_finale'] = $this->Discount['valore_sconto'] == 'E' 
												? ($this->Discount['importo_sconto'] > $this->getProdAmount() ? $this->getProdAmount() : $this->Discount['importo_sconto'])
												: number_format(($this->getProdAmount()*$this->Discount['importo_sconto']/100),2,'.','');
					
				endif;	// fine prodotti associati

				# se sto utilizzando solo una parte del codice sconto avverto l'utente
				if($this->Discount['valore_sconto'] == 'E' && $this->Discount['importo_sconto'] > $this->Discount['importo_finale']):
					$to_return['resp_code'] = 'OK_PARTIAL_USE';
				endif;
			else:
				$to_return['resp_code'] = 'KO_MIN_CART';
				$to_return['resp_msg'] 	= $this->Lang->returnT('codice_sconto_min_cart',array('your_cart' => $this->FixNumber->numberToCurrecy($this->getProdAmount()),'min_cart' => $this->FixNumber->numberToCurrecy($this->Discount['spesa_minima'])));
			endif;	// fine spesa_minima

			$this->Discount['importo_finale'] = $this->Discount['importo_finale'] > $this->totalAmount ? $this->totalAmount : $this->Discount['importo_finale'];		
			
		endif;	// fine tipo sconto

		return $to_return;
	}


	/**
	* 	checkDiscountUsage()
	* 	verifico l'utilizzo del codice sconto
	*	@param void
	*	@return (book)
	*/
	protected function checkDiscountUsage(){

		switch($this->Discount['tipo_utilizzo']):
			case 1:		# utilizzo una sola volta
				return $this->checkSingleUse();
				break;
			default:	# utilizzo n volte
				return true;
		endswitch;

	}


	/**
	* 	checkSingleUse()
	* 	verifico quante volte è stato utilizzato il codice sconto
	*	@param void
	*	@return (bool) posso usare il codice sconto?
	*/
	protected function checkSingleUse(){

		// vedo se il codice è già stato utilizzato			
		$Times = $this->countDiscountUsage($this->Discount['codice']);

		if($Times > 0):
			$this->disableDiscountCode($this->Discount['codice']);
		endif;

		return $Times > 0 ? false : true;

	}


	/**
	* 	countDiscountUsage()
	* 	recupero quante volte è stato utilizzato il codice sconto in un ordine completato
	*	@param string $code
	*	@return (int) numero di utilizzi
	*/
	protected function countDiscountUsage($code = NULL){

		if($code === NULL):	# se il codice sconto ha valore null non posso utilizzarlo
			throw new \Exception('['.__FILE__.'] Il codice sconto di cui verificare l\'utilizzo ha valore <code>NULL</code>',E_USER_NOTICE);
			return 1;
		endif;

		// vedo se il codice è già stato utilizzato			
		return $this->Da->countRecords(array(
			'table'		=> 'ordine_codice_sconto'
			,'cond'		=> 'WHERE code = :discount_code'
			,'params'	=> array('discount_code' => $this->Discount['codice'])
		));

	}


	/**
	* 	disableDiscountCode()
	* 	disabilito il codice sconto passato come parametro
	*	@param string $code
	*	@return bool success
	*/
	public function disableDiscountCode($code = NULL){

		if($code === NULL):
			throw new \Exception('['.__FILE__.'] Il codice sconto da disabilitare ha valore <code>NULL</code>',E_USER_NOTICE);
			return true;
		endif;
		return $this->Da->updateRecords(array(
				'table'		=> 'codice_sconto'
				,'data'		=> array(
					'public' => 'N'
				)
				,'cond'		=> 'WHERE codice = :discount_code'
				,'params'	=> array('discount_code' => $code)
			));
	}


	/**
	* 	getDiscount()
	* 	recupero gli sconti attivi (o uno specifico)
	*	@param void
	*	@return array $this->Discount o bool false
	*/
	public function getDiscount(){
		return $this->Discount;
	}



	#########################################################
	#
	#	ORDINE
	#
	#########################################################


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
	public function saveOrder(){

		$status = true;					// di base imposto il risultato su true

		$this->arOrderData['ordine_code'] 		= $this->generateCode();
		$this->arOrderData['totale']			= $this->totalAmount;
		$this->arOrderData['data1'] 			= isset($this->arOrderData['date_insert']) ? $this->arOrderData['date_insert'] : date('Y-m-d H:i:s');
		$this->arOrderData['date_start'] 		= isset($this->arOrderData['date_insert']) ? $this->arOrderData['date_insert'] : date('Y-m-d H:i:s');

		$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine'),$this->arOrderData);

		// recupero da dati legati al cliente
		$dataToSave["takeaway"] = $this->arOrderData["ordine_cliente"]["takeaway"];
		$dataToSave["table_number"] = $this->arOrderData["ordine_cliente"]["table_number"] ?: null;
		
		/**
		 * token "login" per accesso al dettaglio ordine da link in email
		 * (sarà verificato nella pagina di dettaglio ordine)
		 * @todo esternare e centralizzare
		 */
		$dataToSave['token'] = hash("sha512", uniqid().$dataToSave["ordine_code"]);

		$this->orderID = $this->Da->createRecord(array(
			'table'		=> 'ordine'
			,'data'		=> $dataToSave
		));

        $status = $this->orderID;
        $status = $this->saveUserData();
        
        $status = $this->saveExpeditionData();
        $status = $this->savePaymentData();
        $status = $this->saveCartElements();
        $status = $this->saveDiscount();
        $status = $this->saveCustomOptions();
		
        return 	$status;
	}


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

		if(!isset($this->arOrderData['ordine_cliente'])):
			$this->arOrderData['ordine_cliente'] = array();
		endif;
		$this->arOrderData['ordine_cliente']['id_ordine'] 		= $this->orderID;
		$this->arOrderData['ordine_cliente']['id_cat'] 			= $this->User->getid_cat();
		$this->arOrderData['ordine_cliente']['code'] 			= $this->User->getcat_code();
		$this->arOrderData['ordine_cliente']['lang'] 			= $this->User->getid_lang() ?: 0;
        $this->arOrderData['ordine_cliente']['sigla_nazione']   = strtoupper($this->User->getsigla_nazione());
        $this->arOrderData['ordine_cliente']['company_invoice_request']   = isset($this->arOrderData['ordine_cliente']['company_invoice_request']) && $this->arOrderData['ordine_cliente']['company_invoice_request'] == 'Y' ? 'Y' : 'N';

		# ottengo le sigle della nazione
        if (!empty($this->arOrderData['ordine_cliente']['id_nazione'])) :
            $thisNazione = $this->nationData($this->arOrderData['ordine_cliente']['id_nazione']);

            $this->arOrderData['ordine_cliente']['sigla_nazione']   = strtoupper($thisNazione['sigla_nazione']);
            $this->arOrderData['ordine_cliente']['nazione']         = $thisNazione ? $thisNazione['nazione'] : '';
        endif;
		

		
		# ottengo le sigle della provincia
		if (!empty($this->arOrderData['ordine_cliente']['id_provincia'])):
            $thisProv = $this->provinceData($this->arOrderData['ordine_cliente']['id_provincia']);

            $this->arOrderData['ordine_cliente']['sigla_provincia']     = strtoupper($thisProv['sigla_provincia']);
            $this->arOrderData['ordine_cliente']['provincia']           = $thisProv['provincia'];
        endif;
		

		if($this->Da->countRecords(array(
			'table'		=> 'ordine_cliente'
			,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :table_id'
			,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'table_id' => $this->User->getID())
		)) > 0):
			$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_cliente'),$this->arOrderData['ordine_cliente']);
			return $this->Da->updateRecords(array(
				'table'		=> 'ordine_cliente'
				,'data'		=> $dataToSave
				,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :table_id'
				,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'table_id' => $this->User->getID())
			));
		else:
			$this->arOrderData['ordine_cliente']['ordine_code'] 	= $this->arOrderData['ordine_code'];
			$this->arOrderData['ordine_cliente']['id'] 				= $this->User->getID();
			$this->arOrderData['ordine_cliente']['date_insert'] 	= date('Y-m-d H:i:s');
			$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_cliente'),$this->arOrderData['ordine_cliente']);
			return $this->Da->createRecord(array(
				'table'			=> 'ordine_cliente'
				,'data'			=> $dataToSave
				,'preserveId'	=> 1
			));
		endif;


	}


	/**
	* 	saveExpeditionData()
	* 	salva i dati legati alla spedizione: sia il tipo di spedizione scelta che eventuale indirizzo alternativo
	*	@param void
	*	@return bool true o false
	*/
	protected function saveExpeditionData(){

		# imposto i valori legati al metodo di spedizione
		if(!isset($this->arOrderData['ordine_spedizione'])):
			$this->arOrderData['ordine_spedizione'] = array();
		endif;
		$this->arOrderData['ordine_spedizione']['id_ordine'] 	= $this->orderID;
		foreach($this->selectedExpedition as $k => $v):
			$this->arOrderData['ordine_spedizione'][$k] = $v;
		endforeach;

        # salvo inoltre prezzo originario per futuri ricalcoli
        $this->arOrderData['ordine_spedizione']['prezzo'] 			= $this->selectedExpedition["prezzo_cart"];
        $this->arOrderData['ordine_spedizione']['prezzo_source'] 	= $this->selectedExpedition["prezzo"];

        # calcolo il prezzo iva
        $iva = isset($this->arOrderData['ordine_spedizione']['iva']) && !empty($this->arOrderData['ordine_spedizione']['iva']) ? $this->arOrderData['ordine_spedizione']['iva'] : NULL; // di base IVA al 22
        if($iva === NULL && isset($this->arOrderData['ordine_spedizione']['id_iva']) && !empty($this->arOrderData['ordine_spedizione']['id_iva'])):
        	$thisIVA = $this->Da->getSingleRecord(array(
        		'table'		=> 'iva'
        		,'cond'		=> 'WHERE id = :iva_id'
        		,'params'	=> array('iva_id' => $this->arOrderData['ordine_spedizione']['id_iva'])
        	));
        	if($thisIVA):
        		$iva = $thisIVA['valore'];
        	endif;
        endif;

        # valore iva di fallback
        if($iva === NULL):
        	$iva = 22;
        endif;

        $this->arOrderData['ordine_spedizione']['prezzo_iva'] 		= ceil($this->arOrderData['ordine_spedizione']['prezzo']*$iva)/100;
        $this->arOrderData['ordine_spedizione']['prezzo_no_iva'] 	= $this->arOrderData['ordine_spedizione']['prezzo'] - $this->arOrderData['ordine_spedizione']['prezzo_iva'];

        $this->arOrderData['ordine_spedizione']["soglia"] 			= $this->selectedExpedition["soglia"];

        $this->normalizeExpeditionData();

		# registro i dati
		if($this->Da->countRecords(array(
			'table'		=> 'ordine_spedizione'
			,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :table_id'
			,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'table_id' => $this->selectedExpedition['id'])
		)) > 0):
			$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_spedizione'),$this->arOrderData['ordine_spedizione']);
			$returnValue = $this->Da->updateRecords(array(
				'table'		=> 'ordine_spedizione'
				,'data'		=> $dataToSave
				,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :table_id'
				,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'table_id' => $this->selectedExpedition['id'])
			));
		else:
			$this->arOrderData['ordine_spedizione']['ordine_code'] 	= $this->arOrderData['ordine_code'];
			$this->arOrderData['ordine_spedizione']['id'] 			= $this->selectedExpedition['id'];
			$this->arOrderData['ordine_spedizione']['date_insert'] 	= date('Y-m-d H:i:s');

			$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_spedizione'),$this->arOrderData['ordine_spedizione']);
			$returnValue =  $this->Da->createRecord(array(
				'table'			=> 'ordine_spedizione'
				,'data'			=> $dataToSave
				,'preserveId'	=> 1
			));
		endif;

        return $returnValue;
	}


	/**
	* 	normalizeExpeditionData()
	* 	normalizza i dati di spedizione
	*	@param void
	*	@return void
	*/
	public function normalizeExpeditionData(){

		# normalizzo eventuali dati custom di spedizione
		if($this->arOrderData['ordine_spedizione']['custom_data'] == 'Y'):

			# ottengo le sigle della nazione
			if (!empty($this->arOrderData['ordine_spedizione']['id_nazione'])):

                $thisNazione = $this->nationData($this->arOrderData['ordine_spedizione']['id_nazione']);
                
                $this->arOrderData['ordine_spedizione']['sigla_nazione']    = strtoupper($thisNazione['sigla_nazione']);
                $this->arOrderData['ordine_spedizione']['nazione']      	= $thisNazione['nazione'];
            endif;

			
			# ottengo le sigle della provincia
			if (!empty($this->arOrderData['ordine_spedizione']['id_provincia'])) :

                $thisProv = $this->nationData($this->arOrderData['ordine_spedizione']['id_provincia']);
    
                $this->arOrderData['ordine_spedizione']['sigla_provincia']  = strtoupper($thisProv['sigla_provincia']);
                $this->arOrderData['ordine_spedizione']['provincia']        = $thisProv['provincia'];
    
            endif;

			// collego questo indirizzo di spedizione all'utente
			$this->User->saveExpeditionAddress($this->arOrderData['ordine_spedizione']);

		else:
			$this->arOrderData['ordine_spedizione']['custom_data'] == 'N';
		endif;

	}


	/**
	* 	savePaymentData()
	* 	salva i dati legati alla spedizione
	*	@param void
	*	@return bool true o false
	*/
	protected function savePaymentData(){

		if(!isset($this->arOrderData['ordine_pagamento'])):
			$this->arOrderData['ordine_pagamento'] = array();
		endif;
		$this->arOrderData['ordine_pagamento']['id_ordine'] 	= $this->orderID;
		foreach($this->selectedPaymentMethod as $k => $v):
			$this->arOrderData['ordine_pagamento'][$k] = $v;
		endforeach;


		# registro i dati
		if($this->Da->countRecords(array(
			'table'		=> 'ordine_pagamento'
			,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :payment_id'
			,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'payment_id' => $this->selectedPaymentMethod['id'])
		)) > 0):
			$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_pagamento'),$this->arOrderData['ordine_pagamento']);
			return $this->Da->updateRecords(array(
				'table'		=> 'ordine_pagamento'
				,'data'		=> $dataToSave
				,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :payment_id'
				,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'payment_id' => $this->selectedPaymentMethod['id'])
			));
		else:
			$this->arOrderData['ordine_pagamento']['ordine_code'] 	= $this->arOrderData['ordine_code'];
			$this->arOrderData['ordine_pagamento']['id'] 			= $this->selectedPaymentMethod['id'];
			$this->arOrderData['ordine_pagamento']['date_insert'] 	= date('Y-m-d H:i:s');
			$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_pagamento'),$this->arOrderData['ordine_pagamento']);
			return $this->Da->createRecord(array(
				'table'		=> 'ordine_pagamento'
				,'data'		=> $dataToSave
				,'preserveId'	=> 1
			));
		endif;

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

		$result = true;
		$Items = $Items === NULL ? $this->arProducts : $Items;

		if(count($Items) > 0):
			foreach($Items as & $P):
				$P['ordine_code'] 		= $this->arOrderData['ordine_code'];
				$P['id_ordine'] 		= $this->orderID;
				$P['id_prt'] 			= $P['id'];
				unset($P['id']);

				$normProd = $this->normalizeItemToSave($P);
				$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_prodotto'),$normProd);
				if(!$this->Da->createRecord(array(
						'table'		=> 'ordine_prodotto'
						,'data'		=> $dataToSave
					))):
					$result = false;
				endif;
			endforeach;
		else:
			$result = false;
		endif;

		return $result;
	}


	/**
	* 	normalizeItemToSave()
	* 	normalizza i dati di prodotto per il salvataggio
	*	@param array : il prodotto cui normalizzare i dati
	*	@return array prodotto normalizzato
	*/
	protected function normalizeItemToSave($P){

			# ottengo il prezzo di questo prodotto per questo cliente
			$thisProdInfoPrezzo = $this->Da->getSingleRecord(array(
				'table'		=> 'prodotto_rel_cliente_cat'
				,'cond'		=> 'WHERE id_main = :prt_id AND id_sec = :user_cat_id'
				,'params'	=> array('prt_id' => $P['id_prt'],'user_cat_id' => $this->User->getid_cat())
			));
			
			if(!$thisProdInfoPrezzo):
				throw new \Exception('['.__METHOD__.'] Non ci sono informazioni da recuperare per il prodotto '.$P['title'].' con id '.$P['id_sec'],E_USER_ERROR);
			endif;

			$prezzo_iva 			= isset($P['ar_prezzi']['prezzo_iva']) 			? $P['ar_prezzi']['prezzo_iva'] 			: $thisProdInfoPrezzo['prezzo_iva'];
			$prezzo_no_iva 			= isset($P['ar_prezzi']['prezzo_no_iva']) 			? $P['ar_prezzi']['prezzo_no_iva'] 		: $thisProdInfoPrezzo['prezzo_no_iva'];
			$prezzo_finale_iva 		= isset($P['ar_prezzi']['prezzo_finale_iva']) 	? $P['ar_prezzi']['prezzo_finale_iva'] 	: $thisProdInfoPrezzo['prezzo2_iva'];
			$prezzo_finale_no_iva 	= isset($P['ar_prezzi']['prezzo_finale_no_iva']) 	? $P['ar_prezzi']['prezzo_finale_no_iva'] 	: $thisProdInfoPrezzo['prezzo2_no_iva'];
			$perc_discount 			= isset($P['ar_prezzi']['discount']) 				? $P['ar_prezzi']['discount'] 				: $thisProdInfoPrezzo['perc_discount'];

			$P['quantita'] 			= $P['qta'];
			$P['prezzo'] 			= $P['ar_prezzi']['prezzo'];
			$P['prezzo_iva']		= $prezzo_iva;
			$P['prezzo_no_iva'] 	= $prezzo_no_iva;
			$P['prezzo2'] 			= $P['ar_prezzi']['prezzo_finale'];
			$P['prezzo2_iva'] 		= $prezzo_finale_iva;
			$P['prezzo2_no_iva'] 	= $prezzo_finale_no_iva;
			$P['subtotal'] 			= $P['ar_prezzi']['prezzo_finale']*$P['qta'];
			$P['subtotal_iva'] 		= $prezzo_finale_iva*$P['qta'];
			$P['subtotal_no_iva']	= $prezzo_finale_no_iva*$P['qta'];
			$P['perc_discount'] 	= $perc_discount;
			$P['url'] 				= $P['url_page'];
			$P['date_insert'] 		= date('Y-m-d H:i:s');

			return $P;

	}


	/**
	* 	saveDiscount()
	* 	salva il codice sconto
	*	@param void
	*	@return bool true o false
	*/
	protected function saveDiscount(){
		if($this->Discount):
			if($this->checkDiscountUsage() === true):
				return $this->registerDiscountData(); 
			else:
				$this->clearDiscount();
				throw new \Box\Exceptions\BadRequestException('['.__METHOD__.'] Nessun codice sconto memorizzato nel carrello');
				return false;
			endif;
		else:
			return true;
		endif;
	}


	/**
	* 	registerDiscountData()
	* 	registra i dati del codice sconto
	*	@param void
	*	@return bool true o false
	*/
	protected function registerDiscountData(){

		$to_return = true;

		if(!isset($this->arOrderData['ordine_codice_sconto'])):
			$this->arOrderData['ordine_codice_sconto'] = array();
		endif;

		$this->arOrderData['ordine_codice_sconto'] = $this->Discount;
		$this->arOrderData['ordine_codice_sconto']['title']	= $this->arOrderData['ordine_codice_sconto']['nome'];
        $this->arOrderData['ordine_codice_sconto']['code']  = $this->arOrderData['ordine_codice_sconto']['codice'];
        $this->arOrderData['ordine_codice_sconto']['id_iva']  = isset($this->arOrderData['ordine_codice_sconto']['id_iva']) ? $this->arOrderData['ordine_codice_sconto']['id_iva'] : 0;
		unset($this->arOrderData['ordine_codice_sconto']['id']);

		if(!$this->arOrderData['ordine_codice_sconto']['spesa_minima']):
			$this->arOrderData['ordine_codice_sconto']['spesa_minima'] = 0;
		endif;

		# registro i dati
		if($this->Da->countRecords(array(
			'table'		=> 'ordine_codice_sconto'
			,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :table_id'
			,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'table_id' => $this->Discount['id'])
		)) > 0):
			$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_codice_sconto'),$this->arOrderData['ordine_codice_sconto']);
			return $this->Da->updateRecords(array(
				'table'		=> 'ordine_codice_sconto'
				,'data'		=> $dataToSave
				,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :table_id'
				,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'table_id' => $this->Discount['id'])
			));
		else:
			$this->arOrderData['ordine_codice_sconto']['ordine_code'] 	= $this->arOrderData['ordine_code'];
			$this->arOrderData['ordine_codice_sconto']['id_ordine'] 	= $this->orderID;
			$this->arOrderData['ordine_codice_sconto']['id'] 			= $this->Discount['id'];
			$this->arOrderData['ordine_codice_sconto']['date_insert'] 	= date('Y-m-d H:i:s');
			$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_codice_sconto'),$this->arOrderData['ordine_codice_sconto']);
			return $this->Da->createRecord(array(
				'table'		=> 'ordine_codice_sconto'
				,'data'		=> $dataToSave
				,'preserveId'	=> 1
			));
		endif;


		return $to_return;

	}



	/**
	* 	saveCustomOptions()
	* 	salva le opzioni custom
	*	@param void
	*	@return bool true o false
	*/
	protected function saveCustomOptions(){
		$to_return = true;
		if(isset($this->arOrderData['ordine_opzioni_aggiuntive']) && is_array($this->arOrderData['ordine_opzioni_aggiuntive']) && count($this->arOrderData['ordine_opzioni_aggiuntive']) > 0):
			foreach($this->arOrderData['ordine_opzioni_aggiuntive'] as & $CO):
				# registro i dati
				if($this->Da->countRecords(array(
					'table'		=> 'ordine_opzioni_aggiuntive'
					,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :table_id'
					,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'table_id' => $CO['id'])
				)) > 0):
					$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_opzioni_aggiuntive'),$CO);
					if(!$this->Da->updateRecords(array(
						'table'		=> 'ordine_opzioni_aggiuntive'
						,'data'		=> $dataToSave
						,'cond'		=> 'WHERE ordine_code = :ordine_code AND id = :table_id'
						,'params'	=> array('ordine_code' => $this->arOrderData['ordine_code'],'table_id' => $CO['id'])
					))):
						$to_return = false;
					endif;
				else:
					$CO['ordine_code'] 	= $this->arOrderData['ordine_code'];
					$CO['id'] 			= $CO['id'];
					$CO['date_insert'] 	= date('Y-m-d H:i:s');
					$dataToSave = \Utility\DataUtility::MapData($this->Da->getColumns('ordine_opzioni_aggiuntive'),$CO);
					if(!$this->Da->createRecord(array(
						'table'		=> 'ordine_opzioni_aggiuntive'
						,'data'		=> $dataToSave
						,'preserveId'	=> 1
					))):
						$to_return = false;
					endif;
				endif;
			endforeach;
		endif;

		return $to_return;
	}


	/**
	 * Verifica se elemento è presente in carrello
	 */
	public function isItemInCart($cartKey)	
	{	
		return array_key_exists($cartKey, $this->arProducts);
	}
}

