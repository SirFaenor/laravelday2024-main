<?php
/**
 * @author Emanuele Fornasier - www.atrio.it
 * @version 2016/09/15
 */

namespace Custom\Model;

use Exception;

/**
 * Entità dati / operativa base
 * Contiene logica di dati e operazioni per un entità.
 */
class StdModel implements IModel
{


    /**
     * Elenco delle proprietà base.
     * Devono corrispondere alla mappatura nel 
     * DataModel corrispondente per esser correttamente valorizzate.
     */
    protected $id;
    protected $creationDate;
    protected $lastUpdate;
    protected $position;
    protected $startDate;
    protected $endDate;
    protected $public;


    public function __construct() {}


     /**
     * Setter e getter automatici per comodità.
     * es setTitle()
     */
    public function __call($functionName, $arArguments) {

        $method = substr($functionName, 0, 3);
        $property = substr($functionName, 3);

        switch ($method) {
            case "set":
                return $this->setProperty($property, $arArguments[0]);
                break;
            case "get":
                return $this->getProperty($property);  
                break;
            default : {
                throw new Exception("Metodo inesistente: ".$functionName);
            } 
        };

        return(false);   
    }


    public function isEdited() {
        return $this->_isEditedFlag;
    }


    /**
     * Imposta valore di una proprietà interna,
     * Ogni proprietà che si desidera valorizzare deve essere esplicitata
     * nei modelli figli perchè venga considerata.
     * 
     */
    public function setProperty($propertyName, $value) {

        if(!property_exists($this,$propertyName)):
            trigger_error('Proprietà <code>'.$propertyName.'</code> non esistente nella classe '.get_class($this),E_USER_NOTICE);
        endif;

        $this->$propertyName = $value;
    }
    
    
    /**
     * Recupera valore di una proprietà interna
     * NB: accedendo direttamente ad una proprietà protetta 
     * viene chiamato __get() che reindirizza qui
     */
    public function getProperty($propertyName) {

        return $this->$propertyName;

    }

    /**
     * Ritorna lista di tutte le proprietà
     */
    public function getAllProperties() {
        return array();
    }
    

    /**
     * __get
     * Per avere accesso più immediato ad una proprietà
     */
    public function __get($propertyName) {
        return $this->getProperty($propertyName);
    }
    

    /**
     * Previene impostazione dell'id dall'esterno su un modello
     * vuoto.
     * Un modello con un id deve sempre provenire dal db.
     */
    public function setId() {
        throw new \Exception("Vietato impostare id dall'esterno.");
    }


}