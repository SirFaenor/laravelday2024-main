<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2016-12-23
 */

namespace Box;

use Exception;

/**
 * App class
 * 
 * Agisce su alcune variabili d'ambiente
 * Gestisce risposte http (anche in caso di errore)
 * Contiene una serie di metodi di utilità.
 * Estende la classe Container in modo da poter essere anche usata come iniettore
 * di dipendenze e factory per qualsiasi tipo di componente.
 * 
 * 
 * Dipendenze
 * - Box\Params (in fase di costruzione può essere fornita già la configurazione)
 * - Box\Container
 * 
 */
require_once(__dir__.'/Container.php');

class App extends Container
{

    /**
     * @var array $env
     * array che memorizza variabili di ambiente su cui la classe pouò intervenire
     */
    private $_env;

    /**
     * @var $_exceptionsHandler elenco dei
     * gestori di eccezioni / errori registrati
     */
    private $_exceptionsHandler = array();
    private $_errorsHandler = array();
    

    /**
     * Riceve configurazione base dell'applicazione
     * Ammette sovrascrittura delle impostazioni di default.
     * 
     * @param object Box\Params\Config impostazioni base dell'applicazione 
     * (viene memorizzato come servizio)
     */
    public function __construct (\Box\Params $Config = null) {

        if ($Config) {
            // memorizza configurazione globale
            $this->store("Config", $Config);
        }

        $this->_env["error_reporting"] = ini_get("error_reporting");
        $this->_env["display_errors"] = ini_get("display_errors");

    }

    /**
     * Registra funzione interna di autoloading per le classi.
     * 
     * @param string $basePath directory di base
     * @param array $namespacesMap mappatura namespace => cartella
     *        per i namespace che non rispecchiano esattamente il percorso
     *        sul filesystem
     */
    public function registerAutoload($basePath, array $namespacesMap = array()) {

        // aggiungo namespacesMap di Box nell'autoload
        if (!isset($namespacesMap["Box"])) {$namespacesMap["Box"] = "Box/src";}
        
        spl_autoload_register (function($request) use ($namespacesMap, $basePath)  {        
           

            // divido nome della classe da percorso dei namespace
            $pos = strrpos($request, '\\'); // posizione ultimo "\"
            if ($pos !== false) {
                $prefix = substr($request, 0, $pos + 1);
                $className = substr($request, $pos + 1);
            } else {
                $className = $request;
                $prefix = '';
            }

            // controlla se la prima directory del percorso dei namespace è a sua volta
            // mappata in una cartella specifica
            if ($prefix) {
               
                // prima directory
                $base = substr($prefix, 0, strpos($prefix, '\\'));
                $prefix = str_replace('\\','/', $prefix);

                // sostituisco prima direcorty con la sottocartella
                if (array_key_exists($base, $namespacesMap)) {
                    $prefix = str_replace($base, $namespacesMap[$base], $prefix);
                }
            }

            // nome file completo
            $fileName =  $basePath . '/' . $prefix . $className.'.php';
            
            // include la classe
            if (file_exists($fileName)) :
                require $fileName;
            endif;


        });


    }


    /**
     * Aggiunge una clousure all'elenco dei gestori registrati per le eccezioni.
     * Per ogni eccezione, viene chiamata ciascuna funzione registrata
     * nell'ordine inverso in cui sono state registrate, ogni eccezione 
     * risalirà al contrario lo stack dei gestori finchè
     * una di queste non fermerà l'esecuzione.
     * 
     * @param closure or null, se null ripristina gestore
     * @todo inserire parametro per un pre-filtro sulla base
     * del tipo di eccezione.
     */
    public function onException($closure = null) {
       
        if ($closure == null) {return restore_exception_handler();}
       

        /**
         * supporto a php 5.3
         */
        if (version_compare(PHP_VERSION_ID, '5.4.0') < 0) :
            return set_exception_handler($closure);        
        endif;


        array_unshift($this->_exceptionsHandler, $closure);

        /**
         * registra un nuovo gestore a cui passare tutto lo stack 
         * degli handler registrati
         */
        restore_exception_handler();
        return set_exception_handler(function ($exception) {
            foreach ($this->_exceptionsHandler as $handler): 
                $handler($exception);   
            endforeach;
        });

    }


    /**
     * Equivalente a onException() ma per gli errori.
     * Registrando un gestore, tutti gli errori vengono
     * reindirizzati qui.
     * 
     * @param callable or null, se null ripristina gestore
     * @todo inserire parametro per un pre-filtro sulla base
     * del tipo di errore.
     */
    public function onError($closure = null) {
            
        if ($closure == null) {return restore_error_handler();}
        
        /**
         * supporto a php 5.3
         */
        if (version_compare(PHP_VERSION_ID, '5.4.0') < 0) :
            return set_error_handler($closure);        
        endif;

        array_unshift($this->_errorsHandler, $closure);
        
        /**
         * registra un nuovo gestore a cui passare tutto lo stack 
         * degli handler registrati
         */
        restore_error_handler();
        return set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
            foreach ($this->_errorsHandler as $handler): 
                $handler($errno, $errstr, $errfile, $errline, $errcontext);   
            endforeach;
        });

    }
    

    /**
     * Registra una funzione di shutdown per gli errori fatali.
     */
    public function onFatal($closure) {
        register_shutdown_function(function() use($closure) {
        
            // se ho un ultimo errore fatale
            $error = error_get_last();

            if (in_array($error["type"], array(1,4))) {
                $closure($error);
            }
        });

    }    


    /**
     * Restituisce un parametro singolo da una richiesta
     * o direttamente da $_REQUEST se non viene passato nulla
     */  
    public function getFromRequest($index, array $request = array())
    {

        if (!$request) {$request = $_REQUEST;}

        return isset($request[$index]) ? $request[$index] : null;
    }

   
    /**
     * Helper funzionale per redirect.
     * Usa internamente un \Box\Response appropriato
     */
    public function redirect($url, $statusCode = 302) {
       
        $headers = array(301 => 'HTTP/1.1 301 Moved Permanently', 302 => 'HTTP/1.1 302 Found');

        if (array_key_exists($statusCode, $headers)) {
            header($headers[$statusCode]);
        }

        if(!strlen($url)) {
            throw new Exception("Url vuota");
        }

        header("Location: ".$url);
        exit;

    }


}
