<?php
/**
 * @author Emanuele Fornasier
 */
namespace Custom\Model;

/**
 * Interfaccia per modelli multilivello con
 * voci padre/figlio.
 * Restituisce valori della propria chiave primaria e di quella del padre
 * per permettere corretto annidamento nel metodo nestItems() del Datamodel in uso.
 */
interface Multilevel
{

    /**
     * Ritorna il valore di chiave primaria
     */
    public function getPrimaryKey();


    /**
     * Ritorna il valore di chiave primaria del genitore
     */
    public function getParentPrimaryKey();


    /**
     * Ritorna il nome della proprietà del modello 
     * in cui si vogliono annidare i figli
     */
    public function getPropertyNameForChildren();


}