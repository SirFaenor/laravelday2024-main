<?php
/**
 * @author  Mauricio Cabral, Jacopo Viscuso, Emanuele Fornasier
 * @link    http://http://www.atrio.it
 * @version 2016/09/30 Emanuele Fornasier
 * @package Box
 */

namespace Box;

/**
 * Gestisce logging degli errori.
 * Non influisce sull'esecuzione dell'applicazione: se viene usato con un gestore di errori /eccezioni,
 * è quest'ultimo a doversi preoccupare dell'eventuale termine dell'esecuzione.
 * In caso di errore fatale, ritorna all'esterno con una funzione definita dal client,
 * per non avere dipendenza su alcun componente interno.
 */
class ErrorLogger
{
        
    /**
     * registro degli errori
     */
    private $arErrorList = array();
    
    /**
     * flag per errore fatale, usato nel distruttore
     * per riconoscere la gravità dell'errore.
     */
    private $fatal = false;


    /**
     * @var array $config impostazioni
     */
    private $config = array(
        
        /**
         * se true, si blocca in caso di errore fatale
         */        
        "debug_mode" => 0
        
        /**
         * cartella scrivibile su cui conteggiare
         * esecuzione di errori multipli
         */
        ,"error_counter_path" => ''
        
        /**
         * callback per la segnalazione errori
         * ad admin. Va registrato all'esterno
         * per massima flessibilità e per non avere
         * dipendenze interne su altri componenti.
         * Viene eseguito solo se la classe NON è impostata
         * in debug mode.
         */
        ,"admin_alert_callback" => null
    );
    
    /* Tabella codici errori di PHP */
    private $arErCode = array (
        -1       => 'UNDEFINED'
        ,0       => 'UNDEFINED'
        ,1      => 'E_ERROR'
        ,2      => 'E_WARNING'
        ,4      => 'E_PARSE'
        ,8      => 'E_NOTICE'
        ,256    => 'E_USER_ERROR' 
        ,512    => 'E_USER_WARNING'     
        ,1024   => 'E_USER_NOTICE' 
        ,2048   => 'E_STRICT'
        ,4096   => 'E_RECOVERABLE_ERROR' 
        ,8191   => 'E_ALL' 
        ,42000   => 'SQLSTATE' 
    );
  
    
    
    /**
     * Istanza
     */
    public function __construct (array $config) {
        $this->config = array_merge($this->config, $config);
    }

    
    /**
     * Avviso di errore.
     * Viene chiamata da handleException() / handleError() per errori di livello gravi.
     * Se sono in debug_mode, interrompo sempre esecuzione
     * e mostro messaggio a video, altrimenti passa a esecuzione
     * del callback interno
     * 
     * Può essere usata anche pubblicamente.
     */
    public function alert($errorMSG)
    {
        
         // in modalità debug, mostro solo messaggio a video
        if ($this->config["debug_mode"]) :
            echo '<pre>'.$errorMSG.'</pre>'; 
            exit; // si passa al distruttore
        endif;


        // Verifica se errore verificato su attacco Ddos
        if ($this->controlDdos() == true) {
            exit("Controllo dos non superato!");
            return;
        }

        // se ho un callback valido, lo eseguo
        if (!$this->config["admin_alert_callback"] || !is_callable($this->config["admin_alert_callback"])) {
            return;
        }
        return $this->config["admin_alert_callback"]($errorMSG);

    }
    
    
    /**
     * Gestore di eccezioni.
     * Avvisa admin se eccezione ha messaggio per admin o se è grave.
     */
    public function handleException($inExc)
    {

        // messaggio errore
        $errorCode = $inExc->getCode() ? $inExc->getCode() : 0;
        $erCodeString = isset($this->arErCode[$errorCode]) ? $this->arErCode[$errorCode] : 'UNDEFINED';
        $str = '<strong>[ECCEZIONE - '.$erCodeString.']</strong> : ' .$inExc->__toString();

        $backtrace = debug_backtrace();

        $start = count($backtrace)-1;
        $end = count($backtrace)-3 > 0 ? count($backtrace)-3 : 0;
        for($i = $start; $i >= $end; $i--):
            if(isset($backtrace[$i]['file']) && isset($backtrace[$i]['line'])):
                $str .= '<br>'.$backtrace[$i]['file'].':'.$backtrace[$i]['line'];
            else:
                if(is_array($backtrace[$i])):
                    foreach($backtrace[$i] as $BI):
                        $str .= '<br>'.print_r($BI,1);
                    endforeach;
                else:
                    $str .= '<br>'.urldecode(http_build_query($backtrace[$i]));
                endif;
            endif;
        endfor;


        // Avviso admin se ho errore grave (o se non ho codice) o se l'eccezione contiene un messaggio
       if (
                $errorCode == 256
            ||  $errorCode == 0
            ||  $inExc->getMessage()
        ):
            
            $this->fatal = true;

            // notifica ad admin
            return $this->alert($str);
        endif;

        // Log interno dell'errore
       $this->logError($str);


  
    }


