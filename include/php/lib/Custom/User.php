<?php
/**
*	class User
*	estendo la classe \Site\User.php
*
*	MODIFICHE
*
*	__construct()
*	1.	gestisco login e dati utente in 2 variabili
*		a. la sessione definisce se l'utente è loggato e memorizza tutti i suoi dati
*		b. un cookie ha un id tramite il quale recupero solamente alcuni dati che decido
*	2.	imposto dei valori di default per l'utente anche se non è loggato e non ci sono dati 
*		a. imposto una categoria utente di default
*		b. imposto la nazione di default (Italia)
*		c. definisco le restrizioni per l'utente, sulla base della categoria
*
*	checkLogin()
*	impostato la ricerca nome utente sia su codice fiscale / partita iva che su email
*	
*	normalizeData()
*	adattamenti per la tabella attuale
*/

namespace Custom;
class User extends \Site\User {
    
    /**
     * trait per recupero dati di una nazione
     */
    use \Utility\Traits\NationData;
    
    /**
     * trait per recupero dati di una provincia
     */
    use \Utility\Traits\ProvinceData;

	private $cookieID; 					// id della tabella dove salvo le sessione utente
	private $arCookieFields = array(	// campi del cookie da salvare
									'nome'
									,'id_cat'
									,'id_lang'
									,'cat_code'
									,'ragione_sociale'
									,'id_nazione'
									,'sigla_nazione'
									,'nazione'
									,'newsletter'
								);

	private $arDefaultOptions = array(
									'id_nazione'		=> 1
									,'nazione'			=> 'Italia'
									,'sigla_nazione'	=> 'IT'
									,'id_provincia'		=> 95
									,'provincia'		=> 'Treviso'
									,'sigla_provincia'	=> 'TV'
                                    ,'cookieDuration'   => 3600
								);

    /**
     * @var boolean
     * Contrassegna utente loggato come admin nelle veci di un negozio
     */
    protected $isAdminAsB2b = false;


	// colonne della tabella dove salvo i dati utente: mi serve per mappare i dati da salvare
	protected $tableColumns = NULL;


	public function __construct($Da, $Lang, \Utility\FixNumber $FixNumber, \Utility\FixString $FixString, $arDefaultOptions = NULL){

		$this->Da 			= $Da;
		$this->Lang 		= $Lang;
		$this->FixNumber 	= $FixNumber;
		$this->FixString 	= $FixString;

		if($arDefaultOptions && is_array($arDefaultOptions) && count($arDefaultOptions)):
			$this->arDefaultOptions = array_merge($this->arDefaultOptions,$arDefaultOptions);
		endif;

		if(isset($_SESSION['User']['id'])):	// se imposto l'id l'utente è loggato
            $this->ID = $_SESSION['User']['id'];
            $this->isAdminAsB2b = !empty($_SESSION['User']['isAdminAsB2b']) ? $_SESSION['User']['isAdminAsB2b'] : false;
		endif;

		if(isset($_COOKIE['User'])):	// se ho il cookie l'utente ha i dati
			$this->cookieID = is_numeric($_COOKIE['User']) ? (int)$this->FixNumber->fix($_COOKIE['User']) : NULL;
		endif;

		$this->setDefaults();
		switch($this->isLoggedIn()): # recupero i dati a seconda del tipo di login
			case 1:		// se sono loggato recupero tutti i dati utente
				if(!$this->fetchData()): 
					$this->ID = NULL;
				endif;
				break;
		endswitch;

		$this->setPermits();			// imposto i permessi

		$this->tableColumns = $this->Da->getColumns($this->dataTable);
	}


	/**
	* 	__destruct()
	* 	Salvo l'utente se così impostato
	*	Registro o cancello la sessione o 
	*	il cookie utente se è loggato o meno
	*/
	public function __destruct(){

		// salvo l'utente
		if($this->saveData === true):
			$this->registerData();
		endif;

		switch($this->isLoggedIn()):
			case 1:
				$_SESSION['User']['id'] = $this->ID;
				break;
			default:	// se l'utente non è loggato
				$this->unsetUserData();
		endswitch;

        // salvo stato login utente speciale
        $_SESSION['User']['isAdminAsB2b'] = $this->isAdminAsB2b();

	}


