<?php

namespace Utility;
class Currency {

	/**
	 * Valori di base per formattare la valuta; eventualmente possono essere sovrascritti puntualmente
	 */
	public $currencyFormat = array(
								'decimalSeparator' => ','
								,'thousandsSeparator' => ''
								,'currency' => ''
								,'currencyPosition' => 0
                            );
    
    
    public function __construct(array $settings = array())
    {
        if(count($settings)):
            $this->currencyFormat = array_merge($this->currencyFormat,$settings);
        endif;
    }    

		
	/**
	 * numberToCurrency()
	 * Trasformo un numero in formato valuta
	 * @param float il numero da trasformare
	 * @param string separatore decimale
	 * @param string separatore migliaia
	 * @param string valuta da inserire (inserire anche gli eventuali spazi prima o dopo)
	 * @param int 0|1 posizione del valore $currency: 0 prima del numero, 1 dopo il numero
	 * @return string
	 */
	public function print(float $number,string $decimalSeparator = '',string $thousandsSeparator = '',string $currency = '',int $currencyPosition = 0):string {
        if(!is_numeric($number)){
            throw new \InvalidArgumentException('['.__METHOD__.'] Il numero '.$number.' da convertire in valuta deve essere di tipo numerico');
        }

		$decimalSeparator = $decimalSeparator ?: $this->currencyFormat['decimalSeparator'];
		$thousandsSeparator = $thousandsSeparator ?: $this->currencyFormat['thousandsSeparator'];
		$currency = $currency ?: $this->currencyFormat['currency'];
		$currencyPosition = $currencyPosition ?: $this->currencyFormat['currencyPosition'];

		$to_return = number_format($number,2,$decimalSeparator,$thousandsSeparator);
		if(strlen($currency)):
			$to_return = $currencyPosition == 0 ? $currency.$to_return : $to_return.$currency;
		endif;
		return (string)$to_return;
	}

}
