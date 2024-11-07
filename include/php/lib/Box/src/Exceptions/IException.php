<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2016-10-03
 */

namespace Box\Exceptions;

/**
 * 
 * Aggiunge all'eccezione predefinita un responso per l'utente valido
 * per essere passato a Box->execResponse.
 * Si possono creare eccezioni che la implementano, ciascuna con un responso specifico,
 * in modo da poterle usare nel gestore di eccezioni predefinito per controllare
 * il flusso dell'applicazione in caso di errore.
 * Passando il responso dell'eccezione a Box->execResponse(), questa restituirà al 
 * client le informazioni appropriate.
 * 
 * v. README e tests/exceptions.php per esempi d'uso.
 */
interface IException 
{

    /**
     * Restuisce responso interno
     * 
     * @return \Box\Response\IResponse
     */
    public function getResponse();

}