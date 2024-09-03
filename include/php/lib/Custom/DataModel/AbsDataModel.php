<?php
/**
 * @author Emanuele Fornasier - www.atrio.it
 * @version 2017/10/13
 */

namespace Custom\DataModel;

use \Utility,\Exception,\DateTime, \DataAccessPdo;

use Custom\Model as Model;

use \PDO;

/**
 * Crea entità dal db mappando colonne e proprietà, viene usato dai repository
 * quando si vuole ritornare un modello invece che i record grezzi da db
 * 
 */
abstract class AbsDataModel implements IDataModel
{


    /**
     * Dipendenze
     * @var LangManager $Lang
     */
    protected $Lang;


    /**
     * __construct
     * 
     * @param LangManager $LangManager
     */
    public function __construct($LangManager = null) {
        $this->Lang = $LangManager;
    }
    
 
    /**
     * 
     * Relazione tra campi del database (chiavi dell'array)
     * e proprietà (valori).
     * I valori di questo array devono coincidere con le proprietà
     * del modello che questo DataModel andrà a creare.
     * La mappatura viene usata per riempire l'oggetto 
     * con le proprietà del database.
     * 
     * Va integrata / sovrascritta nelle classi figlie.
     * 
     */
    public function definePropertyMap(){
        return array(
            "id" => "id"
            ,"date_insert" => "creationDate"
            ,"date_update" => "lastUpdate"
            ,'position' => "position"
            ,"public" => "public"
            ,"date_start" => "startDate"
            ,"date_end" => "endDate"
        );
    }


    /**
     * Istanzia la classe specifica specifica su cui 
     * opererà questo datamodel.
     * Va implementata dalle classi figlie.
     * 
     * @return IModel
     */
    protected abstract function modelInstance(); 
    

    /**
     * Metodo per avere una procedura base di creazione di un modello da un array di dati db.
     * Unisce le funzioni createModel e _mapFromDb, in modo che non serva
     * chiamare in sequenza le due funzioni pur mantenendo la possibilità di estenderle
     * nelle classi figlie.
     */
    public function createModel(array $rs) {
        
        $model = $this->modelInstance();

        return $this->_mapFromDb($model, $rs);

    }

    
    /**
     * Mappa campi tornati dal database con proprietà
     * del modello sulla base dell'array di mappatura,
     * perchè queste possano essere usate all'esterno.
     * 
     * Fornisce alle classi figlie un sistema di mappatura standard,
     * in queste basta integrare l'array _propertyMap attraverso definePropertyMap()
     * oppure aggiungere altre elaborazioni a quella di base definita qui.
     *  
     * Se la colonna proveniente dal db non è mappata su una proprietà,
     * viene creata una proprieta pubblica che ha lo stesso nome della colonna recuperata.
     * Se la colonna proveniente dal db è mappata su una proprietà ma questa non è definita nel modello,
     * viene creata una proprietà pubblica con il nome della proprietà
     * Se la colonna proveniente dal db è mappata su una proprietà esistente, questa prende il livello
     * di accesso definito.
     * 
     * @param object $model 
     * @param array $rs record grezzo da db
     */
    protected function _mapFromDb($model, $rs) {
        
        $propertyMap = $this->definePropertyMap() ?: array();

        // se la colonna è stata mappata puntualmente valorizzo la proprietà corrispondente
        foreach ($rs as $column => $value): 
            
            if (array_key_exists($column, $propertyMap)) :
               $model->setProperty($propertyMap[$column], $rs[$column]); 
            endif;
            
        endforeach; 
        
        return $model;
    }


    /**
     * Esegue operazione di mappatura in ingresso delle proprietà
     * del modello sulle colonne del database ciclando le proprietà
     * del modello.
     * Se l'array risultante va ripartito tra più tabelle, questa operazione va fatta nelle classi
     * figlie.
     * 
     * @param IModel $model
     * @return array
     */
    protected function _mapToDb($model) {

        foreach ($this->definePropertyMap() as $propertyName => $column): 

            // se la colonna ha un alias con prefisso {{nome_tabella.}}
            if (strstr($column, ".")) {
                $alias = explode(".", $column);
                $data[$alias[0]] = isset($data[$alias[0]]) ? $data[$alias[0]] : array();
                $data[$alias[0]][$alias[1]] = $model->getProperty($propertyName);
            } else {
                $data[$column] = $model->getProperty($propertyName);
            }
     
        endforeach;

        return $data;

    }



}

