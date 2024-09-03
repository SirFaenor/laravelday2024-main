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
 * Servizio non modificabile
 */
class CannotModifyServiceException extends AbsException
{

    public function __construct($name) {
        parent::__construct("Cannot modify a service : ".$name, E_RECOVERABLE_ERROR);
    }

}
