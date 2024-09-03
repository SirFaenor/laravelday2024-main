<?php
namespace Utility;

use PDO;

/**
 * Trait di utilità per mappare i dati sulle colonne di una tabella
 */
class DataUtility
{
    
    /**
     * ritorno solamente i dati che hanno corrispondenza nelle colonne
     */
    public static function MapData(array $columns,array $data) {

        foreach($data as $col => $D):
            if(array_search($col,$columns) === false):
                unset($data[$col]);
            endif;
        endforeach;

        return $data;

    }

}