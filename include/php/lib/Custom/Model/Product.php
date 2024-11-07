<?php
/**
 * @author Emanuele Fornasier
 */
namespace Custom\Model;

use Custom\Ecommerce as Ecommerce;

class Product extends StdModel implements Ecommerce\Cartable
{


    /**
     * Estensione proprietà di base
     */
    protected $id;

    protected $catId;
    protected $catPosition;
    protected $catTitle;
    protected $catPageUrl;

    protected $clientCatId;

    protected $discountPrice;
    protected $discountPerc;
    protected $discountCodeApplyed;

    protected $published;

    protected $code;
    protected $lang;

    protected $Price;
    protected $PriceNoIVA;
    protected $PriceIVA;
    protected $DiscountedPrice;
    protected $DiscountedPriceNoIVA;
    protected $DiscountedPriceIVA;
    protected $FinalPrice;
    protected $FinalPriceNoIVA;
    protected $FinalPriceIVA;
    protected $PercDiscount;
    protected $HasDiscount;

    protected $img1;
    protected $img1_S;
    protected $img1_M;
    protected $img1_L;

    protected $title;
    protected $subtitle;
    protected $description;

    protected $metaTitle;
    protected $metaDescription;
    protected $metaKeywords;
    protected $pageUrl;

    protected $fullPageUrl;
    protected $Currency;

    protected $vegan;
    protected $glutenfree;
    protected $alcohol;
    protected $expiringOrder;
    protected $sellable;

    protected $ivaId;
    protected $iva;

  
    /**
     * Controlla se il prodotto è vendibile.
     * 
     * @param void
     * @return boolean
     */
    public function isSellable():bool {
        return $this->sellable === true;
    }

    
    /**
     * Controlla se è disponibile sconto
     */
    public function discountAvailable():bool {
        return (float)$this->discountPrice < (float)$this->basePrice;
    }


    /**
     * Il prodotto è disponibile?
     * @param $requestedQuantity quantità richiesta
     * @return true se tutto ok
     */
    public function isAvailable($requestedQuantity = 1):bool {
       
        // per ora non ho controllo disponibilità
        return $this->isSellable();

    }


    /**
     * Implementazione Ecommerce\Custom\Cartable (v.)
     * @return string
     */
    public function getType():string{
        return 'pr';
    }


    /**
     * Ritorno il prezzo formattato con currency
     * @param string proprietà prezzo che si desidera formattare
     * @return string prezzo formattato come da impostazioni
     */
    public function getPrice(string $priceProperty = 'Price'){
        if(!property_exists($this,$priceProperty) || !is_float($this->{$priceProperty})):
            throw new \InvalidArgumentException('['.__METHOD__.'] Proprietà '.$priceProperty.' inesistente o in formato non corretto');
        endif;
        return $this->Currency->print($this->{$priceProperty});
    }

    
    /**
     * Implementazione interfaccia Custom\Ecommerce\Cartable
     * Trasforma entità in array valido per essere aggiunto al carrello
     * Inserire qui tutti gli eventuali calcoli in moda da sgravare il carrello
     * ed avere più flessibilità
     */
    public function cartArray() {
        
        $productAr = array();

        $productAr["id"]            = $this->id;

        $productAr["iva"]           = $this->iva;
        $productAr["title"]         = $this->title;
        $productAr["prezzo"]        = $this->Price;
        $productAr["prezzo_iva"]    = $this->PriceIVA;
        $productAr["prezzo_no_iva"] = $this->PriceNoIVA;
        $productAr["prezzo_finale"] = $this->FinalPrice;
        $productAr["url"]           = $this->fullPageUrl;
        $productAr["img"]           = $this->img1_M;
            
        // per ora esiste solo un tipo di prodotto
        $productAr["type"]          = 'pr';

        // per ora prodotti non hanno codice
        $productAr["codice"]        = $this->code;
        
        // calcola perc di sconto in base a prezzi??
        $productAr["discount"]      = $this->discountPerc;
        //$productAr["qta"]           = $this->availability;
        $productAr["qta"]           = 500000;
        $productAr["alcohol"] = $this->alcohol == 'Y';

        return $productAr;

    }


    /**
     * mostro il dettaglio di questo prodotto?
     * @param void
     * @return bool
     */
    public function showDetail(){
        return $this->img1 && strlen($this->description) > 0;
    }


}

