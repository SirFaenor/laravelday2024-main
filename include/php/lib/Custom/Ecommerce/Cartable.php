<?php
/**
 * @author manu
 */

namespace Custom\Ecommerce;

/**
 * interfaccia per trasformare un entità in un oggetto valido per il carrello.
 * Va implementata da tutte le entità che si vogliono inserire in carrello
 * come voce acquistabile.
 */
interface Cartable
{

    /**
     * Trasforma le proprietà di un entità in un array per il carrello.
     * L'array risultante viene controllato da Cart al momento dell'aggiunta.
     */
    public function cartArray();


    /**
     * Controlla se la quantità richiesta è disponibile
     * 
     * @param $requestedQuantity quantità richiesta
     */
    public function isAvailable($requestedQuantity = 1):bool;

}