<?php
namespace Utility\Traits;

use PDO;

/**
 * Trait per recupero e la normalizzazione dei dati di una nazione partendo da un id
 */
trait NationData
{
    
    /**
     * Link alternativi in lingua
     */
    public function nationData($nationId) {

        $thisNazione = $this->Da->getSingleRecord(array(
            'model'     => 'NAZIONI'
            ,'cond'     => 'AND X.id = :id AND XL.lang = :lgId AND XL.title <> "" AND XL.title IS NOT NULL HAVING published'
            ,'params'   => array('id' => $nationId, 'lgId' => $this->Lang->lgId)
        ));

        if(!$thisNazione):
            trigger_error('['.__METHOD__.'] Nessuna nazione con id '.$nationId.' per la lingua con id '.$this->Lang->lgId,E_USER_ERROR);
        endif;

        return array(
            'id_nazione'        => $thisNazione['id']
            ,'sigla_nazione'    => strtoupper($thisNazione['sigla'])
            ,'nazione'          => $thisNazione['title']
        );

    }


}