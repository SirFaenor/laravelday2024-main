<?php
namespace DataAccessPdo;

use PDO,Exception;

/**
 * Wrapper pdo per accesso al db.
 * Utilizza pdo con prepared statements per accesso al database.
 * Accetta in costruzione un'istanza di pdo preconfigurata all'esterno.
 * 
 * @author Emanuele Fornasier
 * @version 2017/06/20, Emanuele Fornasier
 */
class Wrapper {
    

    /**
     * pdo instance
     */
    protected $pdo;
    
    /**
     * nome del database
     */
    protected $dbName;
    

    /**
     * registro interno delle tabelle e delle relative colonne
     */
    protected $tablesRegistry = array();


    /**
     * array delle query base "modello"
     */
    protected $arModels = array();


    /**
     * log interno
     */
    protected $lastQueryDebug;
    protected $lastStatement;
    protected $lastResult;

    /**
     * @param valid pdo instance, will be stored internally
     * @param $dbName nome del database, serve per controllo su tabelle
     */
    public function __construct(PDO $pdo, $dbName) {
        $this->pdo = $pdo;
        $this->dbName = $dbName;
    }


    /**
     * Ritorna istanza interna di PDO
     */
    public function getConnection() {
        return $this->pdo;
    }


    /**
     * Ultimo step di esecuzione query
     * @param $statement prepared statement
     */
    protected function query(& $stmt) {

        // log della query
        $this->log($stmt);

        // eseguo query
        $stmt->execute();

        // memorizzo ultima query in caso ci debba accedere successivamente
        $this->lastStatement = $stmt;

    }

    /**
     * Esegue una query select
     * 
     * @param $options array di parametri
     *  - table => nome della tabella
     *  - columns => array delle colonne desiderate, se non passato recupera tutto
     *  - cond => condizione
     *  - params => elenco dei parametri di cui fare il binding
     * 
     * @param $format
     *        - se 0/false non restituisce tutto il resultset ma ritorna la 
     *        statement per usarla a piacere all'esterno
     *        - se viene passata una costante pdo la uso per il fetch
     */
    public function select($options, $fetchMode = PDO::FETCH_ASSOC) {


        //
        // validazione
        //
        if (!isset($options["table"]) && !isset($options["model"])) {
            throw new Exception("You must specify table or model ".print_r($options,1));
        }

        if (isset($options["model"]) && !array_key_exists($options["model"], $this->arModels)) {
            throw new Exception("Invalid model ".print_r($options,1));
        }
        
        if (isset($options["table"]) && !$this->tableExists($options["table"])) {
            throw new Exception("Invalid table ".print_r($options,1));
        }


        $this->lastResult = null;
        
        //
        // costruisco la query o la recupero dal modello
        //
        if (isset($options["model"])) :
            $sql = $this->arModels[$options["model"]];
        else :
            $sql = 'SELECT ';

            $columns = isset($options['columns']) ? implode(",", $options["columns"]) : '*';
            $sql .= $columns;

            $sql .= " FROM ".$options["table"];
        endif;
        
        // concateno condizione
        $sql .= isset($options["cond"]) && strlen($options["cond"]) ? ' '.ltrim($options["cond"]," ") : '';

        // prepare
        $q = $this->pdo->prepare($sql);

        // bind parametri
        if (isset($options["params"])) :
            $this->bind($q, $options["params"]);
        endif;

        // esegue query
        $this->query($q);


        if (!$q->rowCount()) {return false;}

        // se non voglio fetch, restuisco statement all'esterno per farne ciò che voglio...
        if ($fetchMode == false) {
            return $q;
        }

        // memorizzo ultimo risultato 
        $this->lastResult = $q->fetchAll($fetchMode);

        // ..altrimenti ritorno fetch completo dei risultati
        return $this->lastResult;

    }


    /**
     * Get columns' names from a table in order to validate input data
     * Calls retrieveTableColumns().
     * Stores columns list in a private var to avoid multiple queries.
     */
    public function getColumns($tableName) {
        return $this->retrieveTableColumns($tableName);
    }


