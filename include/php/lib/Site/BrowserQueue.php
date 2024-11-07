<?php
namespace Site;

/**
 * Gestisce memorizzazione e ritorno della code dei referer
 */
class BrowserQueue
{
    
    private $arOptions = array (
        // numero massimo di referer da salvare
        'max_items' => 10
        ,'session_index' => 'ar_referer'
    );

    /**
     * @var bool
     * Di default, salva sempre
     */
    private $saveReferer = true;
    

    /**
     * @var array
     * Lista dei referer
     */
    private $queue = array();


    public function __construct(array $options = array()) {

        // opzioni
        if ($options) :
            $this->arOptions = array_merge($this->arOptions, $options);
        endif;

        // recupero / init della sessione
        $this->arReferer = isset($_SESSION[$this->arOptions["session_index"]]) ? $_SESSION[$this->arOptions["session_index"]] : array();

    }


    public function __destruct() {


        // verifico che non ce ne siano troppi, se è così rimuovo quelli in eccesso
        if(count($this->arReferer) >= $this->arOptions['max_referers']): 
            array_splice($this->arReferer, $this->arOptions['max_referers'] - 1, (count($this->arReferer) - $this->arOptions['max_referers'] + 1));
        endif;
        
        // impostato no salvataggio
        if($this->saveReferer === false){return;} 

        // inserisco l'url corrente all'inizio dell'array
        $k = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
        if(count($this->arReferer) && $k !== null && $this->arReferer[0] != $k):
            array_unshift($this->arReferer,$k);
        endif;
        $_SESSION[$this->arOptions["session_index"]] = $this->arReferer;

    }


    /**
     * Imposta flag di non salvataggio
     */
    public function noSave() {
        $this->saveReferer = false;
    }

    /**
     * Imposta flag di salvataggio (se dovesse servire annullare il noSave)
     */
    public function save() {
        $this->saveReferer = true;
    }

    
    /**
     * restituisce un elemento della coda
     */
    public function getReferer($key = 0){

        if (isset($this->arReferer[$key])) {return $this->arReferer[$key];}

        return  isset($this->arReferer[0]) ? $this->arReferer[0] : null;

    }

    /**
     * Recupera l'ultima visita differente dall'uri passato
     */
    public function getDistinctReferer($url = null) {

        $url = $url ?: $_SERVER["REQUEST_URI"];

        foreach ($this->arReferer as $key => $value): 
            if ($value != $url) {return $value;}
        endforeach;

        return null;

    }

}