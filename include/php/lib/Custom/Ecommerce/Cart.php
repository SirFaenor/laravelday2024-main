<?php 
/**
 * Note manu:
 * 
 * - generalizzato la classe predispondendo la gestione di n tipi di prodotto 
 * eliminando la tiplogia specifica "packs".
 * - rinominato i metodi usando un più generico "items".
 * - nella classe padre ci sono ancora i vecchi metodi "products"
 * @todo: generalizzare la classe padre ?
 * 
 */
namespace Custom\Ecommerce;
class Cart extends \Ecommerce\Cart {

	protected $arPacks 	= array();		// pacchetti convenienza
	protected $FixNumber;
	protected $FixString;

    protected $Offer = NULL;
    protected $usableOffers = array();

	

	/**
	* 	setPaymentMethods()
	* 	popola l'array con i metodi di pagamento consentiti
	* 	@param void
	* 	@return void
	*/
	public function setPaymentMethods(){
        return;
	}



	/**
	* 	setExpeditions()
	* 	popola l'array con i tipi di spedizioni
	*	In questo caso le spedizioni vengono filtrate sulla base della relazione con le province di spedizione
	*	e sulla base di carrello massimo e minimo
	* 	@param void
	* 	@return void
	*/
	public function setExpeditions(){
        
        return;

	}



    /**
     * addItem()
     * aggiungo un prodotto all'array arProducts
     * @param $cartKey identificatore univoco per il carrello
     * @param array Item : un prodotto (di qualsiasi tipo), deve rispettare la struttura che controllo all'inizio
     * @param int qta : la quantità aggiunta
     * @param bool add: true o false; se ho già nel carrello un prodotto con quell'id aggiungo o sovrascrivo la quantità
     * @param bool is_present: se è un regalo imposto il prezzo a 0
     * @return void
     *
     * @todo manu valueobject per prodotto in ingresso
     */
    public function addItem($cartKey, \Custom\Ecommerce\Cartable $Item,$qta = 1,$add = false,$is_present = false){

        $Item = $Item->cartArray();

        // controllo la struttura di dati in entrata
        $match = array(
            "id"
            ,"iva"
            ,"title"
            ,"prezzo" 
            ,"prezzo_finale"
                
            // valore assoluto dell'iva (in valuta)
            ,"prezzo_iva"

            // prezzo in valuta senza l'iva
            ,"prezzo_no_iva"
            
            ,"url" 
            ,"discount" 
            ,"alcohol" 
        );

        foreach ($match as $column) {
            if(!array_key_exists($column, $Item)) {
                trigger_error('['.__METHOD__.'] Il prodotto in ingresso non rispetta le chiavi richieste per l\'aggiunta in carrello (chiave mancante "'.$column.'"',E_USER_NOTICE);
            }
        }

        $currentItem = isset($this->arProducts[$cartKey]) ? $this->arProducts[$cartKey] : null;
        
        $Item['qta'] = $add === true && $currentItem ? ($currentItem['qta'] + $qta) : $qta;

        $Item['basepricedata'] = array(
            'prezzo'            => $Item['prezzo']
            ,'prezzo_finale'    => $Item['prezzo_finale']
            ,'prezzo_iva'       => $Item['prezzo_iva']
            ,'prezzo_no_iva'    => $Item['prezzo_no_iva']
        );

        if($is_present):
            $Item['prezzo'] = 0;
            $Item['prezzo_finale'] = 0;
            $Item['prezzo_iva'] = 0;
            $Item['prezzo_no_iva'] = 0;
        endif;

       
        // aggiungo data di inserimento in carrello
        $Item['addedincart'] = isset($currentItem['addedincart']) ? $currentItem['addedincart'] : date('YmdHis');

        $this->arProducts[$cartKey] = $Item;
        $this
            ->updateItemTotals($cartKey)
            ->calcItemsTotalAmount();      

        return true;
    }


    /**
     * updateItemTotals()
     * aggiorno il totale sulla base di prezzo e quantità
     */
    protected function updateItemTotals(string $cartKey){

        $this->arProducts[$cartKey]['subtotal'] = $this->arProducts[$cartKey]['prezzo_finale']*$this->arProducts[$cartKey]['qta'];
        $this->arProducts[$cartKey]['subtotal_iva'] = $this->arProducts[$cartKey]['prezzo_iva']*$this->arProducts[$cartKey]['qta'];
        $this->arProducts[$cartKey]['subtotal_no_iva'] = $this->arProducts[$cartKey]['prezzo_no_iva']*$this->arProducts[$cartKey]['qta'];

        return $this;
    }


