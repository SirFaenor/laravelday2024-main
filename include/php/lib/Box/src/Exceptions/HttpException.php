<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2017-04-04
 */

namespace Box\Exceptions;


/**
 * 
 * Aggiunge all'eccezione predefinita un responso valido per l'uscita dall'applicazione.
 * Si possono creare eccezioni che la implementano, ciascuna con un responso specifico,
 * in modo da poterle usare nel gestore di eccezioni predefinito per controllare
 * il flusso dell'applicazione in caso di errore.
 * 
 * v. README e tests/exceptions.php per esempi d'uso.
 */
interface HttpException 
{

    /**
     * Restituisce status header
     * 
     * @return string
     */
    public function getResponseStatus();
    

    /**
     * Restuisce messaggio di risposta interno
     * 
     * @return string
     */
    public function getResponseMessage();
    

    /**
     * Restuisce codice http
     * 
     * @return string
     */
    public function getResponseCode();

}