	/**
	* 	LoginByID()
	* 	funzione chiamata al momento del login dell'utente al sito
	*	@param int ID : utente
	*	@param bool full_search se false cerca solo  tra gli utenti attivi, altrimenti tra tutti; se true attivo l'utente se lo trovo
	*	@return bool true o false a seconda che il login sia andato a buon fine o meno
	*/
	public function LoginByID($ID = null, $full_search = false){

		$this->unsetUserData();															// resetto i dati in sessione

		if($full_search !== true):
			$r =  $this->Da->getSingleRecord(array(
				'table'		=> $this->dataTable
				,'columns'	=> array('id')
				,'cond'		=> 'WHERE attivo = "Y" AND (NOW() BETWEEN date_start AND date_end) AND deleted = "N" AND id = :user_id'
				,'params'	=> array('user_id' => $ID)
			));
			$this->ID = $r ? $r['id'] : $r;
		else:
			// altrimenti attivo l'utente
			$r =  $this->Da->getSingleRecord(array(
				'table'		=> $this->dataTable
				,'columns'	=> array('id')
				,'cond'		=> 'WHERE id = :user_id'
				,'params'	=> array('user_id' => $ID)
			));
			if($r):
				$this->Da->updateRecords(array(
					'table'		=> $this->dataTable
					,'data'		=> array(
										'attivo' 	=> 'Y'
										,'deleted'	=> 'N'
									)
					,'cond'		=> 'WHERE id = :user_id'
					,'params'	=> array('user_id' => $ID)
				));
				$this->ID = $r['id'];
			else:
				$this->ID = $r;
			endif;
		endif;

		if($this->ID):
			if(!$this->fetchData()): // se ho l'id recupero e normalizzo i dati su array utente
				$this->ID = NULL;
			else:
				$this->setPermits();
			endif;
		endif;

		return $this->isLoggedIn();

	}


	/**
	* 	softLoginByID()
	* 	imposto l'utente come loggato senza sovrascrivere eventuali dati di sessione
	*	@param int ID : utente
	*	@return bool true o false a seconda che il login sia andato a buon fine o meno
	*/
	public function softLoginByID($ID = null){

		$r =  $this->Da->getSingleRecord(array(
			'table'		=> $this->dataTable
			,'columns'	=> array('id')
			,'cond'		=> 'WHERE attivo = "Y" AND (NOW() BETWEEN date_start AND date_end) AND deleted = "N" AND id = :user_id'
			,'params'	=> array('user_id' => $ID)
		));
		$this->ID = $r ? $r['id'] : $r;

		if($this->ID):																	// se ho l'id recupero e normalizzo i dati su array utente
			$this->setPermits();
		endif;

		return $this->isLoggedIn();

	}
    
    /**
     * checkLogin()
     * verifico effettivamente che l'utente con i parametri passati esista
     * NB: tengo una funzione separata in modo che sia estensibile se c'è bisogno di query specifiche
     * 
     * Per gli utenti registrati nel vecchio sito (registration <> 'Y')  il controllo sulla 
     * password viene fatto in md5
     * 
     * @param string user : nome utente
     * @param string pass : password
     * @return int id dell'utente | false
     */
    protected function checkLogin($user,$pass){

        /**
         * recupero prima l'utente
         */
        $rsUser =  $this->Da->getSingleRecord(array(
            'table'     => $this->dataTable
            ,'columns'  => array("id","registration")
			,'cond'     => "WHERE attivo = 'Y' AND (NOW() BETWEEN date_start AND date_end) AND deleted = 'N' AND email = ?" 
			,'params'	=> array($user)
        ));
        if (!$rsUser) {return false;}
        

        /**
         * controllo la password in maniera differenziata
         * (vecchi utenti sono in md5)
         */
		$hash_pass 	= $this->hashOnLogin !== false ? strtoupper(hash($this->hashOnLogin,$pass)) : $pass;
		$sql_pass 	= $this->hashOnLogin !== false ? ' UPPER('.$this->sqlPassField.')' : $this->sqlPassField;


		$r =  $this->Da->getSingleRecord(array(
			'table'		=> $this->dataTable
			,'columns'	=> array('id')
			,'cond'		=> "WHERE id = ".$rsUser["id"]." AND ".$sql_pass
			,'cond'		=> "WHERE id = ? AND ".$sql_pass." = ?"
			,'params'	=> array($rsUser["id"],$hash_pass)
		));

		return $r ? $r['id'] : $r;
	}


	/**
	* 	isLoggedIn()
	* 	verifico se l'utente è loggato
	*	3 possibili valori:
	*		2	: l'utente è stato loggato tramite cookie e si vedono solamente alcuni dati pubblici memorizzati in una tabella-cookie
	*		1 	: l'utente è loggato
	*		0 	: l'utente non è loggato
	*	@param void
	*	@return aggiunto valore int 1 se ho solamente i dati da cookie
	*/
	public function isLoggedIn(){
		if($this->ID):
			return 1;
		else:
			return $this->cookieID !== NULL ? 2 : 0;
		endif;
	}


