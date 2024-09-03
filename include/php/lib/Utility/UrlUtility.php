<?php
/**
 * Classe statica di utilità legate alle url disponibile globalmente.
 *
 * @author Emanuele Fornasier, Jacopo Viscuso, Mauricio Cabral
 * @version 2017/06/20
 */

namespace Utility;

class UrlUtility {

    /**
     * Recupera dall'url l'ultimo livello, quello dell'elemento corrente
     * Rimuove eventuale costante suffisso dettaglio (es. '_d')
     * @param directory : directory base di part. per categorie e/o dettaglio es(/ita/news)
     */
    public function getItemUrl($suff_to_remove = '') {
        
        $path = parse_url($_SERVER['REQUEST_URI'],  PHP_URL_PATH);
        
        if ($suff_to_remove) :
            $item =  preg_split('/'.$suff_to_remove.'/', pathinfo($path,  PATHINFO_FILENAME), NULL, PREG_SPLIT_DELIM_CAPTURE);
            return $item[0];
        else :
            return  pathinfo($path,  PATHINFO_FILENAME);
        endif;
        
    }



    /**
     * Recupera dall'url l'albero delle categorie, PULITO da directory di base
     * @param directory : directory base di part. per categorie e/o dettaglio es(/ita/news)
     */
    public function getCatUrl($directory) {
        
        $cleanUrl = preg_replace("(".$directory.")", "", pathinfo($_SERVER['REQUEST_URI'], PATHINFO_DIRNAME),1);

        $cleanUrl = ltrim($cleanUrl,'/'); // rimuovo eventuale slash a sinistra in caso di mancanza di livello superiore
        
        return $cleanUrl;       

    }


    /**
     * Recupera un livello dell'url dato l'indice passato
     * (iniziando da destra)
     * @param zeroIndexFromEndOfUrl : indice zero del livello partendo da destra, nome file compreso
     */
    public function getUrlItemByReverseIndex($zeroBasedIndexFromEnd, $url = null) {
        
        $url = $url ? $url : self::requestUri();

        $url = parse_url($url, PHP_URL_PATH);

        // rimuovo estensione
        $url = str_replace('.'.pathinfo($url, PATHINFO_EXTENSION),'',$url);
        
        // rimuovo eventuale slah finale
        $url = rtrim($url, '/');

        $url = explode("/", $url);

        $url = array_reverse($url);

        return isset($url[$zeroBasedIndexFromEnd]) ? $url[$zeroBasedIndexFromEnd] : null; 

    }



    /**
     * Recupera manualmente request_uri per uniformare
     * differenze nella variabile $_SERVER
     * (es. iCosi)
     */
    public function requestUri($url = '') {
        $url = $url ?: $_SERVER["REQUEST_URI"];
        return preg_replace('%^(http:|https:)?//'.$_SERVER["HTTP_HOST"].'%', "", $url);   
    }

  

}