    /**
     * removeItem()
     * rimuovo un prodotto dall'array arProducts
     * @param string CartID : chiave univoca dell'elemento in carrello
     * @return true o false
     */
    public function removeItem($CartID){

        if (!$this->getItem($CartID)) {
            throw new \Exception("Elemento non in carrello ".$CartID);
        }

        unset($this->arProducts[$CartID]);
        return true;
    }

    /**
    *   getItems()
    *   ritorna i prodotti disponibili nel carrello
    *   @param $type tipologia richiesta
    *   @return array : tutti i prodotti presenti nel carrello
    */
    public function getItems($type = null){
        
        // se richiesto filtro elenco in base alla tipologia, confrontandola
        // con quella della chiave
        if ($type) {
            return array_filter($this->arProducts, function ($key) use ($type) {
            
                return substr($key,0,2) == $type;
            
            }, ARRAY_FILTER_USE_KEY);
        }

        //..altrimenti ritorno tutto
        return $this->arProducts;

    }


	/**
	* 	calcSpedBaseAmount()
	* 	ritorno l'importo che mi serve per calcolare le spese di spedizione calcolato su
	*	- somma prodotti
	*	@param void
	*	@return float importo
	*/
	protected function calcSpedBaseAmount(){
		return $this->prodAmount;
	}


	/**
	* 	calcSpedAmount()
	* 	calcolo le spese di spedizione sulla base delle soglie e del peso del carrello: scelgo il metodo a seconda se ho l'informazione sulla spedizione o meno
	*	@param array $spedData le informazioni sulla spedizione
	*	@return float importo
	*/
	protected function calcSpedAmount($spedData){

		if(!$spedData):
			throw new \Exception('['.__METHOD__.'] Informazioni sulla spedizione non presenti.',E_USER_ERROR);
		endif;

        $cart_compare = array_key_exists('valore_soglia',$spedData) && $spedData['valore_soglia'] == 'Q' ? $this->countItems() : $this->calcSpedBaseAmount();

        $has_free_expedition = $spedData['soglia'] > 0 && $cart_compare >= $spedData['soglia'];
        if($has_free_expedition):
            $Sped['prezzo_cart'] = 0;
        else:
            if(isset($spedData['prezzo_soglie']) && is_array($spedData['prezzo_soglie']) && count($spedData['prezzo_soglie'])):
                return $this->calcFromFixedThresholds($spedData);
            else:
                // altrimenti ritorno il prezzo
                $valore = isset($spedData['valore']) && array_search($spedData['valore'],array('E','P')) !== false ? $spedData['valore'] : 'E';
                switch($valore):
                    case 'P':   // se le spese di spedizione sono percentuale del carrello, ne calcolo il valore
                        $un_p = round($this->prodAmount/100,2);
                        return $un_p*$spedData['prezzo'];
                        break;
                    default:
                        return $spedData['prezzo'];
                endswitch;
            endif;
        endif;

	}


	/**
	* 	calcFromFixedThresholds()
	* 	calcolo le spese di spedizione sulla base delle soglie e del peso del carrello e del prezzo base passato come parametro
	*	@param array $spedData le informazioni sulla spedizione
	*	@return float importo
	*/
	protected function calcFromFixedThresholds($spedData){

    	# altrimenti calcolo le soglie
		$to_return = NULL;
		$cart_price = $this->prodAmount;
		foreach($spedData['prezzo_soglie'] as $price_soglia => $price):
			if($cart_price >= $price_soglia):	# se il valore del carrello è maggiore della soglia, allora attribuisco quelle spese
				$to_return = $price;
			endif;
		endforeach;

		# nel caso in cui non trovi una soglia assegno il prezzo base
		if($to_return === NULL):
			$to_return 	= (float)$spedData['prezzo'];
		endif;

		return $to_return;
	}

    public function saveOrder() {
        return;
    }
	
	/**
	 * 	saveCartElements()
	 * 	Chiamata da saveOrder, dirotto su saveItems
	 *	@param void
	 *	@return bool true o false
	 */
	protected function saveCartElements(){
		return $this->saveItems();
	}