	/**
	* 	unsetUserData()
	* 	cancello i dati utente
	*	@param void
	*	@return void
	*/
	protected function unsetUserData(){
		parent::unsetUserData();
		$this->emptyForms();
		$this->cookieID = NULL;
		unset($_COOKIE['User']);
        if(!headers_sent()):
			setcookie('User','',(time()-$this->arDefaultOptions['cookieDuration']-36000),'/','',true);
        endif;
	}


	/**
	* 	emptyForms()
	* 	svuoto tutti i forms
	*	@param void
	*	@return void
	*/
	protected function emptyForms(){
		if(count($_SESSION)):
			foreach($_SESSION as $k => & $v):
				if(preg_match('/\_form$/',$k) && is_array($v)):
					array_walk($v,function(&$vv,$vk){
						switch($vk):
							case 'data'			: $vv = array(); break;
							case 'response'		: $vv = array('result' => 0, 'msg' => '', 'data' => array()); break;
							case 'redirect_url'	: $vv = ''; break;
							case 'has_post'		: $vv = 0; break;
						endswitch;
					});
				endif;
			endforeach;
		endif;
	}


	protected function getDefault($search){
		return array_key_exists($search,$this->arDefaultOptions) ? $this->arDefaultOptions[$search] : null;
	}


	/**
	 * getDefaultOption
	 * Ottengo il valore di default per un parametro passato
	 * @param string
	 * @return var
	 */
	public function getDefaultOption($search){
		if(!$search):
			trigger_error('['.__METHOD__.'] Non è stato passato alcun valore da cercare',E_USER_ERROR);
		endif;
		return $this->getDefault($search);
	}


	/**
	* 	fetchData()
	* 	recupero di dati dell'utente
	*	NB: tengo una funzione separata in modo che sia estensibile se c'è bisogno di query specifiche
	*	@param void
	*	@return void
	*/
	protected function fetchData(){

		$rawdata = $this->Da->getSingleRecord(array(
			'table'		=> $this->dataTable
			,'cond'		=> 'WHERE attivo = "Y" AND (NOW() BETWEEN date_start AND date_end) AND deleted = "N" AND id = ?'
			,'params'	=> array($this->ID)
		));

		return $this->normalizeData($rawdata);

	}


	/**
	* 	fetchCookieData()
	* 	recupero i dati in sessione dell'utente e cancello le sessioni più vecchie di 6 mesi
	*	@param void
	*	@return void
	*/
	protected function fetchCookieData(){

		// cancello tutte le sessioni più vecchie di 180 giorni
		$this->Da->deleteRecords(array(
			'table'		=> 'cliente_cookie'
			,'cond'		=> 'WHERE DATEDIFF(NOW(),date_insert) > 180'
		));

		$rawdata = $this->Da->getSingleRecord(array(
			'table'		=> 'cliente_cookie'
			,'cond'		=> 'WHERE id = ?'
			,'params'	=> array($this->cookieID)
		));

		# aggiunto Jacopo 27/01/2017 perché funzione non utilizzata
		$rawdata = NULL;
		###########################################################

		return $this->normalizeData($rawdata);

	}


	/**
	* 	registerCookieData()
	* 	salvo i dati per il cookie
	*	se esiste il record con il cookieID aggiorno
	*	altrimenti creo nuovo utente
	*	@param void
	*	@return void
	*/
	private function registerCookieData(){

		$dataToSave = $this->arData;

		foreach($this->arData as $k => $D):
			if(array_search($k,$this->arCookieFields) === false):
				unset($dataToSave[$k]);
			endif;
		endforeach;
		$dataToSave['date_insert'] = date('Y-m-d H:i:s');

		if($this->cookieID):
			$this->Da->updateRecord(array(
				'table'		=> 'cliente_cookie'
				,'data'		=> $dataToSave
				,'cond'		=> 'WHERE id = ?'
				,'params'	=> array($this->cookieID)
			));
		else:
			$this->Da->createRecord(array(
				'table'		=> 'cliente_cookie'
				,'data'		=> $dataToSave
			));
			$this->cookieID = $this->Da->insertId();
		endif;

	}


