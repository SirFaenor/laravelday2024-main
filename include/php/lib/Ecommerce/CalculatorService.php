<?php
namespace Ecommerce;

use Exception;

/**
 * Classe di utilità per svolgere calcoli ricorrenti
 * 
 * @author Emanuele Fornasier
 */
class CalculatorService
{

    /**
     * Valuta corrente
     */
    protected $currency;

    /**
     * @const float DEFAULT_VAT valore predefinito dell'iva sovrascrivibile da esterno
     */
    const DEFAULT_VAT = 22.00;
    
    /**
     * Valute disponibili
     */
    protected $availableCurrencies = [
        "EUR" => "€"
       ,"USD" => "$"
    ];

    
    /**
     * @param string $currency valuta
     * @param string $market identificatore del mercato
     */
    public function __construct($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Scorpora il valore assoluto dell'iva (da percentuale) su un prezzo 
     * e il prezzo stesso senza iva
     * 
     * @param float $fullPrice prezzo pieno (dato comprensivo di iva) 
     * @param float $vat valore in percentuale dell'iva
     * @return array $price[0 => prezzo senza iva, 1 => valore assoluto dell'iva] 
     */
    public function splitPriceByVat($fullPrice, $vat = null) {

        $vat = $vat !== null ? $vat : self::DEFAULT_VAT;

        $price = [];
        $price[0] = round($fullPrice*10000/(100+$vat))/100;
        $price[1] = $fullPrice - $price[0];

        return $price;

    }


    /**
     * Calcola valore dell'iva su un valore di partenza
     * @param float $value valore di partenza
     * @param float $vat valore in percentuale dell'iva
     */
    public function calculateVat($value, $vat = null) {
        
        // se non viene  passato valore uso default
        $vat = $vat !== null ? $vat : self::DEFAULT_VAT;
        
        return round($value/100*$vat, 2);
   
    }

    
    /**
     * Formattazione prezzi, comprensivi di indicatore valuta
     * @param float $price numero da formattare in notazione float
     * @param null or int 1/2 posizione del simbolo valuta ( 1 a sx, 2 a dx, null lo sopprime )
     */
    public function formatPrice($price, $symbolPosition = 1, $ifZero = null) {

        if ($ifZero !== null && $price == 0) {
            return $ifZero;
        }

        // formattazione
        switch ($this->currency) {
            case 'EUR':

                $price = number_format($price, 2, $dec_point = ',', $thousands_sep = '');
                
                break;

            case 'USD':
                
                $price = number_format($price, 2, $dec_point = '.', $thousands_sep = ',');

                break;
           
        }

        // aggiunta simbolo
        if ($symbolPosition === 1) {
            $price = $this->getCurrencySymbol().' '.$price;
        }
        
        if ($symbolPosition === 2) {
            $price .= ' '.$this->getCurrencySymbol();
        }


        return $price;

    }


    /**
     * Ritorna simbolo della valuta
     */
    public function getCurrencySymbol() {
        return $this->availableCurrencies[$this->currency];
    }

}