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
trait LoadGallery
{

    public function loadGallery($id) {

        $result = $this->Da->customQuery("
            SELECT
            T.id_item,
            T.id,
            T.image,
            TL.caption
            FROM ".$this->_table."_image AS T
            LEFT JOIN ".$this->_table."_image_lang AS TL ON TL.id_img = T.id AND TL.lang = ".$this->Lang->lgId." 
            WHERE T.id_item = ?
            ORDER BY T.position ASC
        ", array($id));

        return $result ?: null;

    }

}