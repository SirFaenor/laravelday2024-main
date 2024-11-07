<?php
/**
 * LangManager
 * -----------------------------------------------------------------------------------------------
 * Gestisce traduzioni dei testi e dei link
 * 
 * carica traduzioni attraverso LoadTrads 
 * carica link attraverso LoadLinks (mantengo due metodi separati per poter usare flessibilmente la classe in casi in cui si necessiti solo delle
 * traduzioni o solo dei link.)
 * memorizza variabili d'ambiente (formato numeri, dati, ciò che si vuole..) per le funzioni o gli script che ne hanno bisogno
 * esempio d'uso :
 *  $Lang->loadTrads();                     | posso chiamare questi due metodi indipendentemente a seconda del bisogno
 *  $Lang->loadLinks();                     |
 * 
 * @dipendenze
 * Da -> DataAccess per accesso al db
 * 
 * @author Emanuele Fornasier
 * @version 2017/11/10
 *      
 * 
 * TODO:
 * dividere la logica della gestione lingua da quella della gestione link (es. priority)
 * ( creare altra classe ??)
 * =================================================================================================================================================
*/
namespace LangManager;

use Exception;

use PDO;

class LangManager extends \AtrioTeam\LangManager\LangManager {
    
    private $linkLoaded = false;            //| per evitare doppie chiamate al db
    private $tradLoaded = false;            //|
    
    private $strError = '';                 // stringa degli errori (output nel distruttore)
    private $arRewrite = array();           // array delle regole di rewrite
    private $lgList = array();              // array(id => suff) di tutte le lingue
    private $lgTagList = array();           // array dei tag lingua internazionali
    private $arLinks = array();             // array dei link
    private $arTrads = array();             // array delle traduz.
    private $groupList = '';                // lista dei gruppi (per switchLanguage)
    
    protected $pdo;

    public $lgId;                           // id lingua attiva
    public $lgSuff;                         // suff lingua attiva
    public $lgTag;                          // suff per indicazione codifica
    private $_activeLinkKey = null;             // key del link attivo
    
    private $scriptName = false;            // per impostare link attivi
    private $arOptions = array (
        'display_errors' => 0
        ,'language_tags' => 0
        ,'base_url' => ''
        // nome tabella concatenata a colonna per filtrare lingue attive
        ,'table_lg' => 'lang.active'
        ,'table_link' => 'lang_link'
        ,'table_trad' => 'lang_trad'
        // se impostato a stringa, forza il caricamento di quella lingua
        ,'force_language' => false
        
        // se impostato a stringa, forza il caricamento di quella lingua
        ,'default_language' => 'it'
        
        // gruppi caricati di default (separati da virgola)
        ,'groups' => "global"
        
        // abilita cartella lingua
        ,'enable_language_dir' => true

        // chiave del link per cui non va la language_dir (root del sito) se nella lingua base
        ,'root_key' => 'homepage'
    );


    /**
     * array interno dei link alternativi in lingua
     */
    private $lgAlternates;


