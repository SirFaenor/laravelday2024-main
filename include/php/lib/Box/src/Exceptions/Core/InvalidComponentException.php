<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @license MIT License
 * @version 2017-04-04
 */

namespace Box\Exceptions\Core;

use \Box\Exceptions\AbsException as AbsException;

/**
 * Contiene messaggio di errore interno
 * (passandola a Box\ErrorLogger causerà alert ad admin)
 * Lanciata da Box quando si richiama un componente non registrato
 */
class InvalidComponentException extends AbsException
{   

    public function __construct($componentName) {
        parent::__construct("Componente / Servizio non registrato : ".$componentName, E_RECOVERABLE_ERROR);
    }

}
