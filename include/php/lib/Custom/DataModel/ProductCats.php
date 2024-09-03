<?php
/**
 * modello Categorie prodotti
 * @author Emanuele Fornasier
 */
namespace Custom\DataModel;

use \Custom\Model as Model;

use PDO;

class ProductCats extends AbsDataModel {


    /**
     * Integra mappatura di base
     */
    public function definePropertyMap() {
            
        return array_merge(
            parent::definePropertyMap(),
            array(
                "title"        => "title"
                ,"id_item_sup"  => "parentId"
                ,"lang"         => "lang"
                ,"title_page"   => "metaTitle"
                ,"description_page" => "metaDescription"
                ,"keywords_page"=> "metaKeywords"
                ,"url_page"     => "pageUrl"
            )
        );

    }

    
    /**
     * creazione dell'entità
     */
    protected function modelInstance() {
        return new Model\ProductCat();
    }


    /**
     * Integra creazione modello
     */
    public function _mapFromDb($model, $rs) {

        $model = parent::_mapFromDb($model, $rs);

        // url completa (informazione restituita dal repository)
        $urlTree = $rs["cat_tree"];

        // passo anche parametro lgSuff al LangManager, a seconda della lingua del record.
        // Per restituire lingua corretta nel caso abbia recuperato il record durante
        // la generazione dei link alternativi in lingua
        $model->setProperty("fullPageUrl", $this->Lang->returnL(
            "products_cat",
            array(implode('/',$urlTree)),
            $this->Lang->suffFromId($rs["lang"])
        ));

        $model->setProperty('catChildren',$rs['cat_children']);

        return $model;

    }

}