    /**
     * @var pdo PDO instance
     * @var options array
     */
    public function __construct($pdo, array $options = array()) {

    
        // Connessione db
        $this->pdo = $pdo;

        // Opzioni
        if ($options) :
            $this->arOptions = array_merge($this->arOptions, $options);
        endif;
        
        // Lingue disponibili
        $option = explode(".", $this->arOptions["table_lg"]);
        $table = $option[0];
        $active = isset($option[1]) ? $option[1] : 'active';

        $result = $this->pdo->query('SELECT * FROM '.$table.' WHERE '.$active.' = 1 ORDER BY id ASC', PDO::FETCH_OBJ);
        foreach ($result as $key => $rs): 
            // lista suffissi
            $this->lgList[$rs->id] = $rs->suffix;

            //lista info
            $this->lgRecord[$rs->id] = array();
            foreach ( $rs as $k => $v) :
                $this->lgRecord[$rs->id][$k] = $v;
            endforeach;
            
        endforeach;     

        // suffix lingua (ita di default - cerco e imposto anche in sessione per eventuali altri script )
        $request_uri = !empty($_SERVER['REQUEST_URI']) ? str_replace($this->arOptions["base_url"], '', $_SERVER['REQUEST_URI']) : '';
        $dati_url = explode('/', $request_uri);
       
        // se ho passato forzatura lingua, la uso            
        if ($this->arOptions["force_language"] && strlen($this->arOptions["force_language"]) ) :
            $_SESSION['lgSuff'] = $this->lgSuff = $this->arOptions["force_language"];
        else : 
            $_SESSION['lgSuff'] = $this->lgSuff = 
                    !empty($dati_url[1]) && in_array($dati_url[1], $this->lgList)
                ?   $dati_url[1]
                :   (
                            !empty($_SESSION['lgSuff']) && in_array($_SESSION['lgSuff'], $this->lgList)
                        ?   $_SESSION['lgSuff']
                        :   $this->arOptions["default_language"]
                    )
            ;
        endif;


        // Id lingua
        $this->lgId = in_array($this->lgSuff, $this->lgList) ? array_search($this->lgSuff, $this->lgList) : 1;
        if ($this->arOptions['language_tags']) {$this->lgTag = $this->lgRecord[$this->lgId]["language_tag"];}

    }
    
    public function __destruct() {
        if ($this->arOptions['display_errors'] && $this->strError) {echo $this->strError;}
    }
    
    public function setOption($name, $value) {
        if (array_key_exists($name,$this->arOptions)) {
            $this->arOptions[$name] = $value;
        }
    }
    

    /**
     * carica link in variabile interna
     * La priortià col valore più alto va in cima, risultando prioritaria nella mappatura dei link per il router
     * (nel caso che più espressioni regolari combacino con l'url)
     */
    public function loadLinks() {

        
        $result = $this->pdo->query('SELECT * FROM '.$this->arOptions["table_link"].' ORDER BY priority DESC', PDO::FETCH_ASSOC);
        foreach ($result as $key => $rs): 
            
            // se il link è quello impostato come root del sito e la lingua è la principale            
            $link = isset($this->arOptions['root_key']) && $rs['code'] == $this->arOptions['root_key'] && $this->lgRecord[$this->lgId]['position'] == 1 
                                ? $this->arOptions["base_url"].'/' 
                                : $this->parseLinkOnLoad($rs['link_'.$this->lgSuff]);

            // memorizzo array su key "code"
            $this->arLinks[$rs['code']]['link'] = $link; 
            $this->arLinks[$rs['code']]['filename'] = $rs['filename'];
            $this->arLinks[$rs['code']]['pattern'] = preg_match("#\(([^\)]+)\)#", $link) ? 1 : 0;
            
            // memorizzo array su key "link" per avere rapido accesso al file corrispondente
            $this->arFiles[$link] = $rs['filename'];

            // memorizzo alternates
            $this->arAlternates[$rs["code"]] = array();
            foreach ($this->lgRecord as $key => $lg) {
                $this->arAlternates[$rs['code']][$lg["suffix"]] = isset($this->arOptions['root_key']) && $rs['code'] == $this->arOptions['root_key'] && $lg['position'] == 1 
                                ? $this->arOptions["base_url"].'/' 
                                : $this->parseLinkOnLoad($rs["link_".$lg["suffix"]], $lg["suffix"]);
            }

        endforeach;

        unset($result);
        unset($rs);
        
        $this->linkLoaded = true;

    }


    /**
     * separa logica di generazione del link
     * dal valore in db
     */
    protected function parseLinkOnLoad($dbValue, $lgSuff = '') {

        // se non ho richiesto suffix specifico uso quello attivo
        $lgSuff = $lgSuff ?: $this->lgSuff;

        $suffLink = $this->arOptions["enable_language_dir"] ? '/'.$lgSuff : '';

        $dbValue = $dbValue ?? '';

        // mantengo i link assoluti
        return
            strstr($dbValue, 'http://') || strstr($dbValue, 'https://')
            ?   $dbValue
            :   $this->arOptions["base_url"].$suffLink.$dbValue
        ; 

    }