    /**
    *   normalizeItemToSave()
    *   Mappa i dati di un elemento sul db per il salvataggio
    *   @param array : il prodotto cui normalizzare i dati
    *   @return bool true o false
    * 
    */
    protected function normalizeItemToSave($P){

            $normProd = array();

            /**
             * converto chiavi dell'array in colonne del db
             * lascio tutto esplicito per chiarezza
             */
            $normProd = $P;
            $normProd['quantita']          = $P['qta'];
            $normProd['prezzo2']           = $P['prezzo_finale'];
            $normProd['subtotal']          = $P['prezzo_finale']*$P['qta'];
            $normProd['perc_discount']     = $P['discount'];
            $normProd['id_item']           = $P['id_prt'];
            if(array_key_exists('codice_sconto_id',$P)):
                $normProd['codice_sconto_id']  = $P['codice_sconto_id'];
            endif;
            $normProd['date_insert']       = date('Y-m-d H:i:s');
            if(array_key_exists('id',$P)):
                unset($P['id']);
            endif;

            /**
             * calcolo iva
             * (se non esistono già i valori espliciti in $normProd)
             */
            $normProd['prezzo_no_iva']  = array_key_exists('prezzo_no_iva',$P) ? $P['prezzo_no_iva'] : round($normProd['prezzo'] * 100 / (100+$normProd["iva"]) , 2);
            $normProd['prezzo_iva']     = array_key_exists('prezzo_iva',$P) ? $P['prezzo_iva'] : $normProd['prezzo'] - $normProd['prezzo_no_iva'];
            
            $normProd['prezzo2_no_iva'] = array_key_exists('prezzo2_no_iva',$P) ? $P['prezzo2_no_iva'] : round($normProd['prezzo2'] * 100 / (100+$normProd["iva"]) , 2);
            $normProd['prezzo2_iva'] = array_key_exists('prezzo2_iva',$P) ? $P['prezzo2_iva'] : $normProd['prezzo2'] - $normProd['prezzo2_no_iva'];
               
                // costo totale dell'iva
            $normProd['subtotal_iva'] = array_key_exists('subtotal_iva',$P) ? $P['subtotal_iva'] : $normProd['prezzo2_iva']*$normProd['quantita'];
                // costo totale senza iva
            $normProd['subtotal_no_iva'] = array_key_exists('subtotal_no_iva',$P) ? $P['subtotal_no_iva'] : $normProd['prezzo2_no_iva']*$normProd['quantita'];
            
            if(!strlen($normProd["iva"])) {
                $normProd["iva"] = null;
            }

            return $normProd;

    }


    /**
    *   normalizeExpeditionData()
    *   normalizza i dati di spedizione
    *   @param void
    *   @return void
    */
    public function normalizeExpeditionData(){

        // salvo i dati grezzi della spedizione scelta
        $this->arOrderData['ordine_spedizione']['raw_src'] = serialize($this->selectedExpedition);

        # se ho un id indirizzo recupero le informazioni
        if(isset($this->arOrderData['ordine_spedizione']['id_address']) && !empty($this->arOrderData['ordine_spedizione']['id_address'])):

            $thisAddress = $this->Da->getSingleRecord(array(
                'table'     => 'cliente_spedizioni'
                ,'cond'     => 'WHERE id = :address_id'
                ,'params'   => array('address_id' => $this->arOrderData['ordine_spedizione']['id_address'])
            ));

            if($thisAddress):
                foreach($thisAddress as $k => $v):
                    switch($k):
                        case 'id': 
                        case 'cliente_id': 
                        case 'hash': 
                        case 'prezzo': 
                        case 'date_start': 
                        case 'date_end': 
                        case 'public': 
                        case 'date_insert': 
                        case 'date_update': 
                        case 'soglia': 
                            break;
                        default:
                            $this->arOrderData['ordine_spedizione'][$k] = $v;
                    endswitch;
                endforeach;
            else:
                trigger_error('['.__METHOD__.'] Nessun indirizzo con id <code>'.$this->arOrderData['ordine_spedizione']['id_address'].'</code> per il cliente '.$this->User->getemail(),E_USER_WARNING);
            endif;

        elseif(!empty($this->arOrderData['ordine_spedizione']['custom_data']) && $this->arOrderData['ordine_spedizione']['custom_data'] == 'Y'):

            # ottengo le sigle della nazione
            if (!empty($this->arOrderData['ordine_spedizione']['id_nazione'])):

                $thisNazione = $this->nationData($this->arOrderData['ordine_spedizione']['id_nazione']);
                
                $this->arOrderData['ordine_spedizione']['sigla_nazione']    = strtoupper($thisNazione['sigla_nazione']);
                $this->arOrderData['ordine_spedizione']['nazione']          = $thisNazione['nazione'];
            endif;

            
            # ottengo le sigle della provincia
            if (!empty($this->arOrderData['ordine_spedizione']['id_provincia'])) :

                $thisProv = $this->nationData($this->arOrderData['ordine_spedizione']['id_provincia']);
    
                $this->arOrderData['ordine_spedizione']['sigla_provincia']  = strtoupper($thisProv['sigla_provincia']);
                $this->arOrderData['ordine_spedizione']['provincia']        = $thisProv['provincia'];
    
            endif;

        endif;

    }


