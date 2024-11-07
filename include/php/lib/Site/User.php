<?php
/**
*	class User
*	Gestisce login e recupero dati utente
*/
namespace Site;

use Exception;

class User {

	protected $ID;					// id dell'utente
	
	protected $FixNumber;
	protected $FixString;
	protected $Da;
	protected $Lang;

	protected $arData;				// dati dell'utente
	protected $permits = array(); 	// permessi -> la chiave dell'array è l'oggetto del permesso, true se ha il permesso, false altrimenti

	public $dataTable 		= 'cliente';	// tabella dove recuperare i dati utente
	public $sqlPassField 	= NULL;			// nome del campo password
	public $hashOnLogin 	= 'md5';		// tipo di impronta
	public $saveData 		= false;		// devo salvare i dati in arrivo?

	protected $arUserDataSessionName = array();	// nomi delle sessioni dei forms del sito che gestiscono i dati utente -> vanno impostate manualmente con il metodo addUserDataSessionName; le devo cancellare quando l'utente fa logout

	private $Cart;			// oggetto carrello

	# estendioni per altre classi
	public $emptyCartData = false;

	/**
	 * 	__construct()
     * @param object $Da istanza di dataaccess
     * @param object $Lang istanza di langmanager
     * @param object $FixNumber istanza di FixNumber
     * @param object $FixString istanza di FixString
     * @return void
	 */
	public function __construct($Da, $Lang, \Utility\FixNumber $FixNumber, \Utility\FixString $FixString){

		$this->Da 			= $Da;
		$this->Lang 		= $Lang;
		$this->FixNumber 	= $FixNumber;
		$this->FixString 	= $FixString;
		if(isset($_SESSION['User']) && isset($_SESSION['User']['id']) ):
			$this->ID 					= $_SESSION['User']['id'];
			$this->arUserDataSessionName= isset($_SESSION['User']['arUserDataSessionName']) ? $_SESSION['User']['arUserDataSessionName'] : array();
			if(!$this->fetchData()):
				$this->ID = NULL;
			endif;
		endif;

	}


	/**
	 * 	__destruct()
	 * 	Salvo l'utente se così impostato
	 *	Registro o cancello la sessione utente se è loggato o meno
	 */
	public function __destruct(){

		// salvo l'utente
		if($this->saveData === true):
			$this->registerData();
		endif;

		if($this->isLoggedIn() == 0):
			$this->unsetUserData();
		else:
			$_SESSION['User']['id'] = $this->ID;
			$_SESSION['User']['arUserDataSessionName'] = $this->arUserDataSessionName;
		endif;

	}



	#########################################################
	#
	#	SEZIONE AUTENTICAZIONE
	#
	#########################################################


	/**
	* 	Login()
	* 	funzione chiamata al momento del login dell'utente al sito
	*	@param string user : nome utente
	*	@param string pass : password
	*	@return bool true o false a seconda che il login sia andato a buon fine o meno
	*/
	public function Login($user = null,$pass = null){

		$this->unsetUserData();															// resetto i dati in sessione

		// definisco il campo password (eventualmente lo posso riscrivere)
		$this->sqlPassField = $this->sqlPassField  === NULL 							// se non ho riscritto il nome della colonna password
							? (
								$this->hashOnLogin !== false 							// uso la regola [algoritmo di hash]_password
								? $this->hashOnLogin.'_password' : 'password'
							)
							: $this->sqlPassField;										// altrimenti uso il nome del campo che ho ridefinito
							
		$this->ID = $this->checkLogin($user,$pass);										// verifico effettivamente che l'utente esista e ne recupero l'id


        if($this->ID):																	// se ho l'id recupero e normalizzo i dati su array utente
			if(!$this->fetchData()):
				$this->ID = NULL;
			else:
				$this->setPermits();
			endif;
		endif;

		return $this->isLoggedIn();

	}