	/**
	*	normalizeData()
	*	ridefiniti i campi da recuperare o meno
	*/
	protected function normalizeData($rawdata){

        $nazione = null;
        $prov = null;
		
        if($rawdata):
			$rawdata['published'] = 'Y';
			foreach($rawdata as $k => $v):
				switch($k):
					case $this->sqlPassField: 
					case 'posizione': 
					case 'attivo': 
					case 'date_start': 
					case 'date_end': 
					case 'public': 
					case 'date_insert': 
					case 'date_update': 
					case 'deleted': 
					case 'sigla_provincia':
					case 'provincia':
					case 'sigla_nazione':
					case 'nazione':
						break;

					case 'id_cat':
						$cat = $this->Da->getSingleRecord(array(
							'model'		=> 'CLIENTE_CAT'
							,'cond'		=> 'AND X.id = ? AND XL.lang = ? HAVING published ORDER BY X.position ASC, X.id ASC'
							,'params'	=> array($v,$this->Lang->lgId)
						));
						$this->arData[$k] 			= $v;
						$this->arData['cat_code'] 	= $cat ? $cat['code'] : null;
						$this->arData['title_cat'] 	= $cat ? $cat['title'] : null;
						$this->arData['min_cart'] 	= $cat ? $cat['min_cart'] : null;
						break;

					case 'id_provincia':
						$this->arData[$k] = $v;
						if($v):						
							$prov = $this->Da->getSingleRecord(array(
								'model'		=> 'PROVINCE'
								,'cond'		=> 'AND X.id = ? AND XL.lang = ?'
								,'params'	=> array($v,$this->Lang->lgId)
							));
							if($prov):
								$this->arData['sigla_provincia'] = $prov['sigla'];
								$this->arData['provincia'] = $prov['title'];
							else:
								trigger_error('['.__METHOD__.'] Nessuna provincia per id '.$v.' e lingua '.$this->Lang->lgId,E_USER_ERROR);
							endif;
						endif;
						break;

					case 'id_nazione':
						$this->arData[$k] = $v;
						if($v):
							$nazione = $this->Da->getSingleRecord(array(
								'model'		=> 'NAZIONI'
								,'cond'		=> 'AND X.id = ? AND XL.lang = ?'
								,'params'	=> array($v,$this->Lang->lgId)
							));
							if($nazione):
								$this->arData['sigla_nazione'] = $nazione['sigla'];
								$this->arData['nazione'] = $nazione['title'];
							else:
								trigger_error('['.__METHOD__.'] Nessuna nazione per id '.$v.' e lingua '.$this->Lang->lgId,E_USER_ERROR);
							endif;
						endif;
						break;

					case 'data_nascita':
						$this->arData[$k] 			= $v;
						$this->arData['data_nascita_f'] = $v && $v != NULL ? implode('/',array_reverse(explode('-',$v))) : '';
						$this->arData['birthday'] 	=  $v && $v != NULL ? (int)substr($v,8,2).' '.$this->Lang->returnT('month_'.substr($v,5,2)) : '-';
						break;

					default:
						$this->arData[$k] = $v;
				endswitch;
			endforeach;
			return true;
		else:
			$this->unsetUserData();
			return false;
		endif;

	}

    
	/**
	* 	registerData()
	* 	se utente esiste aggiorno i dati
	*	altrimenti creo nuovo utente
	*	@param void
	*	@return void
	*/
	public function registerData(){

		try {

			$dataToSave = $this->prepareData();

			if(!$dataToSave):
				throw new \Exception('['.__METHOD__.'] Nessun dato da salvare presente');
			endif;

			// rimuovo le chiavi non presenti nella tabella
			if($dataToSave && $this->tableColumns && is_array($this->tableColumns)):
				foreach($dataToSave as $k => $V):
					if(array_search($k,$this->tableColumns) === false):
						unset($dataToSave[$k]);
					endif;
				endforeach;
			endif;

			$userExists = $this->userExists();

			switch(true):
				case ($userExists === 1):						// nel caso l'utente sia registrato lo aggiorno solamente se loggato
					if($this->isLoggedIn() == 1):
						unset($dataToSave['id']);				// rimuovo l'id per sicurezza
						$this->Da->updateRecords(array(
							'table'		=> $this->dataTable
							,'data'		=> $dataToSave
							,'cond'		=> 'WHERE id = :id'
							,'params'	=> array('id' => $this->ID)
						));
					endif;
					break;

				case ($userExists === 2):						// dati presenti ma registrazione non completata

					$this->Da->updateRecords(array(
						'table'		=> $this->dataTable
						,'data'		=> $dataToSave
						,'cond'		=> 'WHERE id = :id'
						,'params'	=> array('id' => $this->arData['registration_id'])
					));
					$this->ID = $this->arData['registration_id'];
					break;

				default:										// utente non registrato
					$this->Da->createRecord(array(	
						'table'		=> $this->dataTable
						,'data'		=> $dataToSave
					));
					$this->ID = $this->Da->insertId();
			endswitch;

			return true;

		} catch(\Exception $e){
			trigger_error('['.__METHOD__.'] '.$e->getMessage(),E_USER_WARNING);
			return false;
		}

	}