	/**
	* 	allowedToCheckout()
	* 	condizioni percui è possibile o meno procedere al checkout
	*	@param void
	*	@return bool true | false
	*/
	public function allowedToCheckout(){
        switch($this->User->getmin_cart_um()):
            case 'PZ':
                if($this->countItems() < $this->User->getmin_cart()):
                    $label_min_cart = $this->User->getmin_cart() == 1 ? '1 '.$this->Lang->returnT('product') : $this->User->getmin_cart().' '.$this->Lang->returnT('products');
                    $label_currentSpesa = $this->countItems() == 1 ? '1 '.$this->Lang->returnT('product') : $this->countItems().' '.$this->Lang->returnT('products');
                    return $this->Lang->returnT('spesa_minima_required_PZ',array('min_cart' => $label_min_cart)).' <br>'.$this->Lang->returnT('spesa_minima_required2_PZ',array('currentSpesa' => $label_currentSpesa));
                endif;
                break;
            default:
                if($this->prodAmount < $this->User->getmin_cart()):
                    return $this->Lang->returnT('spesa_minima_required_EUR',array('min_cart' => $this->Currency->print($this->User->getmin_cart()))).' <br>'.$this->Lang->returnT('spesa_minima_required2_EUR',array('currentSpesa' => $this->Currency->print($this->getProdAmount())));
                endif;
        endswitch;
		
        if ($this->User->getmin_cart_um() == 'PZ' && $this->countItems() < $this->User->getmin_cart()) {
            return $this->Lang->returnT('spesa_minima_required').' <br>'.$this->Lang->returnT('spesa_minima_required2',array('currentSpesa' => $this->getProdAmount()));
        }

		return $this->prodAmount >= $this->User->getmin_cart() ? true : false;
	}


	/**
	* 	countItems()
	* 	ritorna il numero di elementi nel carrello (prodotti*quantità)
	*	@param void
	*	@return int : numero prodotti nel carrello
	*/
	public function countItems(){
		$totale = 0;

		foreach($this->arProducts as $P):
			$totale += $P['qta'];
		endforeach;
		return $totale;
	}



    /**
     * Ritorno dati all'esterno
     * 
     * @param $key chiave per ritornare gruppo specifico dei dati
     */
    public function getOrderData($key = null) {
        return $key && array_key_exists($key, $this->arOrderData) ? $this->arOrderData[$key] : $this->arOrderData;
    }


    /**
     * 
     * Ritorna prodotto in carrello o false se il prodotto non è in carrello
     * 
     */
    public function getItem($key){

        return array_key_exists($key, $this->arProducts) ? $this->arProducts[$key] : null;
        
    }


    
   
    /**
     * Ritorna importo totale dell'iva prodotti
     */
    public function getItemsTotalVatAmount() {

        $total = 0;
        
        $items = $this->getItems();
        
        if (!$items) {return $total;}

        foreach ($items as $key => $rs): 
            $total += $rs["subtotal_iva"];
        endforeach;

        return $total;

    }



    /**
     * COntrolla se contiene prodotti alcolici
     */
    public function hasAlcohol()
    {
        if(!$this->getItems()) {
            return false;
        }

        foreach ($this->getItems() as $key => $item) {
            
            if($item["alcohol"] === true) {
                return true;
            }
            
        }

        return false;
    }

}