    /**
     * carica link in variabile interna
     * carica SEMPRE traduzioni con key "global"
     * @var groupList lista dei gruppo separati da virgola
     */
    public function loadTrads ($groupList = '', $lgSuff = NULL) {
        
        $this->groupList = !empty($groupList) ? $groupList : $this->groupList;

            // aggiungo gruppi di default
        if (strlen($this->arOptions["groups"])) :
            $this->groupList = $this->arOptions["groups"].",".$this->groupList;
        endif;
        

        // Recupero traduzioni dal db
        $sql_gruppi = '';
        if (!empty($this->groupList)) {
            $sql_gruppi = " OR (";
            foreach (explode(",", $this->groupList) as $key => $gruppo) {
                $sql_gruppi .= " group_code = '". $gruppo."' OR";   
            }
            $sql_gruppi = rtrim($sql_gruppi, " OR");
            $sql_gruppi .= ")";
            $sql_gruppi .= " ORDER BY FIELD(group_code";
            foreach (explode(",", $this->groupList) as $key => $gruppo) {
                $sql_gruppi .= " ,'". $gruppo."'";   
            }
            $sql_gruppi .= ")";
        }
        
        // 'global' sempre + eventuali altri gruppi passati
        $result = $this->pdo->query("SELECT ".implode(",",array("code","text_".$this->lgSuff))." FROM ".$this->arOptions["table_trad"]."  WHERE group_code = 'global'".$sql_gruppi, PDO::FETCH_ASSOC);

        if ($result) :

            foreach ($result as $key => $rs): 
                $testo = NULL;
                
                $this->arTrads[$rs['code']] = $rs['text_'.$this->lgSuff];

            endforeach;
        endif;
        
        $this->tradLoaded = true;
        
    }
    

    /**
     * restituisce nome del file associato ad un link
     *
     * @var string $link
     * @returns nome file o null
     */
    public function getFile($link) {
        return array_key_exists($link, $this->arFiles) ? $this->arFiles[$link] : NULL;
    }


    /**
     * cambia lingua ricaricando link e traduzioni
     *
     * @var bool
     */
    public function switchLanguage($suff) {
        
        $this->lgSuff = $suff;
        $this->lgId = array_search($this->lgSuff, $this->lgList);

        if (!$this->lgId) {
            return false;
        }

        // Azzero classe..
        $this->tradLoaded = false; 
        $this->linkLoaded = false;
        $this->arTrads = array(); 
        $this->arLinks = array(); 

        // e la ricarico
        $this->loadTrads();
        $this->loadLinks();
    }
    
    
    /**
     *  accesso ai link
     */
    public function echoL($key, $replace_keys = array(), $lgSuff = null) {
        echo $this->returnL($key, $replace_keys, $lgSuff);
    }

    /**
     * Se il suffix richiesto non è quello corrente,
     * recupero il link dall'array degli alternates.
     * (utile per generare tag <link rel="alternates">)
     */
    public function returnL($key, $replace_keys = array(), $lgSuff = null) {
        
        if (!isset($this->arLinks[$key]['link'])) { 
            trigger_error("Link mancante: ".$key,E_USER_NOTICE); 
            return; 
        }

        $link = $lgSuff && $lgSuff != $this->lgSuff ? $this->arAlternates[$key][$lgSuff] : $this->arLinks[$key]['link'];

        if ($this->arLinks[$key]["pattern"] && $replace_keys) {
            $link = $this->_parseL($link, $replace_keys);
        }

        return $link;
    }

