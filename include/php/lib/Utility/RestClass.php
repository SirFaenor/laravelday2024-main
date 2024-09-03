<?php
namespace Utility;
class RestClass {

	private $response = array(
				"success" => FALSE 						// indica il SUCCESSO della chiamata
				,"content" => NULL 					// contiene il contenuto della RISPOSTA
			);
	private $strData;

	
	/*
	*
	*  	call() 
	*  	-----------
	*  	Faccio una chiamata curl all'indirizzo passato come parametro
	*	@params
	*		$url: cui inviare la chiamata
	*		$arData: dati da concatenare all'url in forma di querystring
	*		$extraHeaders: headers aggiuntivi
	*		$extraOpt: opzioni aggiuntive per cUrl
	*	@return
	*		risposta della chiamata cUrl
	*
	*/
	public function call($url, $arData = array(), $extraHeaders = array(), $extraOpt = array()){

		if(is_array($arData) && count($arData) > 0):
			foreach ($arData as $key => $value): 
				$this->strData .= $key.'='.urlencode($value).'&';
			endforeach;
			$this->strData = rtrim($this->strData,'&');
		endif;
		if(strlen($this->strData)):
			$url .= '?'.$this->strData;
		endif;

		// Preapro headers aggiungendo header di default se non passati
		$curl_header = array();
		$extraHeaders["User-Agent"] 	= !empty($extraHeaders["User-Agent"]) 	? $extraHeaders["User-Agent"] 	: $_SERVER["HTTP_USER_AGENT"];
		$extraHeaders["Content-Type"] 	= !empty($extraHeaders["Content-Type"]) ? $extraHeaders["Content-Type"] : "application/x-www-form-urlencoded";
		foreach ($extraHeaders as $header_name => $value) :
			array_push($curl_header, $header_name.':'.$value);
		endforeach;

		$curl_opt = array();
		$extraOpt[CURLOPT_URL] 				= $url;
		$extraOpt[CURLOPT_RETURNTRANSFER] 	= !empty($extraOpt[CURLOPT_RETURNTRANSFER]) ? $extraOpt[CURLOPT_RETURNTRANSFER] : true;

		if(count($curl_header) > 0):
			$extraOpt[CURLOPT_HTTPHEADER] = $curl_header;
		endif;

		foreach ($extraOpt as $opt_name => $value) :
			$curl_opt[$opt_name] = $value;
		endforeach;

		// Inizializzo e opzioni
		$ch = curl_init();
		if (!empty($curl_opt)):
			curl_setopt_array($ch,$curl_opt);
		endif;

	   	// Eseguo
	    $exec_response = curl_exec($ch);

	   	if ($exec_response === FALSE) {
	    	$response["content"] = curl_error($ch); 
	    } else {
	    	$response["success"] = TRUE; 
	    	$response["content"] = $exec_response; 
	    }


	    // close curl resource to free up system resources
	    curl_close($ch);
		return $response;
	}


}


