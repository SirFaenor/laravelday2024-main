<?php
/**
 * modello Modelli di prodotto Prodotti
 * @author Jacopo Viscuso
 */
namespace Custom\DataModel;

use \Custom\Model as Model;

use PDO;

class Products extends AbsDataModel  {

    /**
     * istanza di \Ecommerce\CalculatorService per operare sui prodotti
     */
    protected $CalculatorService;


    /**
     * istanza di \Utility\Currency per formattare in modo coerente i prezzi
     */
    protected $Currency;


    /**
     * Estendo costruttore per ricevere B2bCartService
     * 
     * @param object $LangManager
     * @param object \Ecommerce\CalculatorService 
     * @param object \Utility\Currency per gestire la formattazione dei prezzi
     */
    public function __construct($LangManager = null, \Ecommerce\CalculatorService $CalculatorService,\Utility\Currency $Currency = null) {
        $this->Lang = $LangManager;
        $this->CalculatorService = $CalculatorService;
        $this->Currency = $Currency;
    }

    /**
     * Integra mappatura di base
     */
    public function definePropertyMap() {
            
        return array_merge(
            parent::definePropertyMap(),
            array(

                "id"            => "id"
                ,"cliente_id_cat"=> "clientCatId"

                ,"id_cat"       => "catId"
                ,"cat_position" => "catPosition"
                ,"cat_name"     => "catTitle"
                ,"cat_url_page" => "catPageUrl"

                ,"code"         => "code"
                ,"lang"         => "lang"

                ,"title"        => "title"
                ,"subtitle"     => "subtitle"
                ,"descr"        => "description"

                ,"title_page"   => "metaTitle"
                ,"description_page" => "metaDescription"
                ,"keywords_page"=> "metaKeywords"
                ,"url_page"     => "pageUrl"
                
                ,"id_iva"       => "ivaId"
                ,"iva"          => "iva"

            )
        );

    }


    /**
     * creazione dell'entità
     */
    protected function modelInstance() {
        return new Model\Product();
    }
    

    /**
     * Integra creazione modello
     */
    public function _mapFromDb($model, $rs) {

        $model = parent::_mapFromDb($model, $rs);

        $model->setProperty("Currency", $this->Currency);

        // link dettaglio
        $urlTree = array($rs["cat_url_page"]);

        // passo anche parametro lgSuff al LangManager, a seconda della lingua del record
        // per restituire lingua corretta nel caso abbia recuperato il record durante
        // la generazione dei link alternativi in lingua
        $model->setProperty("fullPageUrl", $this->Lang->returnL(
            "products_detail",
            array(implode('/',$urlTree).'/'.$rs["url_page"]),
            $this->Lang->suffFromId($rs["lang"])
        ));


        /**
         * utilizzata vecchia label "vegetarian" ma valorizzo informazione "vegan"
         */
        $model->setProperty("vegan",($rs['vegetarian'] == 'Y'));
        $model->setProperty("glutenfree",($rs['glutenfree'] == 'Y'));
        $model->setProperty("alcohol",($rs['alcohol'] == 'Y'));
        $model->setProperty("expiringOrder",($rs['expiring_order'] == 'Y'));
        $model->setProperty("sellable",($rs['sellable'] == 'Y'));


        /**
         * Caricamento dati extra se mi arrivano dati relativi
         */

        $model->setProperty("Price", (array_key_exists('price',$rs) ? (float)$rs['price'] : null));
        $splittedPrice = $this->CalculatorService->splitPriceByVat($model->Price,$model->iva);
        $model->setProperty("PriceNoIVA", (float)$splittedPrice[0]);
        $model->setProperty("PriceIVA", (float)$splittedPrice[1]);
        
        # AL MOMENTO NON HO PREZZI SCONTATI
        $model->setProperty("DiscountedPrice", $model->Price);
        $model->setProperty("DiscountedPriceNoIVA", (float)$splittedPrice[0]);
        $model->setProperty("DiscountedPriceIVA", (float)$splittedPrice[1]);
        
        $model->setProperty("FinalPrice", $model->Price);
        $model->setProperty("FinalPriceNoIVA", (float)$splittedPrice[0]);
        $model->setProperty("FinalPriceIVA", (float)$splittedPrice[1]);
        
        $model->setProperty("PercDiscount", 0);
        $model->setProperty("HasDiscount", ($model->PercDiscount > 0));

        $model->setProperty("img1", $rs["img_1"] ? $this->Lang->returnL('prodotto_img',array($rs['id'],'',$rs['img_1'])) : null);
        $model->setProperty("img1_S", $rs["img_1"] ? $this->Lang->returnL('prodotto_img',array($rs['id'],'S',$rs['img_1'])) : null);
        $model->setProperty("img1_M", $rs["img_1"] ? $this->Lang->returnL('prodotto_img',array($rs['id'],'M',$rs['img_1'])) : null);
        $model->setProperty("img1_L", $rs["img_1"] ? $this->Lang->returnL('prodotto_img',array($rs['id'],'L',$rs['img_1'])) : null);

        return $model;

    }

}
