<?php
/**
 * @author Emanuele Fornasier
 * 
 */
namespace Custom\Repository\Traits;

use PDO;

/**
 * Trait per caricamento gallery
 * Assume che la tabella gallery si chiami come 
 * la tabella del datamodel in uso + il suffiso "_image"
 */
trait LoadRelated
{

    public function loadRelated($id_main,$source_table,$rel_table) {

        $result = $this->Da->customQuery("
            SELECT
            T.id,
            TL.title,
            TL.url_page
            FROM ".$source_table." AS T
            LEFT JOIN ".$source_table."_lang AS TL ON TL.id_item = T.id AND TL.lang = ".$this->Lang->lgId." 
            WHERE T.id IN (SELECT id_sec FROM ".$rel_table." WHERE id_main = ?)
            ORDER BY T.position ASC
        ",array($id_main));

        return $result ?: null;

    }

}