	/**
	*	prepareData()
	* 	prepara i dati per essere salvati nella tabella utente
	*	@param void
	*	@return array $dataPrepared
	*/
	protected function prepareData(){

		$this->arData = parent::prepareData();

		# imposto la lingua
		if(!isset($this->arData['id_lang']) || empty($this->arData['id_lang'])):
			$this->arData['id_lang'] = $this->Lang->lgId;
		endif;


		# imposto i dati della categoria
		$userCat = $this->Da->getSingleRecord(array(
			'table'		=> 'cliente_cat'
			,'cond'		=> 'WHERE id = ?'
			,'params'	=> array($this->arData['id_cat'])
		));
		if(!$userCat):
			trigger_error('['.__METHOD__.'] Categoria cliente con id '.$this->arData['id_cat'].' non trovata', E_USER_ERROR,E_USER_ERROR);
		endif;

		if(!isset($this->arData['cat_code']) || empty($this->arData['cat_code'])):
			$this->arData['cat_code'] = $userCat['code'];
		endif;


		# imposto i dati della nazione
		$userNazione = NULL;
		if(!empty($this->arData['id_nazione'])):
	        $userNazione = $this->nationData($this->arData['id_nazione']);
	        if(!$userNazione):
                trigger_error('['.__METHOD__.'] Nazione con id '.$this->arData['id_nazione'].' non trovata', E_USER_ERROR);
            endif;

			$this->arData['sigla_nazione'] = $userNazione['sigla_nazione'];
			$this->arData['nazione'] = $userNazione['nazione'];
		endif;


		# imposto i dati della provincia
		$userProvincia = NULL;
        if (!empty($this->arData['id_provincia'])) :
            $userProvincia = $this->provinceData($this->arData['id_provincia']);
			$this->arData['sigla_provincia'] = $userProvincia['sigla_provincia'];
			$this->arData['provincia'] = $userProvincia['provincia'];
        endif;


		# imposto il codice legale
		if(!isset($this->arData['legal_code']) || empty($this->arData['legal_code'])):
			if($this->arData['id_cat'] == 1 && isset($this->arData['codice_fiscale'])):
				$this->arData['legal_code'] = $this->arData['codice_fiscale'];
			endif;
			if($this->arData['id_cat'] != 1 && isset($this->arData['partita_iva'])):
				$this->arData['legal_code'] = $this->arData['partita_iva'];
			endif;
		endif;

		return $this->arData;
	}


