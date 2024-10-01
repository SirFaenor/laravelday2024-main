<?php

namespace Utility;
class FixNumber {
							
	//////////////////////////////////////////////////////////////
	// == Fix numero ==
	public function fix($numVal, $type = 1){
		
		$numVal = trim($numVal);
		$numVal = str_replace(',', '.', $numVal); // Sostituisco la virgola con punto
	
		$numVal = (is_numeric($numVal)) ? $numVal : 0; // Controllo se il valore è numerico
		
		if($numVal != 0){return $numVal;}
		if($numVal == 0 && $type == 1){return 0;}
		if($numVal == 0 && $type == 0){return '';}
	}


	/**
	* telnumberToCallableTelnumber()
	* transformo un numero di telefono in un numero di telefono utilizzabile con il link tel:
	* @param string tel_number : il numero da trasformare
	* @return string
	*/
	public function telnumberToCallableTelnumber($tel_number){
		return preg_replace('/[^+0-9]+/','',$tel_number);
	}


	/**
	* telnumberToReadableTelnumber()
	* transformo un numero di telefono in un numero di telefono maggiormente leggibile
	* @param string tel_number : il numero da trasformare
	* @param int interval : blocchi di numeri
	* @return string
	*/
	public function telnumberToReadableTelnumber($tel_number,$interval = 2){
		return preg_replace('/([0-9]{'.$interval.'})/','$1 ',$tel_number);
	}


	/**
	 * getValueFromPerc()
	 * dato un numero e una percentuale, ottengo il valore corrispondente alla percentuale del numero
	 * x = v*p%
	 * @param float il numero di cui voglio calcolare il valore percentuale
	 * @param float percentuale (p)
	 * @param int cifre decimali da ritornare
	 * @return float valore della percentuale (x)
	 */
	public function getValueFromPerc($value,$perc,$decimal = 2){
		if($perc > 100):
			trigger_error('['.__METHOD__.'] La percentuale deve essere inferiore a 100',E_USER_NOTICE);
			return $value;
		endif;
		$decimal_divider = pow(10,$decimal);
		return round($value*$perc*$decimal_divider/100)/$decimal_divider;
	}


	/**
	 * getPercAmount()
	 * dato un numero e una percentuale, ottengo la porzione percentuale come valore
	 * ad esempio se ho un prezzo ivato e passo la percentuale dell'iva, ottengo il prezzo senza IVA
	 * x + xp = valore => x = valore/(1+p);
	 * @param float il numero da cui voglio estrarre la porzione percentuale (valore)
	 * @param float percentuale (p)
	 * @param int cifre decimali da ritornare
	 * @return float valore della percentuale (x)
	 */
	public function getPercAmount($value,$perc,$decimal = 2){
		if($perc > 100):
			trigger_error('['.__METHOD__.'] La percentuale deve essere inferiore a 100',E_USER_NOTICE);
			return $value;
		endif;
		$decimal_divider = pow(10,$decimal);
		return round($value*100*$decimal_divider/(100+$perc))/$decimal_divider;
	}


	/**
	 * subtractPercAmount()
	 * dato un valore lo sottraggo della percentuale passata
	 * x = valore - p*valore => x = valore*(1-p);
	 * @param float il numero a cui voglio sottrarre la percentuale
	 * @param float percentuale (p)
	 * @param int cifre decimali da ritornare
	 * @return float numero senza la percentuale (x)
	 */
	public function subtractPercAmount($value,$perc,$decimal = 2){
		if($perc > 100):
			trigger_error('['.__METHOD__.'] La percentuale deve essere inferiore a 100');
		endif;
		$decimal_divider = pow(10,$decimal);
		return round($value*$decimal_divider*(100-$perc)/100)/$decimal_divider;
	}


	/**
	 * addPercAmount()
	 * dato un valore gli aggiungo percentuale passata
	 * x = valore + valore*p = valore*(1+p);
	 * @param float il numero a cui voglio sottrarre la percentuale
	 * @param float percentuale (p)
	 * @param int cifre decimali da ritornare
	 * @return float numero iniziale sommato dell'importo calcolato sulla base della percentuale (x)
	 */
	public function addPercAmount($value,$perc,$decimal = 2){
		$decimal_divider = pow(10,$decimal);
		return round(($value*$decimal_divider)+($value*$decimal_divider*$perc/100))/$decimal_divider;
	}


	/**
	 * calcPerc()
	 * dati 2 numeri calcolo quanto il secondo è la percentuale del primo
	 * x = 100 - (v2*100/v1);
	 * @param float il numero maggiore
	 * @param float il numero inferiore
	 * @param int cifre decimali da ritornare
	 * @return float valore percentuale (x)
	 */
	public function calcPerc($v1,$v2,$decimal = 2){
		// se i 2 numeri sono uguali la percentuale è 100
		if($v1 == $v2):
			return 100;
		endif;

		// se il secondo numero è maggiore del primo la percentuale è 100
		if($v1 < $v2):
			trigger_error('['.__METHOD__.'] Il primo valore deve essere maggiore del secondo',E_USER_NOTICE);
			return 100;
		endif;
		
		// se il secondo numero è 0, la percentuale è 0
		if($v2 == 0):
			trigger_error('['.__METHOD__.'] Il secondo valore è 0',E_USER_NOTICE);
			return 0;
		endif;
		
		$decimal_divider = pow(10,$decimal);
		return round((100*$decimal_divider) - ($v2*$decimal_divider/$v1))/$decimal_divider;
	}


	/**
	 * addVatToPrice()
	 * calcolo gli importi di prezzo con iva e iva dato un prezzo e un valore iva
	 * @param float prezzo iniziale (senza iva)
	 * @param float valore iva
	 * @return array importo con iva, importo iva, importo senza iva (valore iniziale)
	 */
	public function addVatToPrice($price = null, $vat_perc = 22){
		if($vat_perc > 100):
			trigger_error('['.__METHOD__.'] La percentuale iva non può essere superiore a 100');
		endif;

		$no_vat_price 	= $this->fix($price);
		$full_price 	= $this->addPercAmount($no_vat_price,$vat_perc);
		$vat_price 		= $full_price - $no_vat_price;

		return array(
			'prezzo'		=> $full_price
			,'prezzo_iva'	=> $vat_price
			,'prezzo_no_iva'=> $no_vat_price
		);
	}


	/**
	 * calcVatFromPrice()
	 * calcolo gli importi di prezzo senza iva e iva dato un prezzo e un valore iva
	 * @param float prezzo iniziale
	 * @param float valore iva
	 * @return array valore iniziale, importo iva, importo senza iva
	 */
	public function calcVatFromPrice($price = null, $vat_perc = 22){

		$full_price 	= $this->fix($price);
		$no_vat_price 	= $this->getPercAmount($full_price,$vat_perc);
		$vat_price 	 	= $full_price - $no_vat_price;

		return array(
			'prezzo'		=> $full_price
			,'prezzo_iva'	=> $vat_price
			,'prezzo_no_iva'=> $no_vat_price
		);
	}

    public function isNumber($numVal, $type = 1) {

        $numVal = (is_numeric($numVal)) ? $numVal : 0;
        
        if($numVal != 0){return $numVal;}
        if($numVal == 0 && $type == 1){return 0;}
        if($numVal == 0 && $type == 0){return '';}

    }
}

