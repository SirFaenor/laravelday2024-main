<?php
/**
 * @author Emanuele Fornasier - www.atrio.it
 * @version 2016/09/15
 */

namespace Custom\DataModel;

/**
 * Interfaccia IDataModel
 */
interface IDataModel {

    /**
     * Creazione di un Model usabile da un array di dati provenienti dal db
     * Istanzia la classe specifica da usare come Model e ne riempie
     * le proprietà.
     */
    public function createModel(array $rs);

}