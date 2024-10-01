<?php
/**
 * Repository Prodotti
 * @author Emanuele Fornasier
 */
namespace Custom\Repository;

use PDO;

class Products extends AbsRepository  {


    /**
     * trait per caricamento link alternativi
     */
    use Traits\FullPageUrlAlternates;

    /**
     * Flags per caricare elementi correlati
     */
    protected $withPrice = false;

    /**
     * definizioni
     */
    protected $_table = 'prodotto';
    protected $_sqlPublished = ' AND T.public = \'Y\' AND (NOW() BETWEEN T.date_start AND T.date_end)'; # stringa che mi serve per ignorare eventualmente le pubblicazioni
    //protected $_sqlAvailability = ' AND T.availability > 0';
    protected $_sqlAvailability = '';

    protected $userIdCat = 1;
    

    /**
     * Riceve anche il datamodel categorie per operazioni 
     * interne legate alle categorie
     */
    public function __construct($Da, $LangManager, $datamodel = null, $userIdCat = 1) {
        
        parent::__construct($Da, $LangManager, $datamodel);
        
        // imposto categoria utente
        $this->userIdCat = $userIdCat;
        
    }


    /**
     * query
     */
    protected function _listSql() {
        
        /**
         * Esegue un join esterno sulla medesima tabella per recuperare
         * info del parent (se record è una "variante")
         * Recupera anche le varianti, sta alle query specifiche eliminarle
         * dalla selezione.
         */

        return "
            SELECT
            T.*
            ,IF (T.public = 'Y' AND NOW() BETWEEN T.date_start AND T.date_end, true, false) AS published
            
            ,TL.title
            ,TL.subtitle
            ,TL.descr

            ,TL.url_page
            ,TL.title_page
            ,TL.description_page
            ,TL.lang
            
            ,TC.position AS cat_position
            
            ,TCL.url_page AS cat_url_page
            ,TCL.title AS cat_name

            FROM ".$this->_table." AS T
            INNER JOIN ".$this->_table."_lang AS TL ON TL.id_item = T.id
            INNER JOIN prod_cat AS TC ON TC.id = T.id_cat
            INNER JOIN prod_cat_lang AS TCL ON TCL.id_item = TC.id AND TCL.lang = TL.lang

            WHERE T.id IS NOT NULL ".$this->_sqlPublished.$this->_sqlAvailability;
    }


    /**
     * voglio vedere anchei record non pubblicati
     */
     public function showUnpublished(){
        $this->_sqlPublished = "";
        return $this;
    }

    /**
     * Mostro tutti i prodotti, anche quelli non disponibili
     */
    public function ignoreAvailability(){
        $this->_sqlAvailability = '';
    }


    /**
     * Modificatori per caricare automaticamente elementi relazionati.
     */
    public function withPrice() {
        $this->withPrice = true;
        return $this;
    }

    /**
     * Recupera prodotti direttamente relazionati alla categoria, escludendo le varianti e opzionalmente 
     * un array di id
     * 
     * Integra con filtro, ordinamento e paginazione interni
     */
    public function loadByCat($Cat, $lgId = '', $arToExclude = null):array {
        
        if (!$Cat) {return array();}

        $lgId = $lgId ?: $this->Lang->lgId;


        $allIds = $Cat->catChildren ? array_column($Cat->catChildren,'id') : array();
        $allIds[] = $Cat->id;
        $idsPlaceholders = array_fill(0,count($allIds),'?');

        $exclude = '';
        if(is_array($arToExclude) && count($arToExclude)):
            $exclude = ' AND T.id NOT IN('.implode(',',array_fill(0,count($arToExclude),'?')).')';
            $allIds = array_merge($allIds,$arToExclude);
        endif;

        $allIds[] = $lgId;

        $arProducts = $this->loadFromDb('AND T.id_cat IN ('.implode(',',$idsPlaceholders).') AND TL.lang = ? '.$exclude.$this->_sqlFilter.$this->_sqlOrder.$this->_sqlPager, $allIds);
        
        return $arProducts ?: array();

    }

    /**
     * Recupero i prodotti scontati
     * Esclude varianti
     */
    public function loadDiscounted($lgId, $arToExclude = null){

        $lgId = $lgId ?: $this->Lang->lgId;

        $exclude = '';
        $allIds = array();
        if(is_array($arToExclude) && count($arToExclude)):
            $exclude = ' AND T.id NOT IN('.implode(',',array_fill(0,count($arToExclude),'?')).')';
            $allIds = $arToExclude;
        endif;

        $allIds[] = $lgId;

        $arProducts = $this->loadFromDb("AND TL.lang = ? AND T.id IN (SELECT X.id_main FROM prodotto_rel_cliente_cat AS X WHERE X.id_sec = ? AND X.perc_discount > 0)".$exclude.$this->_sqlFilter.$this->_sqlOrder.$this->_sqlPager, array($allIds));
        
        return $arProducts ?: array();

    }

