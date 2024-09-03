<?php
namespace Utility\Traits;

use PDO;

/**
 * Trait per recupero e la normalizzazione dei dati di una nazione partendo da un id
 */
trait ProvinceData
{
    
    /**
     * recupero le informazioni sulle province
     */
    public function provinceData($provinceId) {

        $thisProv = $this->Da->getSingleRecord(array(
            'model'     => 'PROVINCE'
            ,'cond'     => 'AND X.id = :id AND XL.lang = :lgId AND XL.title <> "" AND XL.title IS NOT NULL HAVING published'
            ,'params'   => array('id' => $provinceId, 'lgId' => $this->Lang->lgId)
        ));

        if(!$thisProv):
            trigger_error('['.__METHOD__.'] Nessuna provincia con id '.$provinceId,E_USER_NOTICE);
        endif;

        return array(
            'id_provincia'        => $thisProv['id']
            ,'sigla_provincia'    => strtoupper($thisProv['sigla'])
            ,'provincia'          => $thisProv['title']
        );
    }

    /**
     * verifico che una determinata provincia esista per una specifica nazione
     */
    public function checkProvince($provinceId,$nationId){
        return $this->Da->countRecords(array(
                    'model'     => 'PROVINCE'
                    ,'cond'     => 'AND X.id = :provinceId AND X.id_cat = :nationId AND XL.title <> "" AND XL.title IS NOT NULL HAVING published'
                    ,'params'   => array(
                                        'provinceId'  => $provinceId
                                        ,'nationId'    => $nationId
                                    )
                )) > 0 ? true : false;
    }

}