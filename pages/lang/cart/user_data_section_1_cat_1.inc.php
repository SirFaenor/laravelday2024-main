<?php
/**
 * =============================================================================================
 * 
 * Form dati per utenti privati
 * 
 * =============================================================================================
 */
?>
                <h2 class="cart_section_title"><?php $App->Lang->echoT('your_data'); ?></h2>

                <fieldset class="user_data">
                    <!-- <label class="">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_nome'); ?></span>
                        <input type="text" name="nome" id="nome" value="<?php echo $Form->getValue('nome'); ?>" aria-required="true" required>
                    </label>
                    <label class=" float_right">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_cognome'); ?></span>
                        <input type="text" name="cognome" id="cognome" value="<?php echo $Form->getValue('cognome'); ?>" aria-required="true" required>
                    </label> -->
                    <label class="required ">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_email'); ?></span>
                        <input type="email" name="email" id="email" value="<?php echo $Form->getValue('email'); ?>" aria-required="true" required>
                    </label>
                    <label class="required float_right">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_email_confirm'); ?></span>
                        <input type="email" name="email_confirm" id="email_confirm" value="<?php echo $Form->getValue('email_confirm'); ?>" aria-required="true" required>
                    </label>
                    <!-- <label class="required w100">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_telefono'); ?></span>
                        <input type="tel" name="telefono" id="telefono" value="<?php echo $Form->getValue('telefono'); ?>" aria-required="true" required>
                    </label> -->
                    <input <?php echo $Form->getValue("takeaway") == 'N' || !$Form->getValue("takeaway") ? 'checked' : '' ?> form="user_form" type="radio" name="takeaway" id="takeaway_checkbox_N" class="input_checkbox" aria-required="true" required data-input_label="<?php $App->Lang->echoT('label_vassoio'); ?>" value="N" autocomplete="false"> 
                    <label class="label_radio required" for="takeaway_checkbox_N" style="margin-right: 4em;">
                        <?php $App->Lang->echoT('label_vassoio'); ?> 
                        <br><br>
                    </label>
                    <input <?php echo $Form->getValue("takeaway") == 'Y' ? 'checked' : '' ?> form="user_form" type="radio" name="takeaway" id="takeaway_checkbox_Y" class="input_checkbox" aria-required="true" required data-input_label="<?php $App->Lang->echoT('label_takeaway'); ?>" value="Y" autocomplete="false"> 
                    <label class="label_radio required" for="takeaway_checkbox_Y">
                        <?php $App->Lang->echoT('label_takeaway'); ?> 
                        <br><br>
                    </label>

<?php if(1 == 2): ?>
                    <label class="required">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_password'); ?></span>
                        <input type="password" name="password" id="password" value="<?php echo $Form->getValue('password'); ?>" required data-input_compare="password_2" data-mode="<?php echo PASSWORD_MODE; ?>" data-minlength="<?php echo PASSWORD_MINLENGTH; ?>" data-maxlength="<?php echo PASSWORD_MAXLENGTH; ?>">
                    </label>
                    <label class="float_right required">
                        <span class="placeholder_fixed"><?php $App->Lang->echoT('label_password_2'); ?></span>
                        <input type="password" name="password_2" id="password_2" value="<?php echo $Form->getValue('password_2'); ?>" required data-mode="<?php echo PASSWORD_MODE; ?>" data-minlength="<?php echo PASSWORD_MINLENGTH; ?>" data-maxlength="<?php echo PASSWORD_MAXLENGTH; ?>">
                    </label>
<?php endif; ?>

               </fieldset>

<?php
/**
 * Campo per numero tavolo.
 * Visiblità gestita via js al cambio della scelta "asporto"
 * (v. user_data.php).
 * Valorizzo inoltro un campo per informare la pagina di elaborazione
 * sulla necessarietà del controllo o meno.
 */
if($showTableField === true) :
?>  
                <fieldset id="table_number_fieldset" style="margin-bottom: 2em;">
                    <span class="placeholder_fixed"><?php $App->Lang->echoT('label_table_number'); ?></span>
                    <input style="width: 4em;" type="text" name="table_number" id="table_number" value="<?php echo $Form->getValue('table_number'); ?>" aria-required="true" required>
                </fieldset>
<?php
endif;
?>
                <input name="table_number_check" id="table_number_check" type="hidden" value="<?php echo $showTableField ? 'Y' : 'N'; ?>">

<?php if(1 == 2): ?>
                <fieldset class="note_data  cart_options_user_data">
                    <span class="placeholder_fixed"><?php $App->Lang->echoT('label_note'); ?></span>
                    <div id="note_form">
                        <label class="w100">
                            <textarea name="note" id="note" data-input_label="<?php echo $App->Lang->returnT('label_note'); ?>" placeholder="<?php $App->Lang->echoT('placeholder_message'); ?>"><?php echo $Form->getValue('note'); ?></textarea>
                        </label>
                    </div>
                </fieldset>
<?php endif; ?>
                
                <p class="field w_100 campi_obbligatori">
                    <span class="field_note">* <?php $App->Lang->echoT('campi_obbligatori'); ?></span>
                </p>