	/**
	* 	setShippingAddress()
	* 	creo o aggiorno un indirizzo di spedizione
	*	@param array arData: se passo questi valori li uso, altrimenti uso quelli di default dell'utente
	*	@param int $id_address (se passo l'id sono in update, altrimenti in creazione)
	*	@return bool
	*/
	public function setShippingAddress($arData = NULL,$id_address = NULL){

		try {

			if(!$this->ID):
				trigger_error('Non ho id utente di cui creare l\'indrizzo',E_USER_ERROR);
			endif;

			$is_update = $id_address && $id_address !== NULL ? true : false;	# sono in inserimento o in update?

			if(!$is_update):  # se sono in inserimento recupero l'elenco degli indirizzi
				$this->arData['shipping_addresses'] = $this->getShippingAddresses();

				$strCompare = $this->addressToString($arData);

				# veriico che non esista un indirizzo uguale a quello che si sta tentando di inserire
				if(count($this->arData['shipping_addresses'])):
					foreach($this->arData['shipping_addresses'] as $SA):
						if($strCompare == $this->addressToString($SA)):
							$id_address = $SA['id'];
							$is_update = true;
							break;
						endif;
					endforeach;
				endif;
			endif;

			# se arData è null e ho già un indirizzo non proseguo
			if(($arData === NULL || (is_array($arData) && !count($arData))) && isset($this->arData['shipping_addresses']) && count($this->arData['shipping_addresses']) > 0):
				trigger_error('È già presente un indirizzo: per inserirne uno nuovo è necessario specificarne i valori',E_USER_ERROR);
			endif;

			# se non vengono passati dei dati, prendo quelli dell'utente (vale solo se devo inserire il primo indirizzo)
			$dataToSave = $arData !== NULL ? $arData : $this->arData;

			unset($dataToSave['id']);
			$dataToSave['cliente_id'] = $this->ID;


			# imposto i dati della nazione
			if(!empty($dataToSave['id_nazione'])):
		        $userNazione = $this->nationData($dataToSave['id_nazione']);
				$dataToSave['sigla_nazione'] = $userNazione['sigla_nazione'];
				$dataToSave['nazione'] = $userNazione['nazione'];
			endif;


			# imposto i dati della provincia
			$userProvincia = null;
	        if (!empty($dataToSave['id_provincia'])) :
	            $userProvincia = $this->provinceData($dataToSave['id_provincia']);
				$dataToSave['sigla_provincia'] = $userProvincia['sigla_provincia'];
				$dataToSave['provincia'] = $userProvincia['provincia'];
	    	else:
	    		$dataToSave['id_provincia'] = NULL;
	        endif;



			if(!isset($dataToSave['title']) || empty($dataToSave['title'])):
				$dataToSave['title'] = $this->Lang->returnT('shipping_address').' '.(isset($this->arData['shipping_addresses']) ? count($this->arData['shipping_addresses'])+1 : '1');
			endif;

			# se tra i dati da salvare imposto anche l'indirizzo di default allora imposto tutti gli altri non default
			if(isset($dataToSave['default_address']) && $dataToSave['default_address'] == 'Y'):
	            $this->Da->updateRecords(array(
	                'table'     => 'cliente_spedizioni'
	                ,'data'     => array('default_address' => 'N')
	                ,'cond'     => 'WHERE cliente_id = :cliente_id'
	                ,'params'   => array('cliente_id' => $this->ID)
	            ));
			endif;

			if($id_address):
				$this->Da->updateRecords(array(
					'table'		=> 'cliente_spedizioni'
					,'data'		=> $dataToSave
					,'cond'		=> 'WHERE id = :address_id'
					,'params'	=> array('address_id' => $id_address)
				));
			else:
				
				# se sono in creazione ed è il primo indirizzo lo imposto come default
				if(!isset($dataToSave['default_address']) && !count($this->arData['shipping_addresses'])):
					$dataToSave['default_address'] = 'Y';
				endif;
				$dataToSave['date_start'] = $dataToSave['insert'] = '{{NOW()}}';

				$table_columns = $this->Da->getColumns('cliente_spedizioni');
				$queryData = array();
				foreach($dataToSave as $k => $v):
					if(array_search($k,$table_columns) !== false):
						$queryData[$k] = $v;
					endif;
				endforeach;
				$id_address = $this->Da->createRecord(array(
					'table'		=> 'cliente_spedizioni'
					,'data'		=> $queryData
				));

				# aggiorno al volo gli indirizzi di spedizione
				$this->getShippingAddresses();
			endif;
			return $id_address;

		} catch(\Exception $e){

			trigger_error('['.__METHOD__.'] '.$e->getMessage(),E_USER_WARNING);
			return false;

		}

	}


	/**
	* 	getShippingAddresses()
	* 	recupero gli indirizzi di spedizione assegnati all'utente
	*	@param void
	*	@return array gli indirizzi di spedizione
	*/
	public function getShippingAddresses(){

		try {

			if(!$this->ID):
				trigger_error('Non ho id utente di cui recuperare gli indrizzi',E_USER_ERROR);
			endif;

			$arAddresses = $this->Da->getRecords(array(
				'table'		=> 'cliente_spedizioni'
				,'cond'		=> 'WHERE cliente_id = :cliente_id ORDER BY FIELD(default_address, "Y") DESC, default_address, position'
				,'params'	=> array('cliente_id' => $this->ID)
			));

			# verifico che ce ne sia almeno uno di default, altrimenti imposto il primo
			if($arAddresses):
				
				$has_default = false;
				$has_shipping_default_key = NULL;	// definisco la chiave per il primo indirizzo valido per le spedizioni

				foreach($arAddresses as $k => & $AD):

					# controllo che l'indirizzo sia compatibile con una spedizione
					$AD['has_shipping'] = $this->Da->countRecords(array(
									            'table'     => 'elenco_stati_rel_spedizioni'
									            ,'column'	=> 'id_main'
									            ,'cond'     => 'WHERE id_main = :nazione_id'
									            ,'params'   => array('nazione_id' => $AD['id_nazione'])
									        ));

					# se l'indirizzo non può accettare spedizioni, allora non puòà essere indirizzo di default
					if(!$AD['has_shipping'] && $AD['default_address'] == 'Y'):
						$AD['default_address'] = 'N';
						$this->setShippingAddress($AD,$AD['id']);
					endif;

					# se accetta spedizioni ed è la prima chiave allora imposto il valore
					if($has_shipping_default_key === NULL && $AD['has_shipping']):
						$has_shipping_default_key = $k;
					endif;

					# se accetta spedizioni ed è la prima chiave allora imposto il valore
					if($AD['default_address'] == 'Y'):
						$has_default = true;
					endif;
				endforeach;

				# se non ho un indirizzo di default e ne ho uno che potrebbe esserlo, lo imposto
				if($has_default === false && $has_shipping_default_key !== NULL):
					$defaultAddress = $arAddresses[$has_shipping_default_key];
					$this->Da->updateRecords(array(
						'table'		=> 'cliente_spedizioni'
						,'data'		=> array('default_address' => 'Y')
						,'cond'		=> 'WHERE id = :address_id'
						,'params'	=> array('address_id' => $defaultAddress['id'])
					));
				endif;
			endif;
			return $arAddresses ? $arAddresses : array();

		} catch(\Exception $e){

			trigger_error('['.__METHOD__.'] '.$e->getMessage(),E_USER_WARNING);
			return array();

		}

	}


