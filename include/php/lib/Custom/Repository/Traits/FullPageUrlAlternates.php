<?php
namespace Custom\Repository\Traits;

use PDO;

/**
 * Trait per recupero link alternativi in lingua
 * usato da diversi Repository
 * $this è l'istanza del repository in cui è usato.
 * Poichè usa il metodo loadById del repository stesso, 
 * presuppone che questo ritorni un Modello (quindi deve aveere un DataModel interno)
 */
trait FullPageUrlAlternates
{
    
    /**
     * Link alternativi in lingua
     */
    public function fullPageUrlAlternates($itemId) {

        $alts = array();

        foreach ($this->Lang->getAllLanguages() as $key => $lg): 
            $item = $this->loadById($itemId, $lg["id"]);
            if (!$item) {continue;}
            $alts[$lg["language_tag"]] = $item->fullPageUrl;
        endforeach;

        return $alts;

    }


}