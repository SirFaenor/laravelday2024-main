<?php
$App->BrowserQueue->noSave();
$App->Lang->loadTrads("cart_global,cart_detail,form,user,cart_user_data,errors");


/**
 * Check e redirect disponibilità carrello
 */
redirectStoreUnavailable();


/**
 * 1. ISTANZIO IL FORM
 */
$xml = __DIR__.'/../../../include/xml/cart_user_profile_fields.xml';
$Form = $App->create('Form',array('form_name' => 'cart_user_data','path_to_xml_file' => $xml));
$Form->prepareInputs();


/**
 * Validazione
 * NB AGGIUNGERE DOPO EVENTUALI ERRORI DI VALIDAZIONE EXTRA!
 */
$Form->validate();

/**
 * Controllo conferma email
 */
if($Form->getValue("email_confirm") != $Form->getValue("email")) {
    $Form->arResponse["msg"] .= '<br>'.$App->Lang->returnT("error_form_mail_confirm");
}


/**
 * Se ci sono errori, reindirizza
 */
if($Form->hasError()):
    $Form->errorRedirect();
endif;


/**
 * 5. RACCOLGO I DATI
 */
$arData                 = $Form->arSqlInputs;
$arData['date_insert']  = $arData['date_start'] = date('Y-m-d H:i:s');
$arData['lang']      = $App->Lang->lgId;
$arData['lingua']    = $App->Lang->lgSuff;
$arData['marketing_checkbox'] =array_key_exists('marketing_checkbox', $arData) && $arData['marketing_checkbox'] == 'Y' ? 'Y' : 'N';


/**
 * Salvo l'ordine tramite api
 * // TODO
 */
$save = true;

if(!$save):    // se per qualche motivo non riesco a salvare invio l'errore

    $Form->arResponse['result'] = 0;
    $Form->arResponse['msg'] = '<strong>'.$App->Lang->returnT('warning') .'</strong><br>'.
                                $App->Lang->replaceVars($App->Lang->returnT('update_cart_error'));
    $Form->errorRedirect();         // se ci sono errori reindirizza

endif;


/**
 * messaggio risposta
 * La risposta api contiene il link di pagamento a cui reindirizzare
 */
$Form->arResponse['result'] = 1;
$Form->arResponse['msg'] = $App->Lang->returnT('order_processing_msg');
$Form->arResponse['redirect'] = '/payment-hub';
$Form->unsetFormInputs();


// Resetto i tentativi di pagamento
$App->Cart->paymentTry = '';

$Form->successRedirect();

