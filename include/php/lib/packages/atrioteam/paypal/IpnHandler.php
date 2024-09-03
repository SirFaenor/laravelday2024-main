<?php
/*
=================================================================================================================================================
*
* class Ipn_Handler 
* -----------------------------------------------------------------------------------------------
* 
* Estende Ipn_Handler_Abs per avere funzionalità aggiuntive.
* Processa notifiche Ipn di Paypal
* 
* 
=================================================================================================================================================
*/
namespace AtrioTeam\Paypal;

class IpnHandler extends AbstractIpnHandler {
	
	
	private $arData = array();
	
	
	/*
	 *
	 * __construct() 
	 * --------------------------
	 * Accetta dati post della notifica.
	 *
	 */
	public function __construct(array $data) {
		$this->arData = $data;
	}

	
	/*
	 *
	 * processData() 
	 * --------------------------
	 * Processa i dati memorizzati nella classe attraverso il
	 * metodo genitore.
	 * Se non va a buon fine, restituisce la risposta del metodo genitore
	 *
	 */
	public function processData() {
		
		$data_processed = parent::process($this->arData);
		
		if (!is_array($data_processed)) return $data_processed;	
		
		$this->arData = $data_processed;
		
		return true;
	}
	
	
	/*
	 *
	 * getData() 
	 * --------------------------
	 * Restituisce un valore della chiamata, se esiste.
	 *
	 */
	public function getData($index = '') {
		if (empty($index)) {
			return $this->arData;
		} else {
			return !empty($this->arData[$index]) ? $this->arData[$index] : '';
		}
	}
	
	/*
	 *
	 * serializeData() 
	 * --------------------------
	 * Trasforma i dati in memoria in stringa separata da ;
	 * per essere inserita completa in db
	 *
	 */
	public function serializeData() {
		$str = '';
		foreach($this->arData as $k => $v) {
			$str .= $k.'='.$v.';';	
		}
		return $str;
	}
}