	/**
	* 	deleteShippingAddress()
	* 	creo o aggiorno un indirizzo di spedizione
	*	@param int $id_address (se passo l'id sono in update, altrimenti in creazione)
	*	@return bool
	*/
	public function deleteShippingAddress($id_address = NULL){

		try{
			if(!$id_address):
				trigger_error('Non ho id dell\'indrizzo da cancellare',E_USER_ERROR);
			endif;

			$this->Da->deleteRecords(array(
				'table'		=> 'cliente_spedizioni'
				,'cond'		=> 'WHERE id = :id'
				,'params'	=> array('id' => $id_address)
			));
		} catch(\Exception $e){
			trigger_error('['.__METHOD__.'] '.$e->getMessage(),E_USER_WARNING);
			return false;
		}

	}


	/**
	* 	addressToString()
	* 	trasformo l'array dell'indirizzo in stringa per poterlo confrontare
	*	@param array $arData l'array con tutte le informazioni
	*	@return string
	*/
	protected function addressToString($arData){

		$strCompare = 	isset($arData['nome']) && !empty($arData['nome']) 		? $arData['nome'] : '';
		$strCompare .= 	isset($arData['cognome']) && !empty($arData['cognome']) ? $arData['cognome'] : '';
		$strCompare .= 	isset($arData['ragione_sociale']) && !empty($arData['ragione_sociale']) ? $arData['ragione_sociale'] : '';
		$strCompare .= 	isset($arData['cap']) && !empty($arData['cap']) ? $arData['cap'] : '';
		$strCompare .= 	isset($arData['citta']) && !empty($arData['citta']) ? $arData['citta'] : '';
		$strCompare .= 	isset($arData['indirizzo']) && !empty($arData['indirizzo']) ? $arData['indirizzo'] : '';
		$strCompare .= 	isset($arData['note_indirizzo']) && !empty($arData['note_indirizzo']) ? $arData['note_indirizzo'] : '';
		$strCompare .= 	isset($arData['id_provincia']) && !empty($arData['id_provincia']) ? $arData['id_provincia'] : '';
		$strCompare .= 	isset($arData['sigla_provincia']) && !empty($arData['sigla_provincia']) ? $arData['sigla_provincia'] : '';
		$strCompare .= 	isset($arData['provincia']) && !empty($arData['provincia']) ? $arData['provincia'] : '';
		$strCompare .= 	isset($arData['id_nazione']) && !empty($arData['id_nazione']) ? $arData['id_nazione'] : '';
		$strCompare .= 	isset($arData['sigla_nazione']) && !empty($arData['sigla_nazione']) ? $arData['sigla_nazione'] : '';
		$strCompare .= 	isset($arData['nazione']) && !empty($arData['nazione']) ? $arData['nazione'] : '';

		return $strCompare;
	}



	/**
	* 	setPermits()
	* 	imposto i permessi e restrizioni per l'utente se ho una categoria
	*	@param void
	*	@return void
	*/
	public function setPermits(){
		
		return;
		
	}
    

    /**
     *  saveExpeditionAddress()
     *  salvo un indirizzo di spedizione collegato all'utente ma solo
     *  per utente privato
     *  @param array $arData: i dati da salvare
     *  @return bool
    */
    public function saveExpeditionAddress($arData){

        /**
         * uso metodo standard se utente è privato
         */
        if ($this->getid_cat() == 1) {
            return parent::saveExpeditionAddress($arData);
        }

        return true;
    }


    /**
     * contrassegna utente loggato come admin nelle veci di un negozio
     * Se flag == null rstituisce stato corrente
     * @param mixed $flag
     */
    public function isAdminAsB2b($flag = null) {
        if ($flag === null) {return $this->isAdminAsB2b;}
        return $this->isAdminAsB2b = $flag ?: false;
    }



