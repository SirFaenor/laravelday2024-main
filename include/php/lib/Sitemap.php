<?php
/**
 * Sitemap
 * 
 * Website xml/html sitemap generator
 * 
 * 
 * @web www.atrio.it
 * @author - Emanuele Fornasier
 * @version 2016-05-02
 * 
 */
class Sitemap  {

    /**
     * @var private $_map internal items collection
     */
    private $_map = array();
    
    /**
     * @var private $_domainUrl domain base url
     */
    private $_domainUrl = '';


    /**
     * defaults for xml nodes
     */
    private $_defaultXmlPriority = 0.4;
    private $_defaultXmlChangefreq = 'monthly';


    /**
     * Sets domain's url
     * 
     * @param $domain domain's url
     */
    public function setDomain($domainUrl) {
        $this->_domainUrl = $domainUrl;
    }


    /**
     * Adds item to collection
     */
    public function add($code, $url, $labelTitle = null, array $xmlExtraTags = array()) {

        $node = array(
            "code" => $code
            ,"url" => $url
            ,"label" => $labelTitle
            ,"tags" => $xmlExtraTags
        );

        $this->_map[$code] = $node;

    }


    /**
     * Appends child to existing item
     */
    public function addChild($parentCode, $code, $url, $labelTitle = null, array $xmlExtraTags = array()) {

        $node = array(
            "code" => $code
            ,"url" => $url
            ,"label" => $labelTitle
            ,"tags" => $xmlExtraTags
        );

        $this->_searchAndAppend($parentCode, $this->_map, $node);

    }


    /**
     * Recursively loops through array searching for parent
     * and appends $item to it.
     * 
     * @param $parentCode parent item's code
     * @param $array array to loop through
     * @item child to append
     */
    private function _searchAndAppend($parentCode, & $array, $item) {
        
        foreach ($array as $code => & $value):  
            
            if ($code == $parentCode) {

                $value["children"] = isset($value["children"]) ? $value["children"] : array();

                $value["children"][$item["code"]] = $item;

                return;
            }
            
            if (isset($value["children"]) && count($value["children"])) {
                $this->_searchAndAppend($parentCode, $value["children"], $item);
            }

        endforeach;   
    
    }


    /**
     * Prints html code (nested <ul>)
     * 
     */
    public function printHtml() {
        
        echo '<ul class="level_01">'.PHP_EOL;

        foreach ($this->_map as $item) :
            $this->_printHtmlItem($item);
        endforeach;

        echo '</ul>'.PHP_EOL;

    }


    /**
     * Prints single $item.
     * Loops recursively through $item's children
     * 
     * @param array $item item to print
     * @param int $level list deepness level 
     */
    private function _printHtmlItem($node,$level = 2) {

        echo '<li>'.PHP_EOL;                 

        $url = $node["url"] ? $this->_domainUrl.str_replace($this->_domainUrl, "", $node["url"]) : '#';
        
        echo '  <a href="'.$url.'">'.$node["label"].'</a>'.PHP_EOL;

        if (isset($node["children"]) && count($node["children"])) :
            echo '<ul class="level_'.str_pad($level,2,'0',STR_PAD_LEFT).'">'.PHP_EOL;
    
            foreach ($node["children"] as $key => $child) :
                $this->_printHtmlItem($child,($level+1));
            endforeach;
           
            echo '</ul>'.PHP_EOL;

        endif;

        echo '</li>'.PHP_EOL;
        

    }


    /**
     * Prints Xml
     */
    public function printXml() {

        header("Content-Type: application/xml; charset=UTF-8").PHP_EOL; 

        echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

        $this->_printXmlNode($this->_map);

        echo '</urlset>'.PHP_EOL;
        
    }

    /**
     * Loops recursively through array and prints single nodes.
     * 
     * @param $array array to start from
     */
    private function _printXmlNode($array) {
        
        foreach ($array as $key => $node) :

            if ($node["url"]) :

                // Ensures one occurrence of domain base url
                $url = str_replace($this->_domainUrl, "", $node["url"]);
                $url = $this->_domainUrl.$node["url"];

                $priority = isset($node["tags"]["priority"]) ? $node["tags"]["priority"] : $this->_defaultXmlPriority;
                $changefreq = isset($node["tags"]["changefreq"]) ? $node["tags"]["changefreq"] : $this->_defaultXmlChangefreq;

                echo '<url>'.PHP_EOL;
                echo '<loc><![CDATA['.$url.']]></loc>'.PHP_EOL;
                echo '<changefreq>'.$changefreq.'</changefreq>'.PHP_EOL;
                echo '<priority>'.$priority.'</priority>'.PHP_EOL;
                echo '</url>'.PHP_EOL;
            endif;

            if (isset($node["children"]) && count($node["children"])) :
                $this->_printXmlNode($node["children"]);
            endif;

        endforeach;
    }


}

