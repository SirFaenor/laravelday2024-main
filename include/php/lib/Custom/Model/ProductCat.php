<?php
/**
 * @author Emanuele Fornasier
 */
namespace Custom\Model;

class ProductCat extends StdModel implements Multilevel
{


    /**
     * Estensione proprietà di base
     */
    protected $title;
    protected $lang;
    protected $metaTitle;
    protected $metaDescription;
    protected $metaKeywords;

    protected $pageUrl;
    protected $fullPageUrl;
    protected $catChildren;

    protected $parentId;

    /**
     * implementazione interfaccia Multilevel per annidamento padre/figlio
     */
    public function getPrimaryKey() {
        return $this->id;
    }
    
    public function getParentPrimaryKey() {
        return $this->parentId;
    }

    public function getPropertyNameForChildren() {
        return "Products";
    }

}

