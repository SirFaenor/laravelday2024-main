<?php
/**
 * @author Emanuele Fornasier - www.atrio.it
 * @version 2017/05/05
 */

namespace Custom\Repository;

use \Utility,\Exception,\DateTime, \DataAccessPdo;

use \PDO;


/**
 * Repository di accesso al db.
 * 
 * - accedendo al db e restituisce record grezzi o entità
 * - crea entità dal db mappando colonne e proprietà attrvaerso un datamapper, se presente (passato in costruzione)
 * 
 * Può essere esteso con metodi per il recupero specifico di dati
 * quando i metodi base non sono sufficienti.
 */
abstract class AbsRepository implements IRepository
{


    /**
     * Dipendenze
     * @var DataAccessPdo\Wrapper
     * @var LangManager $Lang
     */
    protected $Da;
    protected $Lang;
    
    /**
     * Istanza di pdo se la si vuole usare dierttamente
     * Ricavata da DataAccessPdo
     */
    protected $pdo;
    


    /**
     * @var string $table tabella principale
     * Va definita nelle classi figlie
     */
    protected $_table = null;


    /**
     * @repository
     * @var protected string $_sqlFilter
     * Token sql relativo alle condizioni del recupero (per lista delle voci)
     */
    protected $_sqlFilter = '';
    
    /**
     * @repository
     * @var protected string $_sqlOrder
     * Token sql relativo all'ordinamento (per lista delle voci)
     */
    protected $_sqlOrder = '';

    /**
     * @repository
     * @var protected string $_sqlPager
     * Token sql relativo alla paginazione (per lista delle voci)
     */
    protected $_sqlPager = '';
    

    /**
     * @var bool $_rawMode
     * De true, non esegue automaticamente
     * la creazione del modello in fase di recupero.
     * Viene impostata attraverso raw() / unraw() 
     */
    protected $_rawMode = false;


    /**
     * Mantiene in memoria ultimo resultset ottenuto
     * con metodo loadFromDb
     */
    protected $lastResult = null;


    /**
     * @var DataModel 
     * istanza interna di datamodel se si vogliono ritornare oggetti di dominio
     * invece che record grezzi
     */
    protected $DataModel = null;


    /**
     * modalità fecth predefinita (costante PDO)
     * NB: se viene cambiata, avrà ripercussioni
     * su tutti i metodi che utilizzano i resultset risultanti
     * dalle query
     * 
     */
    protected $defaultFetchMode = PDO::FETCH_ASSOC;


    /**
     * __construct
     * 
     * @param $Da DataAccess di cui si serve per eseguire query o per accedere a pdo
     */
    public function __construct($Da, $LangManager = null, $datamodel = null) {
        $this->Da = $Da;
        $this->pdo = $Da->getConnection();
        $this->Lang = $LangManager;
        $this->DataModel = $datamodel;
    }

    
  
    /**
     * Restituisce query per il recupero delle voci
     * Definisce alias delle colonne come {tabella}. {colonna}
     * per offrire un esempio standard per le classi figlie
     * che facilita la mappatura in ingresso e in uscita dal db 
     * (v. definePropertyMap())
     */
    protected function _listSql() {
        
        $sql = array();
        $columns = $this->pdo->getColumns($this->_table);
        foreach ($columns as $columnName): 
            $sql[] = "T.".$columnName." AS '".$this->_table.".".$columnName."'";
        endforeach;

        $sql = "
            SELECT
            ".implode(",", $sql)."            
            FROM ".$this->_table." AS T
            WHERE T.id IS NOT NULL
        ";

        return $sql;
    }


