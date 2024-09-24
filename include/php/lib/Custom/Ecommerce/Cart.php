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
        
        
        // di base imposto la nazione dell'utente se utente è privato, per i negozi la nazione non ha ripercussione
        $id_nazione     = $this->User->getid_nazione();
		$id_provincia   = $this->User->getid_provincia();

        // se è stata impostata una nazione di fatturazione nel carrello imposto quella
        if(isset($this->arOrderData['ordine_cliente'])):
            if(
                isset($this->arOrderData['ordine_cliente']['id_nazione']) 
                && !empty($this->arOrderData['ordine_cliente']['id_nazione'])
            ):
                $id_nazione = $this->FixNumber->fix($this->arOrderData['ordine_cliente']['id_nazione']);
            endif;

            if(
                isset($this->arOrderData['ordine_cliente']['id_provincia']) 
                && !empty($this->arOrderData['ordine_cliente']['id_provincia'])
            ):
                $id_provincia = $this->FixNumber->fix($this->arOrderData['ordine_cliente']['id_provincia']);
            endif;
        endif;

        # se ho gli indirizzi di spedizione, di base prendo il default
        if($this->User->getshipping_addresses()):
            $id_nazione     = $this->User->getshipping_addresses()[0]['id_nazione'];
            $id_provincia   = $this->User->getshipping_addresses()[0]['id_provincia'];
        endif;


        // se è stata impostata una nazione di spedizione nel carrello imposto quella
        if(isset($this->arOrderData['ordine_spedizione'])):
            switch(true):
                case (isset($this->arOrderData['ordine_spedizione']['custom_data']) // se prendo dati custom
                        && $this->arOrderData['ordine_spedizione']['custom_data'] == 'Y'):
			
                    // se ho impostato la nazione di spedizione la uso, altrimenti quella di fatturazione
                    $id_nazione = !empty($this->arOrderData['ordine_spedizione']['id_nazione']) 
                                    ? $this->FixNumber->fix($this->arOrderData['ordine_spedizione']['id_nazione'])
                                    : $this->FixNumber->fix($this->arOrderData['ordine_cliente']['id_nazione']);

                    # verifico se esistono delle province per questa nazione

                    if(!empty($this->arOrderData['ordine_spedizione']['id_provincia'])):

                        $id_provincia = $this->checkProvince($this->arOrderData['ordine_spedizione']['id_provincia'],$id_nazione) !== false
                                        ? $this->arOrderData['ordine_spedizione']['id_provincia']
                                        : NULL;
                    else:
                        $id_provincia = isset($this->arOrderData['ordine_cliente']['id_provincia']) && !empty($this->arOrderData['ordine_cliente']['id_provincia']) && $this->checkProvince($this->arOrderData['ordine_cliente']['id_provincia'],$id_nazione) !== false
                                        ? $this->arOrderData['ordine_cliente']['id_provincia']
                                        : NULL;
                    endif;
                    break;
                case ((!isset($this->arOrderData['ordine_spedizione']['custom_data'])
                        || $this->arOrderData['ordine_spedizione']['custom_data'] == 'N') && isset($this->arOrderData['ordine_spedizione']['id_address'])):  // se non ho dati custom e ho impostato un indirizzo di spedizione
                    $shippingData = $this->Da->getSingleRecord(array(
                        'table'     => 'cliente_spedizioni'
                        ,'cond'     => 'WHERE id = :id AND cliente_id = :cliente_id'
                        ,'params'   => array('id' => $this->arOrderData['ordine_spedizione']['id_address'],'cliente_id' => $this->User->getID())
                    ));
                    if(!$shippingData):
                        trigger_error('['.__METHOD__.'] Non trovo alcun indirizzo con id <code>'.$this->arOrderData['ordine_spedizione']['id_address'].'</code> per l\'utente <code>'.$this->User->getID().'</code>');
                    endif;

                    $id_nazione = $shippingData['id_nazione'];
                    $id_provincia = $shippingData['id_provincia'];
                    $this->arOrderData['ordine_spedizione']['title_address'] = $shippingData['title'];
                    break;
            endswitch;
        endif;


        // verifico se la nazione è associata a quelche metodo di spedizione, sennò prendo la nazione di default
        $avalSpeds = $this->Da->getRecords([
            "table" => "elenco_stati_rel_spedizioni"
            ,"cond" => "WHERE id_main = ?"
            ,"params" => [$id_nazione]
        ]);
        if (!$avalSpeds) {
            $id_nazione = $this->User->getDefaultOption('id_nazione');
        } 
        

		if(!$id_nazione):
			$id_nazione = 1; # fallback di sicurezza nel caso qualcosa vada storto (ITALIA)
		endif;

        $arProvince = $this->Da->getRecords(array(
            'model'     => 'PROVINCE'
            ,'cond'     => 'AND X.id_cat = ? AND lang = ? ORDER BY XL.title ASC'
            ,'params'   => array($id_nazione,$this->Lang->lgId)
        ));

        // verifico se la provincia impostata è tra quelle della nazione, altrimenti annullo
        $has_provincia = false;
        if($arProvince):
            foreach($arProvince as $PR):
                if($PR['id'] == $id_provincia):
                    $has_provincia = true;
                    break;
                endif;
            endforeach;
        endif;

        // cerco se la nazione ha province
        if(!$arProvince || $has_provincia === false):
            $id_provincia = NULL;
        endif;

        # di base se la nazione è Italia e non ho la provincia allora è TV
        if($id_nazione == 1 && !$id_provincia):
            $id_provincia = $this->User->getDefaultOption('id_provincia');
        endif;



        if($id_provincia):
            // recupero le spedizioni
            $arSpeds = $this->Da->getRecords(array(
                'model'     => 'SPEDIZIONI'
                ,'cond'     => 'AND XL.lang = :lang_id AND XR.id_sec = :cat_id
                                    AND X.id IN (SELECT id_sec FROM elenco_province_rel_spedizioni WHERE id_main = :province_id) HAVING published ORDER BY X.position ASC'
                ,'params'   => array('lang_id' => $this->Lang->lgId,'cat_id' => $this->User->getid_cat(),'province_id' => $id_provincia)
            ));
            // se non ho spedizioni imposto la provincia dell'ordine come provincia base
            if(!$arSpeds):
                trigger_error('['.__METHOD__.'] Non esiste spedizione per la provincia '.$id_provincia.'; è stata reimpostata la provincia di default',E_USER_NOTICE);
                $id_provincia = $this->arOrderData['ordine_cliente']['id_provincia'] = $this->User->getDefaultOption('id_provincia');
                $arSpeds = $this->Da->getRecords(array(
                    'model'     => 'SPEDIZIONI'
                ,'cond'     => 'AND XL.lang = ? AND XR.id_sec = ?
                                        AND X.id IN (SELECT id_sec FROM elenco_province_rel_spedizioni WHERE id_main = ?) HAVING published ORDER BY X.position ASC'
                    ,'params'   => array($this->Lang->lgId,$this->User->getid_cat(),$id_provincia)
                ));
            endif;
        else:
            // recupero le spedizioni
            $arSpeds = $this->Da->getRecords(array(
                'model'     => 'SPEDIZIONI'
                ,'cond'     => 'AND XL.lang = ? AND XR.id_sec = ?
                                    AND X.id IN (SELECT id_sec FROM elenco_stati_rel_spedizioni WHERE id_main = ?) HAVING published ORDER BY X.position ASC'
                ,'params'   => array($this->Lang->lgId,$this->User->getid_cat(),$id_nazione)
            ));
        endif;


        if($arSpeds):
            foreach($arSpeds as $k => $Sped):

                // recupero le relazioni con le categorie utente se attivo
                $Rel = $this->Da->getSingleRecord(array(
                    'table'     => 'spedizioni_rel_cliente_cat'
                    ,'cond'     => 'WHERE id_main = :sped_id AND id_sec = :user_id_cat AND attivo > 0'
                    ,'params'   => array('sped_id' => $Sped['id'], 'user_id_cat' => $this->User->getid_cat())
                ));

                if(!$Rel): 
                    trigger_error('Nessuna relazione per il cliente con categoria <code>'.$this->User->getid_cat().'</code> e spedizione <pre>'.print_r($Sped,1).'</pre>',E_USER_NOTICE);
                endif;


                if($Rel):
                    // valore del carrello sulla per verificare la SPEDIZIONE GRATUITA
                    $Sped['prezzo']         = $Rel['prezzo'];
                    $Sped['valore']         = $Rel['valore'];
                    $Sped['soglia']         = $Rel['soglia'];
                    $Sped['prezzo_soglie']  = $Rel['prezzo_soglie'];
                    $Sped['valore_soglia']  = $Rel['valore_soglia'];
                    $Sped['attivo']         = $Rel['attivo'];
                    $Sped['prezzo_cart'] = $this->calcSpedAmount($Rel);

                    if(isset($Rel['prezzo_soglie']) && strlen($Rel['prezzo_soglie'])):
                        $Sped['prezzo_soglie'] = $this->parseSoglie($Rel['prezzo_soglie']);
                    endif;
                else:
                    // se non ho relazione, prendo il valore collegato alla spedizione
                    $Sped['prezzo_cart'] = $this->calcSpedAmount($Sped);
                endif;
                $this->addExpedition($Sped['code'],$Sped);
            endforeach;
        endif;


        if(!$this->arExpeditions):
            trigger_error('['.__METHOD__.'] Non è stato possibile impostare alcun tipo di spedizione', E_USER_ERROR);
        endif;

        // di base imposto il primo metodo di pagamento come quello selezionato
        if(!$this->selectedExpedition || array_key_exists($this->selectedExpedition['code'],$this->arExpeditions) === false || count($this->arExpeditions) == 1):
            $this->selectedExpedition = NULL;
            $this->selectedExpedition = reset($this->arExpeditions);
        endif;

        $this->selectedExpedition['prezzo_cart'] = $this->arExpeditions[$this->selectedExpedition['code']]['prezzo_cart'];


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


	/**
	* 	parseSoglie()
	* 	calcolo le spese di spedizione sulla base delle soglie e del peso del carrello e del prezzo base passato come parametro
	*	@param array $spedData le informazioni sulla spedizione
	*	@return float importo
	*/
	protected function parseSoglie($soglie){

		$arExpeditionAmountSteps = array();	// array che conterrà tutte le spedizioni con relative soglie

		$strThresholds = nl2br($soglie);

		$arThresholds = preg_split('/\<br\s?\/?\>/',$strThresholds,-1,PREG_SPLIT_NO_EMPTY);

        // se non ho soglie valide, ritorno il prezzo base
		if(!$arThresholds || !is_array($arThresholds) || !count($arThresholds)):
			trigger_error('['.__METHOD__.'] Non riesco ad elaborare alcuna informazione sulle soglie.',E_USER_WARNING);
			return array();
		endif;

		foreach($arThresholds as $TH):
			$arTH = explode('=',$TH);
			$arExpeditionAmountSteps[(float)trim($arTH[0])] = (float)trim($arTH[1]);
		endforeach;
        return $arExpeditionAmountSteps;
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
    *   checkDiscount()
    *   verifico se uno sconto è utilizzabile
    *   NB: viene chiamata anche in calcTotalAmount per ricostruire il codice sconto ed usarlo nel calcolo!!
    *   @param string $code : codice sconto; se non presente utilizza il codice sconto già inserito
    *   @return bool true o false se lo sconto può essere applicato o meno
    */
    protected function checkDiscount($code = NULL){

        $response = array('result' => 0, 'resp_code' => 'NOT_FOUND', 'resp_msg' => '');

        if($code !== NULL):
            // verifico che il codice esista e sia attivo (uso LIKE BINARY PER case sensitive)
            $this->Discount = $this->Da->getSingleRecord(array(
                'table'     => 'codice_sconto'
                ,'cond'     => 'WHERE codice LIKE BINARY ? AND public = "Y" AND (NOW() BETWEEN date_start AND date_end)'
                ,'params'   => array($code)
            ));
        endif;

        if($this->Discount):
            
            $this->Discount['importo_finale'] = 0;              // azzero il valore dello sconto
            $this->Discount['info'] = '';

            $is_usable = $this->checkDiscountUsage();

            if($is_usable === true):
                $response = $this->applyDiscount();
            else:
                if($is_usable !== false):
                    $response['resp_code'] = $is_usable;
                endif;
            endif;

        endif;  // fine esiste codice sconto

        if($response['result'] == 0):
            $this->clearDiscount();
        endif;

        return $response;

    }


    /**
    *   applyDiscount()
    *   imposto lo sconto
    *   @param void
    *   @return (bool) posso usare il codice sconto?
    */
    protected function applyDiscount(){

        $to_return = array('result' => 0, 'resp_code' => 'NOT_FOUND', 'resp_msg' => '');
        

        if(!$this->Discount['spesa_minima'] || $this->getProdAmount() > (float)$this->Discount['spesa_minima']):

             // recupero prodotti associati al codice sconto, se ci sono con il nome che mi servirà poi per popolare le info
            $arAssPrt = $this->Da->customQuery("
                SELECT 
                X.id_sec
                ,X.type
                ,P.gest_name AS name
                FROM codice_sconto_rel_prodotto AS X 
                INNER JOIN prodotto AS P ON P.id = X.id_sec
                WHERE id_main = ?"
                ,array($this->Discount['id'],$this->Lang->lgId)
            );

            // se il codice sconto toglie le spese di spedizione
            // (in questo caso non è prevista condizione su spesa minima)
            if($this->Discount['tipo_sconto'] == 'S'):
             
                // procedo per controllo successvi
                $isValid = true;

                // se le spese di spedizione sono a 0 annullo il codice sconto
                if($this->selectedExpedition['prezzo_cart'] == 0): 
                    
                    $to_return['resp_code'] = 'EXP_0';

                    $isValid = false;
                
                // se non ci sono prodotti associati, il codice sconto vale per tutti (logica concordata)..
                elseif (!$arAssPrt) :
                    
                    $isValid = true;
                
                // ..se invece ho qualche prodotto associato, procedo a controllare il contenuto del carrello
                else :

                    // array contenente solo gli id dei prodotti associati
                    $arAssIds = array();
                    foreach ($arAssPrt as $key => $assPrt): 
                        array_push($arAssIds, $assPrt["id_sec"]);
                    endforeach;

                    // verifico che TUTTI i prodotti del carrello siano associati al codice sconto
                    foreach ($this->arProducts as $childKey => $item):

                        // controllo id del genitore (è quello ad essere associato al codice sconto)
                        // se un solo prodotto del carrello non è associato al codice sconto, il codice
                        // automaticamente decade (non serve continuare ciclo)
                        if (!in_array($item["model_id"], $arAssIds)) :
                            
                            $isValid = false;
                            $to_return['resp_code'] = 'KO_PRODUCTS_NOT_ALLOWED';
                            
                            break;

                        endif;

                    endforeach;

                endif;    

                // codice valido
                if ($isValid) :
                    $to_return['result'] = 1;
                    $to_return['resp_code'] = 'OK';
                        // importo del codice sconto è il costo della spedizione attuale
                    $this->Discount['importo_finale'] = $this->selectedExpedition['prezzo_cart'];
                    $this->Discount['info'] = $this->Lang->returnT('discount_info_EXP');

                    // azzero il prezzo della spedizione per i conteggi successivi NOTA J: teoricamente non dovrebbe servire annullare le spese di spedizione perché il codice sconto inserisce lo stesso imposto in negativo
                    // $this->selectedExpedition['prezzo_cart'] = 0;            
                else :
                    $to_return['result'] = 0;
                    $this->Discount['importo_finale'] = 0;
                endif;

            // fine tipo sconto = 'S'
            else:

                if(!$this->Discount['spesa_minima'] || $this->getProdAmount() > (float)$this->Discount['spesa_minima']):

                    // se è associato a dei prodotti verifico che nel carrello ci siano elementi a cui è associato
                    if($arAssPrt):
                        $this->Discount['ar_prt'] = array();        // salvo i prodotti associati
                        $to_return['resp_code'] = 'KO_TO_PRODUCT';   // di base imposto il messaggio come se non avessi nessun prodotto associato nel carrello
                        $apply_to_amount = 0;                       // importo su cu applicare lo sconto

                        $strAssName = '';                           // nomi dei prodotti associati allo sconto
                        $discountToProducts = 0;                    // lo sconto è stato applicato a tutti i prodotti nel carrello?

                        foreach($arAssPrt as $AP):

                            /**
                             * id_sec rappresenta l'id del padre, non della variante specifica.
                             * Passo quindi al metodo isItemInCart il flag isParent = true, se il metodo
                             * non trova varianti, passo oltre.
                             */
                            $strAssName .= ', '.$AP['name'];
                            if($this->isItemInCart($AP['type'].'_'.$AP['id_sec'])):
                                $thisProduct = $this->arProducts[$AP['type'].'_'.$AP['id_sec']];
                                $prod_amount_in_cart =  $thisProduct['prezzo_finale']*$thisProduct['qta']; 
                            else:
                                $discountToAllCartProducts = false;
                                continue;
                            endif;
                            $discountToProducts++;
                            
                            
                            // se nel carrello esiste il prodotto associato aumento il valore dello sconto
                            // con il costo del prodotto
                            $apply_to_amount += $prod_amount_in_cart;
                            $this->Discount['ar_prt'][$AP['id_sec']] = $AP['id_sec'];

                        endforeach;

                        $strAssName = substr($strAssName,2);

                        if($apply_to_amount > 0):
                            $to_return['result'] = 1;
                            $to_return['resp_code'] = 'OK_TO_PRODUCT';
                        endif;

                        $this->Discount['info'] = $discountToProducts == count($this->arProducts) ? $this->Lang->returnT('discount_info_TOTAL') : $this->Lang->returnT('discount_info_PARTIAL',array('products_list' => $strAssName));
                        $this->Discount['importo_finale'] = $this->Discount['valore_sconto'] == 'E' 
                                                    ? ($this->Discount['importo_sconto'] > $apply_to_amount ? $apply_to_amount : $this->Discount['importo_sconto'])
                                                    : number_format(($apply_to_amount*$this->Discount['importo_sconto']/100),2,'.','');

                    else:

                        // se non ci sono prodotti associati, il codice sconto vale per tutti (logica concordata)
                        $to_return['result'] = 1;
                        $to_return['resp_code'] = 'OK';
                        $this->Discount['info'] = $this->Lang->returnT('discount_info_TOTAL');
                        $this->Discount['importo_finale'] = $this->Discount['valore_sconto'] == 'E' 
                                                    ? $this->Discount['importo_sconto'] 
                                                    : number_format(($this->prodAmount*$this->Discount['importo_sconto']/100),2,'.','');
                        
                    endif;  // fine prodotti associati

                else:
                    $to_return['resp_code'] = 'KO_MIN_CART';
                    $to_return['resp_msg']   = $this->Lang->returnT('discount_code_min_cart',array('your_cart' => $this->Currency->print($this->getProdAmount()),'min_cart' => $this->Currency->print($this->Discount['spesa_minima'])));
                endif;  // fine spesa_minima

            endif;  // fine tipo sconto

            $this->Discount['importo_finale'] = $this->Discount['importo_finale'] > $this->totalAmount ? $this->totalAmount : $this->Discount['importo_finale'];
            
        endif;  // fine numero utilizzi codice sconto

        if($to_return['result'] == 0):
            $this->clearDiscount();
        endif;

        return $to_return;

    }


    /**
    *   checkDiscountUsage()
    *   verifico l'utilizzo del codice sconto
    *   @param void
    *   @return (array)
    */
    protected function checkDiscountUsage(){

        $to_return = false;
        if($this->Discount):
            switch($this->Discount['tipo_utilizzo']):
                case 1:     # utilizzo una sola volta
                    $to_return = $this->checkSingleUse();
                    break;
                case 3:     # utilizzo una sola volta per utente
                    $to_return = $this->checkSingleUsePerUser();
                    break;
                case 4:     # utilizzo per il compleanno dell'utente
                    $to_return = $this->checkBirthdayValidity();
                    break;
                default:    # utilizzo n volte
                    $to_return = true;
            endswitch;
        endif;

        return $to_return;

    }


    /**
    *   checkSingleUsePerUser()
    *   verifico quante volte è stato utilizzato il codice sconto
    *   @param void
    *   @return (bool) posso usare il codice sconto?
    */
    protected function checkSingleUsePerUser(){
        
        $Times = $this->Da->customQuery(
            "SELECT 
            COUNT(OCS.code) AS n
            FROM ordine_codice_sconto AS OCS
            INNER JOIN ordine AS O ON O.id = OCS.id_ordine 
            INNER JOIN ordine_cliente AS OC ON O.id = OC.id_ordine
            WHERE OCS.code = ? AND OC.email = ? AND O.conferma_ordine = 'Y'"
        ,array($this->Discount['codice'],$this->User->getemail()));

        return $Times[0]['n'] > 0 ? false : true;

    }


    /**
    *   countDiscountUsage()
    *   recupero quante volte è stato utilizzato il codice sconto in un ordine completato
    *   @param string $code
    *   @return (int) numero di utilizzi
    */
    protected function countDiscountUsage($code = NULL){

        if($code === NULL): # se il codice sconto ha valore null non posso utilizzarlo
            throw new \Exception('['.__FILE__.'] Il codice sconto di cui verificare l\'utilizzo ha valore <code>NULL</code>',E_USER_NOTICE);
            return 1;
        endif;

        $to_return = 0;

        # recupero l'uso del codice sconto nei vari ordini per poi verificare che l'ordine sia completato
        $arDiscountInOrder = $this->Da->getRecords(array(
            'table'     => 'ordine_codice_sconto'
            ,'cond'     => 'WHERE code = :discount_code'
            ,'params'   => array('discount_code' => $this->Discount['codice'])
        ));

        # se lo sconto è già stato utilizzato, verifico che l'ordine sia completato
        if($arDiscountInOrder):
            foreach($arDiscountInOrder as $DO):
                $n = $this->Da->countRecords(array(
                    'table'     => 'ordine'
                    ,'cond'     => 'WHERE ordine_code = :ordine_code AND conferma_utente = "Y" AND conferma_ordine = "Y"'
                    ,'params'   => array('ordine_code' => $DO['ordine_code'])
                ));
                if($n > 0):
                    $to_return = $n;
                    break;
                endif;
            endforeach;
        endif;

        return $to_return;
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