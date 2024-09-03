<?php
ini_set("display_errors",1);
ini_set("display_startup_errors",1);
ini_set("error_reporting", E_ALL);

require_once(__DIR__.'/../src/Wrapper.php');

$mysql = array(
    "host"         =>      "localhost"
    ,"user"         =>      "test"
    ,"password"     =>      "123456"
    ,"database"     =>      "test"
    ,"charset"      =>      "utf8"
);

$pdo = new PDO('mysql:host='.$mysql['host'].';dbname='.$mysql['database'].';charset='.$mysql['charset'], $mysql['user'], $mysql['password'], array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)); 


$Da = new DataAccessPdo\Wrapper($pdo, $mysql["database"]);


$Da->update([
    "table" => "pippow"
]);

exit("ik");
/**
 * SELECT
 * Query con parametri nominati
 * Se il valore del parametro è un array, il secondo indice esplicita il tipo di dato (come da costanti PDO)
 * L'ordine dei parametri non deve rispecchiare necessariamente quello dei placeholder.
 */
/*$result1 = $Da->select(array(
    "table" => "example"
    ,"cond" => "WHERE id = :id AND position = :position"
    ,"params" => [
        "position" => 2
        ,"id" => [1, PDO::PARAM_INT]
    ]
));*/
//print_r($result1);


/**
 * SELECT
 * Query con parametri posizionali
 * L'array passato deve utilizzare indici basati su 1, come vuole PDO, o essere un array
 * sequenziale in base all'ordine dei placeholder
 */
$result2 = $Da->select(array(
    "table" => "example"
    ,"cond" => "WHERE id = ? AND position = ?"
    ,"params" => [
        1 => 1
        ,2 => [8888, PDO::PARAM_INT]
    ]
));
print_r($result2);

// come sopra, ma con array zero based senza esplicitare le chiavi
$result3 = $Da->select(array(
    "table" => "example"
    ,"cond" => "WHERE id = ? AND position = ?"
    ,"params" => [
        1,
        [8888, PDO::PARAM_INT]
    ]
));


/**
 * SELECT
 * Retrocompatibilità
 */
$result4 = $Da->getRecords(array(
    "table" => "example"
    ,"cond" => "WHERE id = ? AND position = ?"
    ,"params" => [
        1 => 1
        ,2 => [2, PDO::PARAM_INT]
    ]
));
//print_r($result4);

/**
 * UTILITY
 * Recupero colonne
 */
$columns = $Da->getColumns("example");
//print_r($columns);


/**
 * UTILITY
 * Esistenza tabella
 */
$exists = $Da->tableExists("example2");
//var_dump($exists);



/**
 * UPDATE
 * Poichè le chiavi dell'array data vengono usate come nomi delle colonne sottoforma di parametri nominali,
 * in questo caso nella condizione DEVO usare sempre e solo parametri nominali
 */
$c = $Da->update(array(
    "table" => "example"
    ,"data" => [
        "position" => 4444
        ,"public" => 0
        ,"date_start" => '{{NOW()}}'
    ]
    ,"cond" => "WHERE id = :id"
    ,"params" => ["id" => 3]
));
print_r($c);


/**
 * INSERT
 */
$id = $Da->insert(array(
    "table" => "example"
    ,"data" => [
        "position" => 97
        ,"public" => 0
        ,"date_insert" => '{{NOW()}}'
    ]
));


/**
 * DELETE
 * come select, è possibile usare sia parametri nominali che posizionali
 * basta che siano corretti come richiede pdo
 */
$c = $Da->delete(array(
    "table" => "example"
    ,"cond" => "WHERE id = ?"
    ,"params" => array(
        1 => $id
    )
));
var_dump($c);

$c = $Da->delete(array(
    "table" => "example"
    ,"cond" => "WHERE id > :id"
    ,"params" => array(
        "id" => 10
    )
));
var_dump($c);


/**
 *  QUERY CUSTOM SELECT
 * nessun parametro binding, $fetchMode == false fa ritornare direttamente all'esterno la statement
 */
$c = $Da->customQuery("SELECT * FROM example", null, false);
while ($rs = $c->fetch(PDO::FETCH_ASSOC)) :
    // print_r($rs);
endwhile;


/**
 *  QUERY CUSTOM SELECT
 * nessun parametro binding, $fetchMode specifica fa ritornare all'esterno un array di risultatu
 */
$c = $Da->customQuery("SELECT * FROM example", null, PDO::FETCH_ASSOC);
//print_r($c);


/**
 * QUERY CUSTOM INSERT
 * nessun parametro binding
 */
//$c = $Da->customQuery("INSERT INTO example (id_parent, position) VALUES (1,999)");


/**
 * QUERY CUSTOM INSERT
 * con binding
 */
/*$c = $Da->customQuery("INSERT INTO example (id_parent, position) VALUES (:id_parent,:position)", array(
    "id_parent" => 234
    ,"position" => 1111
));*/

/**
 * QUERY CUSTOM UPDATE
 * con binding (per le modalità di definizione dell'array di binding vedi query select sopra)
 */
$c = $Da->customQuery("UPDATE example SET id_parent = :id_parent, position = :position WHERE id = 1 ", array(
    "id_parent" => 111
    ,"position" => 8888
));
$c = $Da->customQuery("UPDATE example SET id_parent = :id_parent, position = :position WHERE id = :id ", array(
    "id_parent" => 222
    ,"position" => 7777
    ,"id" => 3
));
$c = $Da->customQuery("UPDATE example SET id_parent = ?, position = ? WHERE id =  ? ", [333, 9999, 1]);