    /**
     * È il metodo che effettivamente accede al database eseguendo la query
     * di base con le varie parti extra aggiunte (filtro, ordinamento, paginazione).
     * Viene usato internamente con la parte di query custom preparata attraverso i metodi
     * order, filter, pager.
     * Può essere usata anche all'esterno nel caso si voglia interagire direttamente con il codice
     * sql, passando la stringa query extra da concatenare a quella di base.
     * 
     * @param string $sql query extra da concatenare a quella base
     * @param array $params array dei parametri per il bind Pdo
     */
    protected function loadFromDb($sql = '', $params = array()) { 

        $q = $this->pdo->prepare($this->_listSql().' '.$sql);

        $q->execute($params);
        $result = $q->rowCount() ? $q->fetchAll($this->defaultFetchMode) : null;
        if (!$result) {return null;}

        // elaborazione del singolo record
        foreach ($result as $key => $rs): 
            $result[$key] = $this->parseRecord($rs);
        endforeach;

        // memorizza in memoria
        $this->lastResult = $result;

        return $result;

    }  

    
    /**
     * Elabora resultset grezzo (array) proveniente da db.
     * Può essere esteso nelle classi figlie per caricare altre informazioni
     * necessarie all'esterno (o al DataModel in uso, se c'è).
     * Di base, se c'è un DataModel in uso lo utilizza per creare un Modello (l'uso del datamodel può
     * essere completamente evitato se preferibile)
     */
    protected function parseRecord($rs) {

        // se datamodel non presente, torna il record grezzo
        if ($this->DataModel == null) {return $rs;}

        // creazione Modello demandata alle classi figlie,
        $model = $this->DataModel->createModel($rs);

        return $model;

    }


    /**
     * Aggiunge una condizione di ordinamento lista.
     * Va chiamata prima di caricare una lista.
     * Richiede la presenza del datamodel interno
     * 
     * @param string  $propertyName nome della proprietà
     * @param string  $value valore della proprietà
     */
    public function order($propertyName = null, $value = null) {


        // azzeramento
        if ($propertyName === null) {$this->_sqlOrder = ''; return $this;}

        // verifico che la proprietà sia valida per il modello
        if (!in_array($propertyName, $this->DataModel->definePropertyMap())) {
            throw new \Exception("Proprietà sconosciuta: ".$propertyName);
        }
        $column = array_search($propertyName, $this->DataModel->definePropertyMap());

        // verifico il valore dell'ordinamento
        if (!in_array(strtoupper($value), ["ASC","DESC"] )) {
            throw new \Exception("Ordinamento non valido: ".$value);
        }

        // inizializzo la stringa (se già esiste devo concatenare) 
        $sql = ! strlen($this->_sqlOrder) ? " ORDER BY " :  $this->_sqlOrder." , ";

        // concateno condizione (non serve quote, valore e colonna sono già state validate)
        $sql .=  $column." ".$value;

        // memorizzo per eventuali chiamate successive
        $this->_sqlOrder = $sql;

        return $this;
        
    }


    /**
     * imposta ordinamento casuale
     */
    public function orderRand() {
        $this->_sqlOrder = ' ORDER BY RAND()';

        return $this;
    }

 
    /**
     * Memorizza sql per la paginazione della lista.
     * Va chiamata prima di caricare una lista.
     * 
     * @param pageNumber numero di pagina
     * @param integer $rowsPerPage numero di righe per pagina
     */
    public function pager($pageNumber = null, $rowsPerPage = null) {

        // azzeramento
        if ($pageNumber === null || $rowsPerPage == null) {$this->_sqlPager = ''; return;}
        
        if (!is_numeric($pageNumber) || !is_numeric($rowsPerPage)) {
            throw new Exception("Invalid offset.");
        }

        // calcolo offset di inizio
        $start = ($pageNumber-1) * $rowsPerPage;

        // concateno condizioni
        $this->_sqlPager = " LIMIT ".$start.", ".$rowsPerPage;

        return $this;

    }


