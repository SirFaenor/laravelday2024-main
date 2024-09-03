<?php 

// definisco i filtri per i dati geografici (se calcolo spese spedizione o altro)
$sql_published = ' HAVING published';
if(isset($calc_sped_amount) && $calc_sped_amount === true):
    $sql_published = '';
endif;

$arContinenti = array();

// ottengo e organizzo nazioni e continenti sia per fatturazione che per spedizione
$arContinenti['invoice'] = $App->Da->getRecords(array(
    'model'     => 'NAZIONI'
    ,'cond'     => 'AND XL.lang = ? AND X.level = 1 AND XL.title <> "" AND XL.title IS NOT NULL'.$sql_published.' ORDER BY XL.title ASC'
    ,'params'   => array($App->Lang->lgId)
));
$arContinenti['shipping'] = $arContinenti['invoice'];


$arNazioni = $arProvince = null;

# devo usare select (true) o testo libero (false) per nazione e provincia? 
$constrains_field_text_id_nazione        = false;
$constrains_field_text_id_provincia      = false;
$constrains_field_text_sped_id_nazione   = false;
$constrains_field_text_sped_id_provincia = false;

if($arContinenti['invoice']):  // ottengo le nazioni per i continenti
    $arNazioni = $arProvince = array();
    foreach($arContinenti['invoice'] as $kC => $C):
        $arNazioni['invoice'][$C['id']] = $App->Da->getRecords(array(
            'model'     => 'NAZIONI'
            ,'cond'     => 'AND XL.lang = ? AND X.level = 2 AND X.id_item_sup = ? AND XL.title <> "" AND XL.title'.$sql_published.' IS NOT NULL ORDER BY XL.title ASC'
            ,'params'   => array($App->Lang->lgId,$C['id'])
        ));
        if(!$arNazioni['invoice'][$C['id']]):   // se non ci sono nazioni per questo continente annullo il continente
            unset($arNazioni['invoice'][$C['id']]);
            unset($arContinenti['invoice'][$kC]);
            unset($arContinenti['shipping'][$kC]);
        else:

            $arNazioni['shipping'][$C['id']] = $arNazioni['invoice'][$C['id']];
            
            $constrains_field_text_id_nazione = true;
            $constrains_field_text_sped_id_nazione = true;

            foreach($arNazioni['invoice'][$C['id']] as $kN => $N):

                $arProvince['invoice'][$N['id']] = $App->Da->getRecords(array(
                    'model'     => 'PROVINCE'
                    ,'cond'     => 'AND XL.lang = ? AND X.id_cat = ? AND XL.title <> "" AND XL.title IS NOT NULL HAVING published ORDER BY XL.title ASC'
                    ,'params'   => array($App->Lang->lgId,$N['id'])
                ));
                if(!$arProvince['invoice'][$N['id']]):   // se non ci sono province per questa nazione annullo la nazione
                    unset($arProvince['invoice'][$N['id']]);
                else:
                    $arProvince['shipping'][$N['id']] = $arProvince['invoice'][$N['id']];
                endif;


                if(!strlen($sql_published)):

                    // verifico che la nazione abbia una spedizione attiva
                    $has_expeditions = $App->Da->customQuery("
                        SELECT 
                        COUNT(id) AS totale
                        FROM spedizioni 
                        WHERE public = 'Y' AND (NOW() BETWEEN date_start AND date_end) AND 
                        id IN (SELECT id_sec FROM elenco_stati_rel_spedizioni WHERE id_main = ".$N['id'].")
                    ");

                    // se non ci sono spedizioni per questa nazione la rimuovo
                    if(!$has_expeditions[0]['totale']):
                        unset($arNazioni['shipping'][$C['id']][$kN]);
                        continue;
                    endif;


                    # verifico le province
                    if(isset($arProvince['shipping'][$N['id']])):
                        foreach($arProvince['shipping'][$N['id']] as $kP => $P):
                            $has_expeditions_p = $App->Da->customQuery("
                                SELECT 
                                COUNT(id) AS totale
                                FROM spedizioni 
                                WHERE public = 'Y' AND (NOW() BETWEEN date_start AND date_end) AND 
                                id IN (SELECT id_sec FROM elenco_province_rel_spedizioni WHERE id_main = ".$P['id'].")
                            ");
                            if(!$has_expeditions_p[0]['totale']):
                                unset($arProvince['shipping'][$N['id']][$kP]);
                                continue;
                            endif;

                            $constrains_field_text_id_provincia = true;
                            $constrains_field_text_sped_id_provincia = true;
                                                            
                        endforeach;

                        # metto una voce vuota all'inizio di province
                        array_unshift($arProvince['invoice'][$N['id']],array('id' => '', 'title' => ''));
                        array_unshift($arProvince['shipping'][$N['id']],array('id' => '', 'title' => ''));
                    endif;
                endif;

            endforeach;
        endif;

        // se non ho nessuna nazione nel continente lo tolgo dalle spedizioni
        if(!array_key_exists($C['id'],$arNazioni['shipping']) || (!count($arNazioni['shipping'][$C['id']]) && array_key_exists($kC,$arContinenti['shipping']))):
            unset($arContinenti['shipping'][$kC]);
        endif;
    endforeach;
endif;

return array(
	'continents'	=> $arContinenti
	,'nations'		=> $arNazioni
	,'provinces'	=> $arProvince
	,'constraints'	=> array(
			'nation'		=> $constrains_field_text_id_nazione
			,'sped_nation'	=> $constrains_field_text_sped_id_nazione
			,'province'		=> $constrains_field_text_id_provincia
			,'sped_province'=> $constrains_field_text_sped_id_provincia
		)
);
