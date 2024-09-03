<?php
/**
 * @author Emanuele Fornasier - www.atrio.it
 * @version 2017/10/13
 */

namespace Custom\Repository;

use Custom\Model as Model;

/**
 * Interfaccia IRepository
 */
interface IRepository {

    /**
     * recupero voce singola da id
     */
    function loadById($id);
    
    /**
     * salvataggio
     */
    function save(\Custom\Model\IModel $model);
    
    /**
     * recupero lista
     */
    function load();
    
    /**
     * ordinamento per lista
     */
    function order();
    
    /**
     * filtro per lista
     */
    function filter($propertyName, $value);
    
    /**
     * paginazione per lista
     */
    function pager();


}