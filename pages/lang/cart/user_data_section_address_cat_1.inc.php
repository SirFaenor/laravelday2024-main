                    <label class="required w75">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('indirizzo'); ?></span>
                        <input type="text" name="indirizzo" id="indirizzo" value="<?php echo $Form->getValue('indirizzo'); ?>" data-input_label="<?php echo $App->Lang->returnT('indirizzo'); ?>" required>
                    </label>
                    <label class="float_right required w25">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_civico'); ?></span>
                        <input type="text" name="civico" id="civico" value="<?php echo $Form->getValue('civico'); ?>" data-input_label="<?php echo $App->Lang->returnT('label_civico'); ?>" required>
                    </label>
                    <label class="required float_right">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_citta'); ?></span>
                        <input type="text" name="citta" id="citta" value="<?php echo $Form->getValue('citta'); ?>" data-input_label="<?php echo $App->Lang->returnT('label_citta'); ?>" required>
                    </label>
                    <label class="required w25">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_cap'); ?></span>
                        <input type="text" name="cap" id="cap" value="<?php echo $Form->getValue('cap'); ?>" data-input_label="<?php echo $App->Lang->returnT('label_cap'); ?>" required>
                    </label>
        <?php 
        // se ho le province imposto vincoli, altrimenti testo libero
        if($arGeographicData['constraints']['province'] === true):
                echo '          <label class="label_select required w25">
                                    <span class="placeholder_fixed">'.$App->Lang->returnT('label_provincia').'</span>
                                    <select name="id_provincia" id="id_provincia" data-input_label="'.$App->Lang->returnT('label_provincia').'" required>'.PHP_EOL;
            foreach($arGeographicData['provinces']['shipping'] as $spedIdNaz => $PN):
                if($PN):
                    foreach($PN as $P):
                        echo '          <option'.($spedIdNaz == $Form->getValue('id_nazione') ? ' class="active"' : '').' value="'.$P['id'].'"'.($P['id'] ==  $Form->getValue('id_provincia') ? ' selected' : '').' data-id_naz="'.$spedIdNaz.'" data-provincia="'.$P['title'].'">'.$P['title'].'</option>'.PHP_EOL;
                    endforeach;
                endif;
            endforeach;
                echo '              </select>
                                </label>'.PHP_EOL;
        else:
            echo '              <label class="required w25">
                                    <span class="placeholder_fixed">'.$App->Lang->returnT('label_provincia').'</span>
                                    <input type="text" name="provincia" id="provincia" value="'.$Form->getValue('provincia').'" data-input_label="'.$App->Lang->returnT('label_provincia').'" required>
                                </label>'.PHP_EOL;
        endif;

        // se ho continenti e nazioni imposto i vincoli, altrimenti testo libero
        if($arGeographicData['constraints']['nation'] === true):
            $thisCont = count($arGeographicData['continents']['shipping']) == 1 ? reset($arGeographicData['continents']['shipping']) : NULL;
            $thisNaz = $thisCont !== NULL &&  isset($arGeographicData['nations']['shipping'][$thisCont['id']]) && count($arGeographicData['nations']['shipping'][$thisCont['id']]) == 1 ? reset($arGeographicData['nations']['shipping'][$thisCont['id']]) : NULL;

            // se ho una sola nazione disponibile mostro il testo altrimenti mostro il select
            if($thisCont !== NULL && $thisNaz !== NULL):
                echo '          <p class="label one_option w100">
                                    <span class="placeholder_fixed">'.$App->Lang->returnT('label_nazione').'</span>
                                    <span class="input">'.$thisNaz['title'].'</span>
                                    <input type="hidden" name="id_nazione" value="'.$thisNaz['id'].'" data-input_label="'.$App->Lang->returnT('label_nazione').'" data-sigla_nazione="'.$thisNaz['sigla'].'" required>
                                </p>'.PHP_EOL;
            else:
                echo '          <label class="label_select w100">
                                    <span class="placeholder_fixed">'.$App->Lang->returnT('label_nazione').'</span>
                                    <select name="id_nazione" id="id_nazione" data-input_label="'.$App->Lang->returnT('label_nazione').'" required>'.PHP_EOL;
                            echo '      <option value=""></option>'.PHP_EOL;
                foreach($arGeographicData['continents']['shipping'] as $C):
                    if($arGeographicData['nations']['shipping'][$C['id']]):
                        foreach($arGeographicData['nations']['shipping'][$C['id']] as $N):
                            echo '      <option value="'.$N['id'].'"'.($N['id'] ==  $Form->getValue('id_nazione') ? ' selected' : '').' data-sigla_nazione="'.$N['sigla'].'">'.$N['title'].'</option>'.PHP_EOL;
                        endforeach;
                    endif;
                endforeach;
                echo '              </select>
                                </label>'.PHP_EOL;
            endif;
        else:
            echo '              <label class="w100">
                                    <span class="placeholder_fixed">'.$App->Lang->echoT('label_nazione').'</span>
                                    <input type="text" name="nazione" id="nazione" value="'.$Form->getValue('nazione').'" data-input_label="'.$App->Lang->returnT('label_nazione').'" required>
                                </label>'.PHP_EOL;
        endif;