	/**
	* 	LoginByID()
	* 	funzione chiamata al momento del login dell'utente al sito
	*	@param int ID : utente
	*	@return bool true o false a seconda che il login sia andato a buon fine o meno
	*/
	public function LoginByID($ID = null){

		$this->unsetUserData();															// resetto i dati in sessione

		$r =  $this->Da->getSingleRecord(array(
			'table'		=> $this->dataTable
			,'columns'	=> array('id')
			,'cond'		=> 'WHERE public = "Y" AND (NOW() BETWEEN date_start AND date_end) AND deleted = "N" AND id = ?'
			,'params'	=> array($ID)
		));
		$this->ID = $r ? $r['id'] : $r;

		if($this->ID):																	// se ho l'id recupero e normalizzo i dati su array utente
			if(!$this->fetchData()):
				$this->ID = NULL;
			else:
				$this->setPermits();
			endif;
		endif;

		return $this->isLoggedIn();

	}


	/**
	* 	Logout()
	* 	funzione di logout
	*	@param void
	*	@return void
	*/
	public function Logout(){
		$this->unsetUserData();
		$this->emptyCartData = true;
	}


	/**
	* 	checkLogin()
	* 	verifico effettivamente che l'utente con i parametri passati esista
	*	NB: tengo una funzione separata in modo che sia estensibile se c'è bisogno di query specifiche
	*	@param string user : nome utente
	*	@param string pass : password
	*	@return int id dell'utente | false
	*/
	protected function checkLogin($user,$pass){

		$hash_pass 	= $this->hashOnLogin !== false ? strtoupper(hash($this->hashOnLogin,$pass)) : $pass;
		$sql_pass 	= $this->hashOnLogin !== false ? ' UPPER('.$this->sqlPassField.')' : $this->sqlPassField;

		$r =  $this->Da->getSingleRecord(array(
			'table'		=> $this->dataTable
			,'columns'	=> array('id')
			,'cond'		=> 'WHERE public = "Y" AND (NOW() BETWEEN date_start AND date_end) AND deleted = "N" AND email = ? AND '.$sql_pass.' = ?'
			,'params'	=> array($user,$hash_pass)
		));

		return $r ? $r['id'] : $r;
	}


	/**
	* 	unsetUserData()
	* 	cancello i dati utente
	*	@param void
	*	@return void
	*/
	protected function unsetUserData(){
		unset($_SESSION['User']);
		if(is_array($this->arUserDataSessionName) && count($this->arUserDataSessionName) > 0):
			foreach($this->arUserDataSessionName as $SK):
				unset($_SESSION[$SK]);
			endforeach;
		endif;
		$this->ID = $this->arData = NULL;
		$this->setDefaults();
	}


	/**
	* 	isLoggedIn()
	* 	verifico se l'utente è loggato
	*	@param void
	*	@return int 1 | 0
	*/
	public function isLoggedIn(){
		return $this->ID ? 1 : 0;
	}


	/**
	* 	addUserDataSessionName()
	* 	aggiungo un nome di sessione all'array con i dati utente
	*	@param string $name
	*	@return bool
	*/
	public function addUserDataSessionName($name){
		if(!($name && is_string($name))):
			return false;
		endif;
		if(!is_array($this->arUserDataSessionName)):
			$this->arUserDataSessionName = array();
		endif;
		return $this->arUserDataSessionName[] = $this->FixString->fix($name);
	}



	#########################################################
	#
	#	SEZIONE DATI UTENTE
	#
	#########################################################


	/**
	* 	setDefaults()
	* 	Imposto alcuni dati di defaults per gli utenti
	*	@param void
	*	@return void
	*/
	protected function setDefaults(){

		$this->arData['id_cat'] 	= 1;			# id della categoria
		$this->arData['title_cat'] 	= 'Privato';	# nome categoria
		$this->arData['code_cat'] 	= 'PRIVATO';	# codice categoria
		$this->arData['min_cart'] 	= 0;			# carrello minimo per questa categoria
		$this->arData['min_cart_um']= 1;			# unità di misura per il carrello minimo
		$this->arData['id_nazione'] = 1;			# italia
		$this->arData['nazione'] 	= 'Italia';
		$this->arData['codice_nazione'] = 'IT';
		$this->arData['newsletter'] = 'N';	

	}