    /**
     * Gestore di errori.
     * Avvisa admin in caso di errore grave.
     */
    public function handleError($errno, $errstr, $errfile, $errline) {


        $errno = $errno && isset($this->arErCode[$errno]) ? $errno : 0;
        $str = '<strong>[ERRORE - '.$this->arErCode[$errno].']</strong> : '.$errstr . ' in ' .$errfile. ' line ' .$errline;

        $backtrace = debug_backtrace();
        $start = count($backtrace)-1;
        $end = count($backtrace)-3 > 0 ? count($backtrace)-3 : 0;
        for($i = $start; $i >= $end; $i--):
            if(isset($backtrace[$i]['file']) && isset($backtrace[$i]['line'])):
                $str .= '<br>'.$backtrace[$i]['file'].':'.$backtrace[$i]['line'];
            else:
                $str .= '<br>'.urldecode(http_build_query($backtrace[$i]));
            endif;
        endfor;
            
        // Log dell'errore
        $this->logError($str);


        // se errore grave, non ho codice o errore è sconosciuto
        if (in_array($errno, array(0,1,4,2,256)) || !array_key_exists($errno, $this->arErCode)) {

            //  imposto a fatal per essere informato in modalità debug(v. distruttore)
            $this->fatal = true;
            
            // notifica ad admin
            $this->alert($str);

        }           
        
    }
    

    /**
     * Distruttore
     */
    public function __destruct() {
        
        if ($this->config["debug_mode"]) :

            $this->printErrors();
            
            // se sono in fatal
            if ($this->fatal) {
                echo '<br /><span style="color: red">APPLICAZIONE INTERROTTA !!</span>';
            }
        endif;

    }

    
    /**
     * Memorizza errore in variabile interna.
     */
    private function logError($inMsg) {
        array_push($this->arErrorList, $inMsg);
    }


    /**
     * Stampa array interno che contiene gli errori
     */
    public function printErrors() {
        
        // Qui potrei decidere se visualizzare o meno gli errori memorizzati
        // in base ad un livello di debug impostato in configurazione.
        // Per ora non registro il livello in $arErrorList e quindi mostro tutto.   
        if(count($this->arErrorList) ) {
            echo '<pre>';
            foreach ($this->arErrorList as $k => $msg) {
                echo nl2br($msg) . '<br />';
            }
            echo '</pre>';
        }

    }


    /**
     * controlla frequenza del verificarsi dell'errore
     * Chiamata in alert() per evitare loop di messaggi multipli
     * 
     * @return true se frequenza degli errori è al di sopra della soglia consentita
     */
    public function controlDdos(){
        
        // Definizione / Verifica / Creazione cartella
        if (!$this->config["error_counter_path"]) {
            return false;
        }
        $dir = $this->config["error_counter_path"];
        if(!file_exists($dir)){ mkdir($dir); chmod($dir, 0777); }
        
        // Nome file da IP + Filename Script
        $errorFile = $dir.'/'.sha1($_SERVER['REMOTE_ADDR'].$_SERVER['SCRIPT_FILENAME']).'.txt';
        
        $toReturn = false;


        // Se il file non esiste (primo errore)
        if(!file_exists($errorFile)) :
            $fp = fopen($errorFile, 'w');   // Apertura file in sola scrittura
            fwrite($fp, time().'|1');       // Contenuto: attuale Timestamp|valore "1" primo errore
            fclose($fp);                    // Chiudo il file

        // Se il file esiste, ma è vecchio (errore registrato a più di 20 min, si ricomincia da 1)
        elseif(filemtime($errorFile) < (time() - 1200)) :

            @unlink($errorFile);      // Cancellazione file

            $fp = fopen($errorFile, 'w');   // Apertura file in sola scrittura
            fwrite($fp, time().'|1');       // Contenuto: attuale Timestamp|valore "1" primo errore
            fclose($fp);                    // Chiudo il file

        // Se l'errore è già stato registrato (incremento conteggio)
        else :

            $fp = fopen($errorFile, 'r+');                              // Apertura file in scrittura e lettura
            $fileContent = fread($fp, 5096);
            $fileContent = explode('|', $fileContent);
            $fileContent[1] = (int)$fileContent[1];

            // controllo limite di errori
            if($fileContent[1] >= 20) :
                $toReturn = true;                                      // Limite raggiunto, controllo positivo
            else :

                ftruncate($fp, 0);
                rewind($fp);

                if($fileContent[0] > (time() - 3)) :                    // Controllo tempo ultimo errore, se entro 3 secondi
                    fwrite($fp, time().'|'.++$fileContent[1]);
                else :
                    fwrite($fp, time().'|'.$fileContent[1]);
                endif;          
            endif;
            

            fclose($fp);
            
        endif;

        return $toReturn;
    
    }

}
