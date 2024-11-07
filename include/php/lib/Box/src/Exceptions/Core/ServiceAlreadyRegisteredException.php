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
 * Servizio già registrato in Box
 */
class ServiceAlreadyRegisteredException extends AbsException
{

    public function __construct($name) {
        parent::__construct("Service Already Registered : ".$name, E_RECOVERABLE_ERROR);
    }

}