    /**
     * Recupero successivo
     * Esclude varianti
     */
    public function loadNext($Product, $lgId = null) {
        
        $lgId = $lgId ?: $this->Lang->lgId;
        
        // COALESCE per trasformare "null" in db
        $result = $this->loadFromDb(" AND T.id_cat = ? AND T.position >= ? AND T.id <> ? AND TL.lang = ? ORDER BY T.position ASC, T.id ASC LIMIT 1", array($Product->catId, $Product->position, $Product->id, $lgId));
    
        return $result ? $result[0] : $result;

    }


    /**
     * Recupero precedente
     * Esclude varianti
     */
    public function loadPrev($Product, $lgId = null) {

        $lgId = $lgId ?: $this->Lang->lgId;

        // COALESCE per trasformare "null" in db
        $result = $this->loadFromDb(" AND T.id_cat = ? AND T.position <= ? AND T.id <> ? AND TL.lang = ? ORDER BY T.position DESC,T. id DESC LIMIT 1", array( $Product->catId, $Product->position, $Product->id, $lgId));
    
        return $result ? $result[0] : $result;

    }


    /**
     * Recupera prodotti secondo dei parametri di ricerca
     */
    public function loadBySearchParams($search, $lgId = null) {

        $lgId = $lgId ?: $this->Lang->lgId;

        $sql = " AND TL.lang = ? AND 
            (
                (
                    TL.title LIKE CONCAT('%',?,'%') 
                    OR TL.title_extended LIKE CONCAT('%',?,'%') 
                    OR TL.intro LIKE CONCAT('%',?,'%') 
                    OR TL.main_title LIKE CONCAT('%',?,'%') 
                    OR TL.description LIKE CONCAT('%',?,'%') 
                    OR TL.description_2 LIKE CONCAT('%',?,'%') 
                    OR TL.description_3 LIKE CONCAT('%',?,'%') 
                    OR T.code LIKE CONCAT('%',?,'%') 
                )
                OR 
                (
                    SELECT COUNT(XL.id_item) FROM prodotto_tags_lang AS XL WHERE XL.lang = ? AND XL.id_item IN (SELECT id_sec FROM prodotto_rel_tags WHERE id_main = T.id) AND XL.title LIKE CONCAT('%',?,'%')
                ) 
                OR
                (
                    TCL.name LIKE CONCAT('%',?,'%')
                )
            )".$this->_sqlOrder.$this->_sqlPager;

        //exit($sql);
        if($search):
            $result = $this->loadFromDb($sql,array($lgId,$search,$search,$search,$search,$search,$search,$search,$search,$lgId,$search,$search));
            if($result):
                $to_return = array();
                foreach($result as $R):
                    if(!array_key_exists($R->id,$to_return)):
                        $to_return[] = $R;
                    endif;
                endforeach;
                return $to_return;
            else:
                return $result;
            endif;
        else:
            return null;
        endif;

    }

    /**
     * Riceve array degli elementi già presenti nel carrello,
     * li esclude dal recupero per poi reinsirli, presumo per ovviare a eventuali variazioni di prezzo intercorse?? (manu, 05/04/2021)
     */
    public function loadInCartDetail(array $arCartitems = array(),$lgId = NULL){

        $lgId = $lgId ?: $this->Lang->lgId;
        
        // I prodotti da mostrare nel dettaglio carrello

        $arToExclude = count($arCartitems) > 0
                        ? array_map(function($v){
                                return $v->id;
                            },$arCartitems)
                        : array();
                
        $exclude = '';
        $arParams = array($lgId);
        if(is_array($arToExclude) && count($arToExclude)):
            $exclude = ' AND T.id NOT IN('.implode(',',array_fill(0,count($arToExclude),'?')).')';
            $arParams = array_merge($arParams,$arToExclude);
        endif;

        /**
         * Non considero "sellable", mostro indicazione fuori per eventuali prodotti esauriti
         */
        $result = $this->loadFromDb(" AND TL.lang = ?  ".$exclude." ORDER BY position ASC",$arParams);

        // per compatibilità con array_merge
        if(!$result) {
            $result = [];
        }

        return $result;

    }
}