	/**
	* 	userExists()
	* 	verifica se un utente esiste e il suo stato di registrazione;
	*	imposto come proprietà dell'utente lo stato di registrazione
	*	@param array $arData -> array con i dati da confrontare: le chiavi dell'array sono le colonne su cui confrontare il valore
	*	@return int (1: utente registrato e attivo; 0: utente non registrato; > 1 stati diversi)
	*/
	public function userExists($arData = NULL){

		$this->arData['registration_status'] = 0;

		$arSqlWhere = array();
		if($arData === NULL):
			if(isset($this->arData['email']) && strlen($this->arData['email'])): $arSqlWhere[] = 'email = "'.$this->arData['email'].'"'; endif;
			if(isset($this->arData['id_cat'])):
				switch($this->arData['id_cat']):
					case 1:
						if(isset($this->arData['codice_fiscale']) && strlen($this->arData['codice_fiscale'])): 
							$arSqlWhere[$this->arData['codice_fiscale']] = 'codice_fiscale = ?'; 
						endif;
						break;
					default:
						if(isset($this->arData['partita_iva']) && strlen($this->arData['partita_iva'])): 
							$arSqlWhere[$this->arData['partita_iva']] = 'partita_iva = ?'; 
						endif;
				endswitch;
			endif;
		else:
			foreach($arData as $col => $val):
				$arSqlWhere[$val] = $col.' = ?';
			endforeach;
		endif;


		if(count($arSqlWhere) <= 0):
			$this->arData['registration_status'] = 1; # se non ho condizioni faccio finta che l'utente esista già
		else:

			$myUser = $this->Da->getSingleRecord(array(
						'table'		=> $this->dataTable
						,'cond'		=> 'WHERE '.(count($arSqlWhere) > 1 ? '('.implode(' OR ',array_values($arSqlWhere)).')' : implode(' OR ',array_values($arSqlWhere))).' AND deleted = "N"'	# non cancellati
						,'params'	=> array_keys($arSqlWhere)
					));
			if($myUser):
				$this->arData['registration_status'] 	= $myUser['registration'] == 'Y' ? 1 : 2;
				$this->arData['registration_id'] 		= $myUser['id'];
			endif;

		endif;

		return $this->arData['registration_status'];

	}


	/**
	* 	canRegister()
	* 	un utente può essere registrato?
	*	@param array $arData -> array con i dati da confrontare: le chiavi dell'array sono le colonne su cui confrontare il valore
	*	@return bool
	*/
	public function canRegister($arData = NULL){

		return $this->userExists($arData) === 1 ? false : true;

	}


	/**
	* 	unRegister()
	* 	imposto la registrazione su N
	*	@param void
	*	@return void
	*/
	protected function unRegister(){
		if(!$this->ID):
			trigger_error('['.__METHOD__.'] ID utente di cui annullare la registrazione non presente <pre>'.print_r($this->arData,1).'</pre>',E_USER_WARNING);
		else:
			$this->Da->updateRecords(array(
				'table'		=> $this->dataTable
				,'data'		=> array(
					'registration'	=> 'N'
				)
				,'cond'		=> 'WHERE id = :id'
				,'params'	=> array('id' => $this->ID)
			));
		endif;
	}


	/**
	* 	get3DsecureData()
	* 	filtra i dati utente e ritorna solamente quelli utili per il 3dSecure
	*	@param void
	*	@return array
	*/
	public function get3DsecureData(){
		$userData = array_filter($this->arData,function($k){
			switch($k):
				case 'nome':
				case 'cognome':
				case 'ragione_sociale':
				case 'cap':
				case 'citta':
				case 'indirizzo':
				case 'sigla_provincia':
				case 'provincia':
				case 'sigla_nazione':
				case 'email':
				case 'telefono':
				case 'shipping_addresses':
					return true;
				default:
					return false;
			endswitch;
		},ARRAY_FILTER_USE_KEY);

		if(isset($userData['shipping_addresses']) && count($userData['shipping_addresses']) > 0):
			foreach($userData['shipping_addresses'] as & $A):
				$A = array_filter($A, function($k){
					switch($k):
						case 'id':		// me serve per verificare l'indirizzo di spedizione attivo
						case 'nome':
						case 'cognome':
						case 'ragione_sociale':
						case 'cap':
						case 'citta':
						case 'indirizzo':
						case 'sigla_provincia':
						case 'provincia':
						case 'sigla_nazione':
						case 'email':
						case 'telefono':
							return true;
						default:
							return false;
					endswitch;
				},ARRAY_FILTER_USE_KEY);
			endforeach;
		endif;

		return $userData;
	}
}