    /**
     * Imposta esplicitamente riga di partenza e offset
     */
    public function limit($start, $offset) {
        
        if (!is_numeric($start) || !is_numeric($offset)) {
            throw new Exception("Invalid offset.");
        }
        
        // concateno condizioni
        $this->_sqlPager = " LIMIT ".$start.", ".$offset;

        return $this;
    }

    
    /**
     * Aggiunge una condizione di filtro per una lista 
     * creando una condizione di eguaglianza sulla proprietà per il valore passato.
     * Se questo metodo non è sufficiente a soddisfare tutti i i bisogni,
     * creare un metodo di recupero specifico.
     */
    public function filter($propertyName, $value) {
        
        // azzeramento
        if ($propertyName === null) {$this->_sqlFilter = ''; return;}

        // verifico che la proprietà sia valida per il modello
        if (!in_array($propertyName, $this->DataModel->definePropertyMap())) {
            throw new \Exception("Proprietà sconosciuta: ".$propertyName);
        }
        $column = array_search($propertyName,$this->DataModel->definePropertyMap());
        
        // inizializzo la stringa (se già esiste devo concatenare) 
        $sql = !strstr(" ".strtolower($this->_listSql())." ", "where" ) ? " WHERE " :  " AND ";

        // concateno condizione
        $sql .= $column." = ".$this->pdo->quote($value);

        // memorizzo per eventuali chiamate successive
        $this->_sqlFilter = $sql;

        return $this;

    }
    


    /**
     * Metodo generico per ritorno di una lista.
     * Aggiunge sql di filtro e ordinamento se sono state impostate
     */
    public function load($lgId = null) {
        
        $lgId = $lgId ?: $this->Lang->lgId;

        $sql = " AND TL.lang = ? ".$this->_sqlFilter.' '.$this->_sqlOrder.' '.$this->_sqlPager;

        return $this->loadFromDb($sql, array($lgId));
    
    }


    /**
     * Recupera record singolo attraverso id della tabella principale
     */
    public function loadById($id, $lgId = 0) {

        $lgId = $lgId ?: $this->Lang->lgId;

        $result = $this->loadFromDb("AND T.id = ? AND TL.lang = ? ", array($id, $lgId));

        return $result ? $result[0] : $result; 
        
    }


    /**
     * Recupera modello singolo filtrando su colonna url
     */
    public function loadByUrl($url, $lgId = 0) {

        $lgId = $lgId ?: $this->Lang->lgId;

        $result = $this->loadFromDb("AND TL.url_page = ?  AND TL.lang = ? ", array($url, $lgId));

        return $result ? $result[0] : null;
    }


    /**
     * Accesso al db fornendo una stringa sql extra 
     * da concatenare alla query di base.
     * La query extra deve essere resa sicura all'esterno se non parametrizzata.
     * 
     * @param string $sql query extra da concatenare a quella base
     * @param array $params array dei parametri per il bind Pdo
     */
    public function loadBySql($sql, $params = array()) {
        return $this->loadFromDb($sql, $params);
    }


    /**
     * 
     * Esegue operazione di salvataggio di un modello.
     * Di base, salva tabella principale e tabella lingua.
     * Può essere esteso per salvare altri dati
     * o tabelle relazionate.
     * 
     * @param $model entità
     */
    public function save(\Custom\Model\IModel $model) {

        $data = $this->DataModel->_mapToDb($model);


        // creazione
        if (!$model->getProperty('id')) :
            
            $data[$this->_table]["date_insert"] = '{{NOW()}}';

            // salvataggio tabella principale
            $this->pdo->createRecord(array(
                "table" => $this->_table
                ,"data" => $data[$this->_table]
            ));
            $model->setProperty('id',$this->pdo->insertId());
            // svuoto chiave
            unset($data[$this->_table]);

            // salvataggio tabella lingua
            if (isset($data[$this->_table."_lang"])): 
                $values = $data[$this->_table."_lang"];
                $this->pdo->createRecord(array(
                    "table" => $this->_table."_lang"
                    ,"data" => array_merge(
                        $values
                        ,array(
                            "id_item" => $model->getProperty('id')
                        )
                    )
                ));
                unset($data[$this->_table."_lang"]);
            endif;


            // salvataggio altre tabelle
            foreach ($data as $table => $values): 
                $this->pdo->createRecord(array(
                    "table" => $table
                    ,"data" => array_merge(
                        $values
                        ,array(
                            "id_item" => $model->getProperty('id')
                        )
                    )
                ));
            endforeach;

        //aggiornamento
        else :

            // salvataggio tabella principale
            $this->pdo->updateRecords(array(
                "table" => $this->_table
                ,"data" => $data[$this->_table]
                ,"cond" => "WHERE id = ? LIMIT 1"
                ,"params"=>array($model->getProperty('id'))
            ));
            // svuoto chiave
            unset($data[$this->_table]);

   
            // salvataggio tabelle correlate
            if (isset($data[$this->_table."_lang"])): 
                $values = $data[$this->_table."_lang"];
                $this->pdo->updateRecords(array(
                    "table" => $this->_table."_lang"
                    ,"data" => $values
                    ,"cond" => "WHERE id_item = ? AND lang = ?"
                    ,"params"=> array($model->getProperty('id'),$values['lang'])
               ));
                unset($data[$this->_table."_lang"]);
            endif;
            
            // salvataggio altre tabelle
            foreach ($data as $table => $values): 
                $this->pdo->createRecord(array(
                    "table" => $table
                    ,"data" => $values
                    ,"cond" => "WHERE id_item = ?"
                    ,"params"=> array($model->getProperty('id'))
                ));
            endforeach;

        endif;

        return $model->getProperty('id');

    }