	/**
	* 	getData()
	* 	ritorno i dati dell'utente
	*	NB: tengo una funzione separata in modo che sia estensibile se c'è bisogno di query specifiche
	*	@param void
	*	@return array dati utente
	*/
	public function getData(){
		return $this->arData;
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
			,'cond'		=> 'WHERE public = "Y" AND (NOW() BETWEEN date_start AND date_end) AND deleted = "N" AND id = ?'
			,'params'	=> array($this->ID)
		));

		return $this->normalizeData($rawdata);

	}


	/**
	* 	normalizeData()
	* 	recupero di dati dell'utente e li normalizzo secondo le esigenze
	*	NB: tengo una funzione separata in modo che sia estensibile se c'è bisogno di query specifiche
	*	@param void
	*	@return void
	*/
	protected function normalizeData($rawdata){

		if($rawdata):
			$this->arData = array();
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
	* 	__call()
	* 	overloading per ottenere o impostare elementi dell'array arData
	*	get+valore da ottenere per ottenere un valore di arData
	*	set+valore da impostare per inserire un valore in arData
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
			case 'get': return isset($this->arData[$search]) ? $this->arData[$search] : ($search == 'ID' ? $this->ID : false); break;
			case 'set': return $this->arData[$search] = $val; break;
			default: 	throw new Exception("Metodo inesistente: ".$key);
		endswitch;
	}


	/**
	* 	registerData()
	* 	se l'utente è loggato allora aggiorno i dati
	*	altrimenti creo nuovo utente
	*	@param void
	*	@return void
	*/
	public function registerData(){

		$dataToSave = $this->prepareData();

		if($this->isLoggedIn() == 1):
			unset($dataToSave['id']);				// rimuovo l'id per sicurezza
			$this->Da->updateRecords(array(
				'table'		=> $this->dataTable
				,'data'		=> $dataToSave
				,'cond'		=> 'WHERE id = ?'
				,'params'	=> array($this->ID)
			));
		else:
			$this->Da->createRecord(array(
				'table'		=> $this->dataTable
				,'data'		=> $dataToSave
			));
			$this->ID = $this->Da->insertId();
		endif;

		return true;
	}


	/**
	* 	userExists()
	* 	prepara i dati per essere salvati nella tabella utente
	*	@param array $arData -> array con i dati da confrontare: le chiavi dell'array sono le colonne su cui confrontare il valore
	*	@return int 
	*/
	public function userExists($arData = NULL){

		$arSqlWhere = array();
		if($arData === NULL):
			trigger_error('['.__METHOD__.'] Non vengono passati dati per verificare se l\'utente esiste',E_USER_ERROR);
		else:
			foreach($arData as $col => $val):
				$arSqlWhere[$val] = $col.' = ?';
			endforeach;
		endif;


		if(count($arSqlWhere) <= 0):
			trigger_error('['.__METHOD__.'] Nessuna condizione realizzata sulla base delle informazioni passate <pre>'.print_r($arData,1).'</pre>',E_USER_ERROR);
		else:

			return $this->Da->countRecords(array(
						'table'		=> $this->dataTable
						,'cond'		=> 'WHERE deleted = "N" AND '.(count($arSqlWhere) > 1 ? '('.implode(' OR ',array_values($arSqlWhere)).')' : implode(' OR ',array_values($arSqlWhere)))	# non cancellati
						,'params'	=> array_keys($arSqlWhere)
					)) > 0
					? 1
					: 0;
		endif;		
	}


	/**
	* 	prepareData()
	* 	prepara i dati per essere salvati nella tabella utente
	*	@param void
	*	@return array $dataPrepared
	*/
	protected function prepareData(){

        // sistemo alcuni dati per modalità strict mysql
        if (array_key_exists('date_deletion',$this->arData) && !strlen($this->arData['date_deletion'])) {unset($this->arData['date_deletion']);}
        if (array_key_exists('data_nascita',$this->arData) && !strlen($this->arData['data_nascita'])) {unset($this->arData['data_nascita']);}
		if (array_key_exists("id_provincia", $this->arData) && !strlen($this->arData["id_provincia"])) {unset($this->arData["id_provincia"]);}
		if (array_key_exists("id_nazione", $this->arData) && !strlen($this->arData["id_nazione"])) {unset($this->arData["id_nazione"]);}

		return $this->arData;
	}


	/**
	* 	deleteUser()
	* 	cancello l'utente (imposto la flag deleted) se loggato
	*	@param void
	*	@return void
	*/
	protected function deleteUser(){
		if(!$this->ID):
			trigger_error('['.__METHOD__.'] ID utente da cancellare non presente <pre>'.print_r($this->arData,1).'</pre>',E_USER_WARNING);
		else:
			$this->Da->updateRecords(array(
				'table'		=> $this->dataTable
				,'data'		=> array(
					'deleted'	=> 'Y'
				)
				,'cond'		=> 'WHERE id = :id'
				,'params'	=> array('id' => $this->ID)
			));
		endif;
	}



	#########################################################
	#
	#	PERMESSI
	#
	#########################################################


	/**
	* 	setPermits()
	*	da definire nell'estensione della classe
	* 	imposto i permessi e restrizioni per l'utente se ho una categoria
	*	@param void
	*	@return void
	*/
	public function setPermits(){
		throw new Exception('['.__METHOD__.'] La gestione permessi per l\'utente va gestita nella classe custom');
	}


	/**
	* 	allow()
	* 	imposto una restrizione per l'utente
	*	@param string $key: l'oggetto del permesso (ad esempio l'uso del pacchetto convenienza)
	*	@param bool $val: true o false; sono permissivo: se il valore è diverso da false l'utente non ha la restrizione
	*	@param bool $restrictive: sono restrittivo? se true nel caso in cui val != true l'utente non ha il permesso; se false nel caso in cui val != false allora l'utente ha il permesso
	*	@return	void
	*/
	public function allow($key,$val,$restrictive = false){
		if($restrictive):
			$val = $val != true ? false : true;
		else:
			$val = $val != false ? true : false;
		endif;
		$this->permits[$key] = $val;
	}


	/**
	* 	allowed()
	* 	verifico se l'utente ha il permesso per un determinato ambito
	*	@param string $key: l'oggetto del permesso (ad esempio l'uso del pacchetto convenienza)
	*	@param bool $restrictive: sono restrittivo? se true nel caso in cui la chiave non esista l'utente non ha il permesso; se false nel caso in cui la chiave non esista l'utente ha il permesso
	*	@return true o false; sono permissivo: se la chiave non è presente l'utente ha il permesso
	*/
	public function allowed($key,$restrictive = true){
		if($restrictive):
			return isset($this->permits[$key]) ? $this->permits[$key] : false;
		else:
			return isset($this->permits[$key]) ? $this->permits[$key] : true;
		endif;
	}




	#########################################################
	#
	#	SPEDIZIONI
	#
	#########################################################


	/**
	* 	saveExpeditionAddress()
	* 	salvo un indirizzo di spedizione collegato all'utente
	*	@param array $arData: i dati da salvare
	*	@return bool
	*/
	public function saveExpeditionAddress($arData){

		$id_nazione 	= isset($arData['id_nazione']) ? $arData['id_nazione'] : '';
		$id_provincia 	= isset($arData['id_provincia']) ? $arData['id_provincia'] : '';
		$cap 			= isset($arData['cap']) ? $arData['cap'] : '';
		$citta 			= isset($arData['citta']) ? strtoupper($arData['citta']) : '';

		$hash 			= hash('sha512',$id_nazione.$id_provincia.$cap.$citta);

		if(!$this->Da->countRecords(array(
			'table'		=> 'cliente_spedizioni'
			,'cond'		=> 'WHERE hash = ? AND cliente_id = ?'
			,'params'	=> array($hash,$this->ID)
		))):
			$arData['cliente_id'] 	= $this->ID;
			$arData['hash'] 		= $hash;
			     
            // manu, 2017/11/05
            // elimino chiave "id" in seguito a riscontro errore (per velocità, non so da dove provenga)
            if (isset($arData["id"])) :
                unset($arData["id"]);
            endif;

            return $this->Da->createRecord(array(
						'table'		=> 'cliente_spedizioni'
						,'data'		=> $arData
					));
        endif;

		return true;
	}


}