    public function columnExists($column, $tableName) {
        return $this->getColumns($tableName) && in_array($column, $this->getColumns($tableName));
    }


    /**
     * Recupera elenco delle colonne per una data tabella
     * Viene ora usata anche per validare l'esistenza o meno di una tabella.
     * Memorizza internamente il risultato se è già stata interrogata per la stessa
     * tabella in modo da non ripetere esecuzione della query di controllo.
     */
    protected function retrieveTableColumns($tableName) {
        
        // se tabella non esiste
        $c = $this->tableExists($tableName);
        if (!$c) {
            return false;
        }        

        // se ho già controllato in precedenza  
        if (isset($this->tablesRegistry[$tableName]["columns"])) {
            return $this->tablesRegistry[$tableName]["columns"];
        }


        // recupero colonne
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :table";
        $q = $this->pdo->prepare($sql);
        $q->execute(['table' => $tableName, "database" => $this->dbName]);
        if (!$q->rowCount()) {return false;}
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) :
            $this->tablesRegistry[$tableName]["columns"][] = $row["COLUMN_NAME"];
        endwhile;
        
        return $this->tablesRegistry[$tableName]["columns"];

    }


    /**
     * Checks if a table exists.
     * 
     * @param string $tableName
     * @return bool
     */
    public function tableExists($tableName) {
            
        // if previously checked
        if (array_key_exists($tableName, $this->tablesRegistry)) :
            return $this->tablesRegistry[$tableName]["exist"];
        endif;

        // check now
        $sql = "SELECT COUNT(*) as table_exists FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :table";
        $q = $this->pdo->prepare($sql);
        $q->execute(['table' => $tableName, "database" => $this->dbName]);
        $exists = $q->fetchColumn(); 

        // store result
        $this->tablesRegistry[$tableName] = array(
            "exist" => $exists ? true : false
        );

        return $this->tablesRegistry[$tableName]["exist"];
         
    }   
    

    /**
     * Valida le colonne in ingresso confrontandole con quelle
     * della tabella di destinazione
     * 
     * @param array options
     */
    protected function validateData($options = array()) {
        
        if (empty($options["table"]) || !$this->tableExists($options["table"])) {
            throw new Exception("Missing or invalid table: ".print_r($options,1));
        }
        
        if (empty($options["data"]) || !is_array($options["data"]) || count($options["data"]) == 0) {
            throw new Exception("Missing data: ".print_r($options,1));
        }

        $validColumns = $this->getColumns($options["table"]);
      
        $arValues = array();
        foreach ($options["data"] as $k => $v) {
            if (in_array($k, $validColumns)) :
                $arValues[$k] = $v;
            endif;      
        }
        
        return $arValues;
    
    }
    

    /**
     * Aggiorna un record.
     * Ritorna il numero di righe interessate
     * 
     * @param $options array [table,data,cond]
     * @todo costruisci array unico per valori ($options["data"]) e parametri ($options["param"])
     * in modo da usare il metodo bind() della classe su questo array risultante
     */
    public function update($options) {

        //
        // valido i dati
        //
        $options["data"] = $this->validateData($options);
    

        //
        //  costruisco la query
        //  
        
        // base
        $sql = 'UPDATE '.$options["table"]. ' SET ';
        
        // colonne
        $data = $options['data'];
        foreach ($data as $column => $value): 
            
            // se il valore è circondato da '{{}}', assumo sia una funzione sql 
            // e ne estraggo il codice
            if (preg_match("(^\{\{.*\}\}$)", $value)) :
                $value = preg_replace("(^\{\{(.*)\}\}$)", '$1', $value);

                // elimino la colonna per non interferire col bind dei parametri
                unset($data[$column]);

                $sql .= $column.' = '.$value.',';
            else :
                $sql .= $column.' = :'.$column.',';
            endif;

        endforeach;
        $sql = rtrim($sql,",");

        // condizione
        $sql .= isset($options["cond"]) && strlen($options["cond"]) ? ' '.ltrim($options["cond"]," ") : '';

        // prepare
        $q = $this->pdo->prepare($sql);

        // bind valori colonne (questi sono nominali)
        // lo faccio manualmente per poi usare bind sui parametri restanti
        foreach ($data as $col => $value): 

            $qkey = ':'.$col;
            
            // se $value è un array, assumo di avere anche il tipo di dato
            if (is_array($value) && count($value) > 1) :
                $q->bindValue($qkey, $value[0], $value[1]);
            else :
                $q->bindValue($qkey, $value);
            endif;

        endforeach;

        // bind parametri
        if (isset($options["params"])) :
            $this->bind($q, $options["params"]);
        endif;
       
        
        // eseguo
        $this->query($q);

        return $q->rowCount();

    }

    /**
     * Inserisce un record.
     * Ritorna id del record inserito.
     * 
     * @param $options array [table,data,cond]
     */
    public function insert($options) {
        
        //
        // valido i dati
        //
        $options["data"] = $this->validateData($options);


        //
        //  costruisco la query
        //  
        
        // base
        $sql = 'INSERT INTO '.$options["table"]. ' (';
        
        // colonne e dati
        $sqlA = '';
        $sqlB = '';
        $data = $options['data'];
        foreach ($data as $column => $value): 
            
            $sqlA .= $column.',';
    
            // se il valore è circondato da '{{}}', assumo sia una funzione sql 
            // e ne estraggo il codice
            if (preg_match("(^\{\{.*\}\}$)", $value)) :
                $value = preg_replace("(^\{\{(.*)\}\}$)", '$1', $value);
                $sqlB .= $value.',';

                // elimino la colonna per non interferire col bind dei parametri
                unset($data[$column]);
            else :
                $sqlB .= ':'.$column.',';
            endif;
        endforeach;
        $sql .= rtrim($sqlA,',').') VALUES ('.rtrim($sqlB,',').')';

        // prepare
        $q = $this->pdo->prepare($sql);

        // bind valori
        $this->bind($q, $data);


        // eseguo
        $this->query($q);

        return $this->pdo->lastInsertId();

    }

    /**
     * cancella un record
     * Ritorna il numero di righe cancellate
     */
    public function delete($options) {
        
        if (!$this->tableExists($options["table"])) {
            throw new Exception("Invalid table: ".$options["table"]);
        }
        
        //
        //  costruisco la query
        //  
        
        // base
        $sql = 'DELETE  FROM '.$options["table"].' '.$options["cond"];

        // prepare
        $q = $this->pdo->prepare($sql);
        
        // bind parametri
        if (isset($options["params"])) :
            $this->bind($q, $options["params"]);
        endif;
        
        // eseguo
        $this->query($q);

        return $q->rowCount();


    }


    /**
     * Esegue una query preparata come stringa all'esterno.
     * 
     * @param $sql stringa query
     * @param $params array di parametri per il binding di pdo
     * @param $fetchMode modalità fetch di pdo(valida solo per query select, vedi descrizione del metodo select())
     */
    public function customQuery($sql, $params = array(), $fetchMode = PDO::FETCH_ASSOC) {

        // tiplogia della query per decidere al volo che risultato tornare
        // (in linea con i vari metodi specifici)
        $query = trim(preg_replace('/\s+/', ' ', $sql));
        $querySplit = preg_split("/ / ", $query);
        $queryType = strtolower(trim($querySplit[0]));

        //
        //  costruisco la query
        //  

        // pulizia
        $sql =  preg_replace( "/\r|\n|\t/", " ", $sql);
        $sql =  preg_replace( "/\s+/", " ", $sql);
    
        // prepare
        $q = $this->pdo->prepare($sql);
        
        // bind parametri
        $this->bind($q, $params);
        
        // eseguo
        $this->query($q);

        // ritorno
        switch ($queryType) {
            case 'select':

                // se non voglio fetch ritorno lo statement
                if (!$fetchMode) {return $q;}
                
                if (!$q->rowCount()) {return null;}

                // memorizzo ultimo risultato 
                $this->lastResult = $q->fetchAll($fetchMode);

                return $this->lastResult;
                
                break;
            case 'update':

                return $q->rowCount();

                break;
            case 'insert':

                return $this->pdo->lastInsertId();

                break;
            case 'delete':
                
                return $q->rowCount();

                break;
            
            default:
                    
                // ritorna ultima statement
                return $q;    

                break;
        }

    }
    

    /**
     * Conta i record in una tabella
     * 
     * @param array $options (v. select())
     * @return integer count
    */
    public function countRecords(array $options) {
        
        if (!empty($options["model"])) :

            $result = $this->select($options);
            
            return count($result);

        endif;

        $options["column"] = !empty($options["column"]) ? $options["column"] : 'id';
        $options["cond"] = !empty($options["cond"]) ? $options["cond"] : '';

        $sql = "SELECT COUNT(".$options["column"].") AS total FROM ".$options['table']." ".$options['cond'];

        // recupero parametri
        $bindParams = isset($options["params"]) ? $options["params"] : array();

        $c = $this->customQuery($sql, $bindParams);
        
        return $c[0]['total'];

    }

    /**
     * Esegue binding dei parametri sulla statement in ingresso
     */
    protected function bind(& $q, $values) {

        if (!$values || !is_array($values)) {return;}

        // verifico se l'array è associativo
        $isAssoc = count(array_filter(array_keys($values), 'is_string')) > 0;

        // se non è associativo mi assicuro che le chiavipartano da "1" come vuole pdo
        if (key($values) === 0) :
            $values = array_combine(range(1, count($values)), array_values($values));
        endif;

        // ciclo per binding
        foreach ($values as $key => $thisValue): 

            $qkey = $isAssoc ? ':'.$key : $key;
                
            // se $thisValue è un array, assumo di avere anche il tipo di dato
            if (is_array($thisValue) && count($thisValue) > 1) :
                $q->bindValue($qkey, $thisValue[0], $thisValue[1]);
            else :
                $q->bindValue($qkey, $thisValue);
            endif;

        endforeach;

        return $values;
    }


    /**
     * Logga l'ultima query in esecuzione chiamando
     * metodo nativo di PDO
     */
    protected function log($q) {
        ob_start();

        $q->debugDumpParams();
        $this->lastQueryDebug =  ob_get_clean();
    }


    /**
     * Debug, ritorna l'ultima query loggata
     * con il metodo log()
     */
    public function printQuery($return = false) {
        
        if ($return) {return $this->lastQueryDebug;}
        
        echo $this->lastQueryDebug;
    
    }


    /**
     * retrocompatibilità con vecchi metodi
     */
    public function getRecords($params) {
        return $this->select($params);
    }
    public function getSingleRecord($params) {
        $result = $this->select($params);
        return $result ? $result[0] : null;
    }
    public function createRecord($params) {
        return $this->insert($params);
    }
    public function updateRecords($params) {
        return $this->update($params);
    }
    public function deleteRecords($params) {
        return $this->delete($params);
    }
    public function insertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Ritorna ultimo resultset in accordo con vecchio 
     * metodo. Non supporta più il parametro $format ARRAY/OBJECT
     * in quanto l'ultimo resultset è stato già memorizzato come
     * richiesto nel metodo che ha generato la query select.
     */
    public function fetchResults($format = null) {

        trigger_error("Calling 'FetchResults' is no longer required.", E_USER_WARNING);

        return $this->lastResult;

    }


    /**
     * Riceve un array di query "modello" e le memorizza internamente.
     */
    public function registerModels(array $sqlArray) {
        
        foreach($sqlArray as $ModelName => $Query):
        
            $this->arModels[$ModelName] = $Query;
        
        endforeach;

        return true;
    }



}