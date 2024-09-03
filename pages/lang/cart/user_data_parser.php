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
$xml = $App->User->getid_cat() == 2  ? 'cart_user_profile_b2b_fields' : 'cart_user_profile_fields';
$Form = $App->create('Form',array('form_name' => 'cart_user_data','path_to_xml_file' => ASSETS_PATH.'/xml/'.$xml.'.xml'));
$Form->prepareInputs();

$are_products_available = true;

/**
 * 2. CONTROLLO DISPONIBILITÀ
 * faccio un ultimo controllo sulle disponibilità per non avere valori negativi
 */
$Form->arResponse['msg'] = '';
foreach($arCartItems as $CartID => $Item):

    $arPrtInfo  = explode('_',$CartID);
    $id_prt     = $arPrtInfo[1];                    // id prodotto 

    $myData = $App->Da->customQuery("SELECT (availability - ".$Item['qta'].") AS d FROM prodotto WHERE id = ".$id_prt);
    if($myData[0]['d'] < 0):
        $are_products_available = false;
        $Form->arResponse['msg'] .= $App->Lang->returnT('cart_no_disp_quantity_error',array(
            'prod_name'     => $Item['title']
            ,'cart_detail'  => $App->Lang->returnL('cart_detail')
        ));
        break;
    endif;

endforeach;

if($are_products_available === false):
    $Form->errorRedirect();         // se ci sono errori reindirizza
endif;

/**
 * Validazione
 * NB AGGIUNGERE DOPO EVENTUALI ERRORI DI VALIDAZIONE EXTRA!
 */
$Form->validate();


/**
 * Controllo alcolici
 */
$checkAlcohol = !empty($_POST["alcohol"]) ? $_POST["alcohol"] : null;
if($App->Cart->hasAlcohol() && !$checkAlcohol) {
    $Form->arResponse["msg"] .= $App->Lang->returnT("error_age_18");
}


/**
 * Controllo conferma email
 */
if($Form->getValue("email_confirm") != $Form->getValue("email")) {
    $Form->arResponse["msg"] .= '<br>'.$App->Lang->returnT("error_form_mail_confirm");
}


/**
 * Controllo obbligatorietà tavolo, solo se 
 * - ordine NON è da asporto
 * - ho inserito pietanze nell'ordine (campo 'table_number_check' valorizzato su 'Y)
 */
if($Form->getValue("takeaway") == 'N' && $Form->getValue("table_number_check") == 'Y') {

    if (! $Form->getValue("table_number")) {
        $Form->arResponse["msg"] .= '<br><strong>'.$App->Lang->returnT("error_table_number").'</strong>';
    } elseif( (int) $Form->getValue("table_number") > 146 || (int) $Form->getValue("table_number") < 1 ) {
        $Form->arResponse["msg"] .= '<br><strong>'.$App->Lang->returnT("error_table_number_range").'</strong>';
    }
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
 * 6. SALVO I DATI IN CARTA
 */
foreach($arData as $k => $UserData):

    $App->User->{"set".$k}($UserData); 
    $App->Cart->setCustomData('ordine_cliente',$k,$UserData);

endforeach;


// imposto il nome del registrante sui messaggi sulla base della categoria
$userName = $App->User->getid_cat() == 1 ? $App->User->getnome().' '.$App->User->getcognome() : $App->User->getragione_sociale();
$nome     = $App->User->getnome();
$cognome  = $App->User->getcognome();
$email    = $App->User->getemail();

/* 8. VERIFICO CHE CI SIANO ANCORA I PRODOTTI NELL'ORDINE
***************************************************************************************************************/
if($App->Cart->countItems() <= 0):
    $Form->arResponse['result'] =   0;
    $Form->arResponse['msg'] =    $App->Lang->returnT('errore_order_timeout');

    /**
     * Se è ajax uso Form altrimenti lancio eccezione Http per avere responso completo
     */
    if ($Form->isAjax()):
        $Form->errorRedirect();
    endif;
    throw new \Box\Exceptions\GenericErrorException("Carrello vuoto!", $Form->arResponse['msg']);

endif;


/* 
 * 9.verifico che l'utente non esista già, nel qual caso lo collego
 * a quello che sta facendo l'ordine.
 * I dati inviati dal form andranno ad aggiornare quelli presenti in db
 */
$user = $App->Da->getSingleRecord([
    "table" => "cliente"
    ,"cond" => "WHERE email = :email"
    ,"params" => [
        "email" => $email
    ]
]);
if($user) {
    $App->User->setID($user["id"]);
}


/**
 * Salvo utente
 */
if (!$App->User->registerData()) :
    $Form->arResponse['result'] =   0;
    $Form->arResponse['msg'] = '<strong>'.$Lang->returnT('warning') .'</strong><br>'.
                                $Lang->replaceVars($Lang->returnT('update_cart_error'));
    $Form->errorRedirect();         // se ci sono errori reindirizza
endif;


/**
 * Notifica di registrazione utente non viene inviata
 */


/* 11. REGISTRAZIONE ORDINE
***************************************************************************************************************/
$App->Cart->setCustomData('ordine_pagamento','stato','In-Progress');
$save = $App->Cart->saveOrder();

if(!$save):    // se per qualche motivo non riesco a salvare invio l'errore

    $Form->arResponse['result'] = 0;
    $Form->arResponse['msg'] = '<strong>'.$App->Lang->returnT('warning') .'</strong><br>'.
                                $App->Lang->replaceVars($App->Lang->returnT('update_cart_error'));
    
    $Form->errorRedirect();         // se ci sono errori reindirizza

endif;

/**
 * messaggio risposta
 */
$Form->arResponse['result'] = 1;
$Form->arResponse['msg'] = $App->Lang->returnT('order_processing_msg');
$Form->unsetFormInputs();


// Resetto i tentativi di pagamento
$App->Cart->paymentTry = '';

$Form->redirectUrl = $App->Lang->returnL('cart_order_prepare');

$Form->successRedirect();

