<?php 
$App->Lang->loadTrads("carrello,errors");
$App->BrowserQueue->noSave();

$items = array(); // gli elementi che andranndo nel carrello

$is_ajax        = array_key_exists('is_ajax',$_GET) && $_GET['is_ajax'] == 1 ? true : false;    // sulla base della trasmissione imposto la risposta
$id_item        = array_key_exists('id_item',$_GET) ? $_GET['id_item'] : null;
$qta            = array_key_exists('qta',$_GET) ? $_GET['qta'] : 0;
$removeOnZero   = array_key_exists('removeOnZero',$_GET) ? $_GET['removeOnZero'] : false;       // rimzione del prodotto se la sua quantità è zero
$add            = array_key_exists('add',$_GET) && $_GET['add'] == 1 ? true : false;            // se prodotto già presente, somma quantità invece che sovrascrivere


/**
 * Check e redirect disponobilità carrello
 */
redirectStoreUnavailable();


/**
 * Risposta
 */
$response = [
    "result" => 0
    ,"msg" => $App->Lang->returnT('update_cart_error')
    ,"itemscounter" => count($arCartItems)
    ,"needsreload"  => false 
];

$has_error = false; # visto che posso aggiornare più prodotti contemporaneamente, gestisco errori e conferme in un messaggio finale


/**
 * Ciclo elementi, recupero voce originaria
 * e la memorizzo in carrello
 */
$n_items = 0;
$totQnt = 0;
try {

    // popolo l'array da inserire in carrello a seconda del tipo di dato che ho
    if(is_array($qta)):

        $items = $qta;

    else:
        if (!$id_item) :
            throw new \InvalidArgumentException($App->Lang->returnT("unvalid_item_error"));
        endif;

        $items[$id_item] = $qta;
    endif;

    if (!$items):
        throw new \Box\Exceptions\BadRequestException;
    endif;


    /**
     * ciclo elementi
     */
    $final_msg = '';

    foreach($items as $id_item => $qnt):
        $thisRemoveOnZero = 
            is_array($removeOnZero) 
            ? array_key_exists($id_item,$removeOnZero) && (bool)$removeOnZero[$id_item] === true
            : (bool)$removeOnZero;


        // se non arriva quantità, o se quantità è 0 e non devo rimuovere
        if (($qnt < 0 || ($qnt == 0 && $thisRemoveOnZero == false)) && array_key_exists($id_item,$arCartItems)):
            $has_error = true;
            $final_msg .= '<strong>'.$arCartItems[$id_item]['title'].'</strong>: '.$App->Lang->returnT("quantity_error").'<br>'.PHP_EOL;
        endif;

        $CartItem = $App->CartItemFactory->createFromKey($id_item, $App->Lang->lgId);
        if($CartItem):

            $is_item_in_cart = $App->Cart->isItemInCart($id_item);

            // se quantità è 0, rimuovo
            if ($qnt == 0 && $removeOnZero == true && $is_item_in_cart):
                $App->Cart->removeItem($id_item);
                $response['itemscounter']--;
            endif;
            
            if(!$is_item_in_cart && $qnt > 0):
                $response['itemscounter']++;
            endif;

            // controllo disponibilita (se devo aggiungere, sommo alla quantità già presente in carrello)
            $cartQnt = $is_item_in_cart ? $arCartItems[$id_item]['qta'] : 0;
            $qntCheck = $add ? $cartQnt + $qnt : $qnt;
            if ($qntCheck && !$CartItem->isAvailable($qntCheck)):
                $has_error = true;
                $final_msg .= $App->Lang->returnT("unavailable_quantity_error", array("item" => '<strong>'.$CartItem->title.'</strong>')).PHP_EOL;
            endif;

            // aggiungo
            if ($has_error === false && $qnt && $App->Cart->addItem($id_item,$CartItem,$qnt,$add)):
                $n_items++;
                $totQnt += $qnt;
            endif;

            // ho eseguito un operazione, risposta ok

                
        endif;
    endforeach;

    if($has_error === true):
        throw new \InvalidArgumentException($final_msg);
    else:
        $response["result"] = 1;
    endif;

} catch (\InvalidArgumentException $e) {

    $response['msg'] = $e->getMessage();
 
} catch (Exception $e) {

    $response['msg'] = '<strong>'.$App->Lang->returnT('warning').'</strong><br> '.$App->Lang->returnT('cart_update_error').'<br>';
    $App->ErrorLogger->handleException($e);

}


/**
 * Se sono qui, non ho errori, risponso con "Carrello aggiornato"
 * (posso aver aggiunto ma anche rimosso articoli)
 */
$response["msg"] = $response["result"] == 1 && $has_error === false ? $App->Lang->returnT('cart_updated') : $response["msg"];


// se non provengo già dal carrello aggiungo link al carrello
if (
    $response["result"]
    &&
    (
        empty($_SERVER["HTTP_REFERER"]) || $App->UrlUtility->requestUri($_SERVER["HTTP_REFERER"]) !=  $App->Lang->returnL("cart_detail")
    )
):
    $response["msg"] .= '';
endif;


/**
 * Risposta ajax
 */
if(IS_AJAX_REQUEST === true):
    echo json_encode($response);
else:
    $App->redirect($App->Lang->returnL('cart_detail'));
endif;

exit;