    /**
     * Sostituisce variabili interne a valore link con quelle passate.
     * Se è un pattern, sostituisce ciascun parametro / espressione regolare
     * all'interno del link con l'indice corrispondente
     * passato in $replace_keys.
     * Es. link /it/news/{{(.*)}}.php
     */
    private function _parseL($link, $replace_keys = array()) {

        // cerco e rimpiazzo le espressioni (le stringhe all'interno di "()"  )
        //  con i valori passati.
        foreach ($replace_keys as $replace): 
            $link = preg_replace("#\(([^\)]+)\)#", $replace, $link, 1);
        endforeach;

        // rimpiazzo il delimitatore
        $link = str_replace('$','',$link);

        return $link;
    
    }

    /**
     * Output classe link attivo
     */
    public function echoActive($keys_to_search = array()) {
        echo $this->returnActive($keys_to_search);
    }

    public function returnActive($keys_to_search = array()) {
        
        // nel caso abbia passato una stringa
        $keys_to_search = is_array($keys_to_search) ? $keys_to_search : array($keys_to_search);

        if (in_array($this->getActive(),$keys_to_search)) {
            return 'active';
        }
    }

    public function getLinks() {
        return $this->arLinks;
    }


    /**
     *  accesso alle traduzioni
     *  @param bool as_string voglio un json?
     *  @return array
     *  @return string
     */
    public function getTrads($as_string = false) {

        $arToReturn = array();
        if($this->arTrads):
            foreach($this->arTrads as $k => $T):
                $arToReturn[$k] = $this->replaceVars($T);;
            endforeach;
        endif;
        return $as_string === true ? json_encode($arToReturn) : $arToReturn;
    }
    

    private function _formatT($string, $format) {
            
        switch ($format) :
            case 'cpt' : {$string = ucfirst($string); break;}
            case 'upp' : {$string = strtoupper($string); break;}
            case 'low' : {$string = strtolower($string); break;}
        endswitch;
        
        $string = $this->replaceVars($string);

        return $string;
    }
    
    public function echoT($key, array $replace_keys = array()) {
        echo $this->returnT($key, $replace_keys);
    }
    
    public function returnT($key, array $replace_keys = array()) {

        if (!array_key_exists($key, $this->arTrads)) {
            trigger_error('Traduzione mancante: '.$key, E_USER_NOTICE);
            return '';
        } 

        return $this->_parseT($this->arTrads[$key], $replace_keys);
        
    }
    

    /**
     * Sostituisce variabili interne a testo traduzione con quelle passate
     */
    private function _parseT($text, $replace_keys) {

        foreach ($replace_keys as $search => $replace): 
            $text = str_replace("{{".$search."}}", $replace, $text);       
        endforeach;

        // per retrocompatibilità
        return $this->replaceVars($text);
    }
       

    /**
     * Elenca lingue disponibili
     */
    public function listLanguages() {
        return $this->lgList;
    }


    public function suffFromId($lgId) {
        return $this->lgRecord[$lgId]["suffix"];
    }
    

    /**
     * Elenca lingua corrente
     */
    public function getLanguage($id = 0) {
        if (empty($id)) {$id = $this->lgId;}
        return $this->lgRecord[$id];
    }
    

    /**
     * Inserite per retrocompatibiltà con
     * versioni precedenti
     */
    public function getAllLanguages() {
        return $this->lgRecord;
    }
    

    /**
     * Ritorno le lingue impostate come visibili
     */
    public function getPublicLanguages() {
        $to_return = array();
        foreach($this->lgRecord as $id => $data):

            # se non è presente un dato VISIBLE ritorno tutte le lingue
            if(!isset($data['visible'])):
                throw new Exception('['.__METHOD__.'] Non è presente un campo <code>visible</code>; ritorno tutti i records', E_USER_NOTICE);
                return $this->lgRecord;
                break;
            endif;

            if($data['visible']):
                $to_return[] = $data;
            endif;
        endforeach;

        // se non ci sono record ERRORE
        if(!count($to_return)):
            throw new Exception('['.__METHOD__.'] Nessuna lingua visibile nel frontend. ', E_USER_ERROR);
        endif;

        return $to_return;
    }
    
