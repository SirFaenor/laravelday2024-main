<?php
/**
 * Repository Categorie prodotti
 * @author Emanuele Fornasier
 */
namespace Custom\Repository;

use PDO;

class ProductCats extends AbsRepository {

    
    /**
     * trait per caricamento link alternativi
     */
    use Traits\FullPageUrlAlternates;


    /**
     * definizioni
     */
    protected $_table = 'prod_cat';

    
    /**
     * istanza interna del datamodel delle categorie 
     */
    protected $ProductsRepository;
    

    /**
     * Riceve anche il datamodel categorie per operazioni 
     * interne legate alle categorie
     */
    public function __construct($Da, $LangManager, $datamodel = null, $ProductsRepository = null) {
        
        parent::__construct($Da, $LangManager, $datamodel);
        
        // memorizzo datamodel categorie
        $this->ProductsRepository = $ProductsRepository;
        
    }


    /**
     * query
     */
    protected function _listSql() {
        
        return "
            SELECT
            T.*
            ,TL.title
            ,TL.title_page
            ,TL.description_page
            ,TL.url_page
            ,TL.keywords_page
            ,TL.lang
            FROM ".$this->_table." AS T
            INNER JOIN ".$this->_table."_lang AS TL ON TL.id_item = T.id
            WHERE T.public = 'Y'
            AND (NOW() BETWEEN T.date_start AND T.date_end)
            AND TL.url_page <> '' AND TL.url_page IS NOT NULL 
        ";
    }


    /**
     * caricamenti extra
     */
    protected function parseRecord($rs) {

        // albero completo categoria (in caso di multilivello)
        $rs["cat_tree"] = $this->catTree($rs["id"], $rs["lang"]);

        $rs['cat_children'] = $this->getChildren($rs["id"], $rs["lang"], false);

        return parent::parseRecord($rs);

    }


    /**
     * @repository
     * 
     * Recupero figli diretti di un genitore (1 livello)
     */
    public function loadByParent(\Custom\Model\ProductCat $Cat, $lgId = '') {
        $lgId = $lgId ?: $this->Lang->lgId;
        
        return $this->loadFromDb("AND T.id_item_sup = ? AND TL.lang = ? ".$this->_sqlFilter.$this->_sqlOrder.$this->_sqlPager, array($Cat->id, $lgId));
    }


    /**
     * Recupera lista di tutti i figli (n livelli).
     * Riceve id di categoria di partenza
     * 
     * @param $id
     * @param $currentList array per portare avanti ricorsivamente la lista
     */
    public function loadChildrenIds($id, $currentList = array()) {
        
        $rs = $this->Da->customQuery("SELECT GROUP_CONCAT(id) AS list FROM prod_cat WHERE id_item_sup = ?", array($id));

        if (strlen($rs[0]["list"])) {
        
            $list = explode(",",$rs[0]["list"]);
    
            foreach ($list as $id): 
                array_push($currentList, $id);
            
                $currentList = $this->loadChildrenIds($id,$currentList);
            
            endforeach;

        }

        return $currentList;

    }



    /**
     * Recupera albero delle categorie partendo da un id passato (che viene compreso
     * nell'albero) e risalendo di livello
     * 
     * @param int id di partenza
     * @param int id lingua
     * @param bool se true carica l'albero come array di Modelli, altrimenti recupera solo url
     * @param array serve a portare avanti lista in chiamate ricorsive
     * @return array
     */
    public function catTree($id, $lgId = '', $fullLoad = false, $currentList = array()) {

        $lgId = $lgId ?: $this->Lang->lgId;
        
        $url = null;
        $idItemSup = null;

        if ($fullLoad) :
            $rs = $this->loadById($id);

            $url = $rs ? $rs->pageUrl : null;
            $idItemSup = $rs ? $rs->parentId : null;

        else:
            $rs = $this->Da->customQuery("
                SELECT
                T.id
                ,T.id_item_sup
                ,TL.url_page
                FROM ".$this->_table." AS T
                INNER JOIN ".$this->_table."_lang AS TL ON TL.id_item = T.id
                WHERE T.id = ? AND TL.lang = ?"
                , array($id,$lgId)
                ,PDO::FETCH_ASSOC
            );

            $url = isset($rs[0]) ? $rs[0]["url_page"] : null;
            $idItemSup = isset($rs[0]) ? $rs[0]["id_item_sup"] : null;

        endif;

        //memorizzo url o modello completo
        if ($url) :
            array_push($currentList, $fullLoad ? $rs : $url); 
        endif;

        // se non ho risultato o non ho genitore rovescio l'albero e mi fermo
        if (!$idItemSup) :
            return array_reverse($currentList);
        endif;
        
        // continuo per risalire albero
        return $this->catTree($idItemSup, $lgId, $fullLoad, $currentList);        

    }



    /**
     * Recupera categorie figlie partendo da un id passato e scendendo di livello
     * @param int id di partenza
     * @param int id lingua
     * @param bool se true carica l'albero come array di Modelli, altrimenti recupera solo url
     * @return array
     */
    public function getChildren($id, $lgId = '', $fullLoad = false){

        $children = array();

        if ($fullLoad) :
            $rs = $this->loadById($id);

            $url = $rs ? $rs->pageUrl : null;
            $idItemSup = $rs ? $rs->parentId : null;

        else:
            $arChildren = $this->Da->customQuery("
                SELECT
                T.id
                ,TL.url_page
                FROM ".$this->_table." AS T
                INNER JOIN ".$this->_table."_lang AS TL ON TL.id_item = T.id
                WHERE T.id_item_sup = ? AND TL.lang = ?"
                , array($id,$lgId)
                ,PDO::FETCH_ASSOC
            );

            if($arChildren):
                foreach($arChildren as $CH):
                    $children[] = $CH;
                    $children = array_merge($children,$this->getChildren($CH['id'], $lgId, $fullLoad));
                endforeach;
            endif;
        endif;
        
        // continuo per risalire albero
        return $children;

    }


    public function loadFirstLevelCats($lgId = NULL){
        $lgId = $lgId ?: $this->Lang->lgId;

        return $this->loadFromDb('AND T.level = 1 AND T.id_item_sup = 0 AND TL.lang = ? ORDER BY T.position ASC',array($lgId));

    }
    

}
