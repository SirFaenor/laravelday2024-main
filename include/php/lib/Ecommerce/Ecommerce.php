<?php
/*
 *	Informazioni e metodi legati alla gestione dell'ecommerce
 *	Contiene un oggetto Cart
*/

namespace Ecommerce;
class Ecommerce {

	protected $arSettings = array();		// impostazioni varie dell'ecommerce
	protected $arProducts = array();		// prodotti singoli
	protected $arUserCats = array();		// categorie utente

	protected $Da;							// oggetto DataAccess
	protected $Lang;						// oggetto LangManager
	protected $User;						// oggetto User

	public $Name = 'EcommerceByAtrio';		// nome dell'ecommerce (da usare eventualmente nei campi title)


	public function __construct($Da, $Lang, \Site\User $User){
		$this->Da 	= $Da;
		$this->Lang = $Lang;
		$this->User = $User;

		$this->loadSettings();
	}



	#########################################################
	#
	#	IMPOSTAZIONI DELL'ECOMMERCE
	#
	#########################################################

	/**
	 * Carico le impostazioni dell'ecommerce
	 * @param void
	 * @return void
	 */
	protected function loadSettings(){

		# ottengo le impostazioni per l'ecommerce
		$arSettings = $this->Da->getRecords(array(
			'table'		=> 'settings'
			,'cond'		=> 'WHERE public = "Y" AND (NOW() BETWEEN date_start AND date_end)'
		));
		if($arSettings):
			foreach($arSettings as $S):
				$this->arSettings[$S['code']] = $S['valore'];
			endforeach;
		endif;
		
	}

	/**
	* 	getAllSettings()
	* 	ritorna tutte le impostazioni dell'ecommerce
	*	@param void
	*	@return array
	*/
	public function getAllSettings(){
		return $this->arSettings;
	}

	/**
	* 	getSetting()
	* 	recupera un valore dell'array arSettings usando come chiave il parametro passato
	*	@param string $key : chiave del valore da recuperare
	*	@return string il valore corrispondente alla chiave passata come parametro | false
	*/
	public function getSetting($key){
		if(!isset($this->arSettings[$key])):
			trigger_error('['.__METHOD__.'] Chiave mancante <code>'.$key.'</code> in <code>$arSettings</code>: <pre>'.print_r($this->arSettings,1).'</pre>',E_USER_WARNING);
		else:
			return $this->arSettings[$key];
		endif;
	}


	/**
	* 	setSetting()
	* 	aggiunge un valore a settings
	*	@param string $key : chiave da assegnare al valore da definire
	*	@param string val : valore 
	*	@return void
	*/
	public function setSetting($key,$val){
		$this->arSettings[$key] = $val;
	}



	#########################################################
	#
	#	PRODOTTI
	#
	#########################################################


	/**
	* 	setProducts()
	* 	popolo l'array con i prodotti
	*	funzione che va ridefinita per ogni ecommerce
	*	@param void
	*	@return void
	*/
	protected function setProducts(){
		trigger_error('['.__METHOD__.'] Metodo definito ma non è stata ancora implementata la logica',E_USER_ERROR);
	}


	/**
	* 	countProducts()
	* 	ritorna il numero di prodotti disponibili nell'ecommerce
	*	@param void
	*	@return int numero prodotti nell'ecommerce
	*/
	public function countProducts(){
		return count($this->arProducts);
	}


	/**
	* 	getProducts()
	* 	ritorna i prodotti disponibili nell'ecommerce
	*	@param void
	*	@return array tutti i prodotti presenti nell'ecommerce
	*/
	public function getProducts(){
		return $this->arProducts;
	}



	#########################################################
	#
	#	UTENTI
	#
	#########################################################


	/**
	* 	setUserCats()
	* 	popolo l'array con le categorie utente
	*	funzione che va ridefinita per ogni ecommerce
	*	@param void
	*	@return void
	*/
	protected function setUserCats(){
		trigger_error('['.__METHOD__.'] Metodo definito ma non è stata ancora implementata la logica',E_USER_ERROR);
	}


	/**
	* 	getUserCats()
	* 	ritorna le categorie utente disponibili
	*	@param void
	*	@return array tutte le categorie utente
	*/
	public function getUserCats(){
		return $this->arUserCats;
	}
	
}
?>