    public function getCountry($id = 0) {
        if (empty($id)) {$id = $this->lgId;}
        return $this->lgRecord[$id];
    }

    
    /**
     * Sostituzione variabili dinamiche, usata da _formatT 
     */
    public function replaceVars($TEXT) {
        
        /// SOSTITUZIONE TAG LING
        $matches = array();
        preg_match_all('~\{LINK:(\w+)\}~', $TEXT, $matches);
                foreach ($matches[1] as $k => $trad_key_link) :
            $TEXT = str_replace($matches[0][$k], $this->returnL($trad_key_link) ,$TEXT);
        endforeach;
        
        
        /// SOSTITUZIONE TAG VAR
        $matches = array();
        preg_match_all('~\{VAR:(\w+)\}~', $TEXT, $matches);
        foreach ($matches[1] as $k => $var_name) :
            eval('global $'.$var_name.';');

            $replace = eval('return $'. $var_name . ';') ?: '';

            $TEXT = str_replace($matches[0][$k], $replace , $TEXT);
        endforeach;
        
        return $TEXT;
    }


    /**
     * imposta manualmente un link come attivo
     * (utile nel caso di link multipli che hanno lo stesso nome file )
     */
    public function setActive ($key) {
        $this->_activeLinkKey = $key;
    }
    
    public function getActive () {
        
        if (!$this->_activeLinkKey) {
            while ( (list($key, $value) = each($this->arLinks) ) && $this->_activeLinkKey == null) {
                if(!strlen($value["filename"])) continue;
               
                $this->_activeLinkKey = strstr($_SERVER["SCRIPT_NAME"],$value["filename"]) ? $key : null;
            }
        }

        return $this->_activeLinkKey;
    }
    
    
    /**
     * stampa tutti i link 
     */
    public function printLinks() {
        foreach ($this->arLinks as $k => $v) {
            echo '<br />'.$k.' => '.$v.'<br />';
            
            foreach ($v as $k2 => $v2) {
                echo "\t".$k2.' => '.$v2;
            }
        }
    }
    
    
    /**
     * stampa testo per htaccess da mappa dei link interna
     */
    public function printHtaccess() {
        $result = $this->pdo->getRecords(array(
            "table" => "lingue_link"
            ,"cond" => " ORDER BY code ASC "
        ));
        foreach ($result as $rs): 
            $this->arRewrite[$rs['code']] = $rs;        
        endforeach;

        foreach ($this->arRewrite as $key => $row) :
            foreach ($this->lgList as $id => $suff) {
                echo 'RewriteRule ^'.$suff.'/'.(!empty($row['link_'.$suff]) ? $row['link_'.$suff] : 'MISSING_LINK!!').'$ /'.(!empty($row['filename']) ? $row['filename'] : 'MISSING_FILE!!').' [L,QSA]<br />'."\n";
            }
        endforeach;
    }
    
    /**
     * Restituisce link alternativo per una data lingua
     */
    public function lgAlternate($targetSuff, $replacements = array(), $key = null) {

        // se non ho passato chiave o non ho link attivo internamente
        $key = $key ?: $this->_activeLinkKey;
        if (!$key) {return;}

        return $this->returnL($key, $replacements, $targetSuff);

    }
    
    
    /**
     * Restituisce tutti i link alternativi.
     * I replacements se passati devono essre un array bidimensionale
     * avente come chiave nel primo livello il suffix lingua. 
     */
    public function getAllAlternates($key = null, $replacements = array(), $isMain = false) {

        // se non ho passato chiave o non ho link attivo internamente
        $key = $key ?: $this->_activeLinkKey;
        if (!$key) {return;}

        foreach ($this->lgRecord as $lg) {
            if(!$lg['visible']): continue; endif;
            $lgReplacements = isset($replacements[$lg["suffix"]]) ? $replacements[$lg["suffix"]] : array();
            $alternates[$lg["language_tag"]] = $this->returnL($key, $lgReplacements, $lg["suffix"]);
        }

        return $alternates;


    }



}