    /**
     * Annida gli elementi in base al genitore inserendo i figli
     * nella proprietà "Children" di quest'ultimo.
     * Gli elementi della lista devono implementare l'interfaccia
     * Model\Multilevel per ritornare valore della propria chiave primaria e di quella del padre.
     */
    public function nestItems($listTemp, $currentList = array()) {
        
        foreach ($listTemp as $key => $Item): 
            
            if (! $Item instanceof \Custom\Model\Multilevel) {
                throw new Exception("La voce non implementa l'interfaccia per Multilevel per l'annidamento.");
            }

            // recupero valori (metodi dell'interfaccia)
            $primaryKey = $Item->getPrimaryKey();
            $parentPrimaryKey = $Item->getParentPrimaryKey();
            $propertyNameForChildren = $Item->getPropertyNameForChildren();

            // se non è di primo livello e genitore non esiste ancora nella lista definitiva
            if ($parentPrimaryKey && !isset($currentList[$parentPrimaryKey])) {continue;}

            // se non è di primo livello e genitore esiste nella lista definitiva,
            // lo memorizzo tra i figli del genitore e lo elimino dalla lista temporanea
            if ($parentPrimaryKey && isset($currentList[$parentPrimaryKey])) {
                $currentList[$parentPrimaryKey]->{$propertyNameForChildren}[] = $Item;
                unset($listTemp[$key]);
                continue;
            }

            // se arrivo qui allora è l'elemento è di primo livello, quindi lo memorizzo nella lista definitiva
            $Item->{$propertyNameForChildren} = array();
            $currentList[$primaryKey] = $Item;
            unset($listTemp[$key]);
        
        endforeach; // ciclo lista temporanea

        // se ho ancora elementi da elaborare, continuo passando le liste allo stato attuale
        if (count($listTemp)) {
            return $this->nestItems($listTemp, $currentList);
        }

        return array_values($currentList);
    }


    /**
     * Metodo di utilità pre creare una lista di placeholder ad uso di pdo
     * 
     * @param $count numero desiderato (1-xxx)
     * @param $baseName nome base a cui sarà concatenato l'indice numerico
     */
    public function createPlaceholdersList($count, $baseName = 'placeholder') {
        
        $list = array();

        for($i=1;$i<=$count;$i++) :
            array_push($list, ':'.$baseName.$i);
        endfor;

        return implode(",", $list);
    }
    

    /**
     * Metodo di utilità per creare un array di parametri con nome.
     * I parametri saranno le chiavi dell'array finale, con valori vuoti.
     * 
     * @param $values valori dei parametri
     * @param $baseName nome base del parametro
     */
    public function createNamedParams($values, $baseName = 'placeholder') {
        
        $list = array();

        foreach ($values as $index => $value): 
            $list[':'.$baseName.$index] = $value;
        endforeach;

        return $list;
    }


}

