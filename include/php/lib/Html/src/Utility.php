<?php
namespace Html;

/**
 * Classe statica che accorpa funzioni utili varie per html
 */
class Utility 
{

    /**
     * Elimina interruzioni di riga
     */
    static function noLineBreak ($string) {
        $search = array ("\n", "\r", "\n\r", "\r\n", "/n", "/r", "/n/r", "/r/n");
        $replace = array ("", "", "", "", "", "", "");
        
        return str_replace($search, $replace, $string); 
        
    }


    /**
     * Converte i br in spazi
     * (per eliminare i br ed evitare che due parole risultino attaccate)
     */
    static function br2space ($string) {
        $search = array ("<br>", "<br/>", "<br />");
        $replace = array (" ", " ", " ");

        return str_replace($search, $replace, $string); 
    }


    /**
     * Tronca una strinaga in un certo numero di parole
     */
    static function maxWords($stringa, $max, $strip, $continue = '') {
        
        // Tolgo i tags html se indicato
        if($strip){$stringa = strip_tags($stringa);} 
        
        $stringa_x = explode(' ', $stringa);
        $stringa2 = '';
        
        if(count($stringa_x) > $max){
            for($i = 0; $i < $max; $i++){
                $stringa2 .= $stringa_x[$i].' ';
            }
            $stringa2 .= $continue; 
            $stringa = $stringa2;
        }
        return $stringa;
    }
    
    /**
     * Tronca una stringa in un certo numero di parole
     * permettendo solo dati tag
     */

    static function maxWordsTag($stringa, $max, $tag){

        require_once __DIR__.'/../../../vendor/htmlpurifier/library/HTMLPurifier.auto.php';
        
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.AllowedElements', $tag);
        $config->set('AutoFormat.AutoParagraph', false);
        $purifier = new \HTMLPurifier($config); // Inizializzo
        $stringa = $purifier->purify($stringa); // Purifico
        
        $stringa_x = explode(' ', $stringa);
        $stringa2 = '';

        
        if(count($stringa_x) > $max){
            for($i = 0; $i <= $max; $i++){
                $stringa2 .= $stringa_x[$i].' ';
            }
            $stringa2 .= '...'; // Aggiugo i (...) se la stringa  pi lunga del massimo
            $stringa = $stringa2;
        }
        return $stringa;

    }

    /**
     * Tronca una stringa in un certo numero di lettere
     */
    static function maxChars($stringa, $max, $stripTag = 0,$continue = ''){
        
        if($stripTag){
            $stringa = strip_tags($stringa);
            $stringa = str_replace("\n", '', $stringa);
            $stringa = str_replace("\r", '', $stringa);
            $stringa = str_replace("\t", '', $stringa);
        }
        
        if(strlen($stringa) > $max){
            $stringa = mb_substr($stringa, 0, $max, 'UTF-8').' '.$continue;
        }
        
        return $stringa;
    }

    
    public static function fixstr($string, $quot = 0){
        
        require_once __DIR__.'/../../../vendor/htmlpurifier/library/HTMLPurifier.auto.php';
            
        // configrazione di HTMLPurifier
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.AllowedElements', 'table,caption,tr,td,tbody,thead,tfoot,th,em,u,strong,br,p,a,ul,li,ol,span,h1,h2,h3,h4,h5,h6,sup,sub,iframe');
        $config->set('Attr.AllowedRel', 'blank');
        $config->set('Core.EscapeInvalidChildren', 'true');
        $config->set('Core.EscapeInvalidTags', 'true');
        $config->set('Attr.AllowedFrameTargets', '_blank');
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', 'true');
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(http:|https:)?//(www.youtube(?:-nocookie)?.com/embed/|player.vimeo.com/video/|www.slideshare.net/slideshow|www.google.com)%'); //allow YouTube and Vimeo
        //$config->set('HTML.TidyLevel', 'none');
        $config->set('AutoFormat.AutoParagraph', false);
        
        // purificazione 
        $purifier = new \HTMLPurifier($config); 
        $string = $purifier->purify($string);
    
        // converto entità html
        $string = htmlentities($string,  ENT_QUOTES, 'utf-8');
    
        if(!get_magic_quotes_gpc()){$string = addslashes($string);}
        $string = addcslashes($string, "=");
        $string = addcslashes($string, "[");
        $string = addcslashes($string, "]");
        $string = addcslashes($string, "#");
        
        // Ripristino alcuni elementi per avere html corretto
        if(!$quot){ $string = str_replace('&quot;', '\"', $string); } 
        $string = str_replace('&amp;', '&', $string);
        $string = str_replace('&lt;', '\<', $string);
        $string = str_replace('&gt;', '\>', $string);
        
        return $string;
    }


    static function whitespaceToBr($string) {
        return str_replace(" ", "<br> ", $string);
    }


}