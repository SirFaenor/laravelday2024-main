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

// select
$rs = $Da->getSingleRecord(array(
    "table" => "example"
    ,"cond" => "WHERE id = 1"
));

$result = $Da->getRecords(array(
    "table" => "example"
));

$c = $Da->updateRecords(array(
    "table" => "example"
    ,"data" => array(
        "position" => 6666
        ,"date_end" => '{{NOW()}}'
    )
    ,"cond" => "WHERE id = 1"
));
$c = $Da->createRecord(array(
    "table" => "example"
    ,"data" => array(
        "position" => 6666
        ,"date_end" => '{{NOW()}}'
    )
));
//print_r($c);

$c = $Da->deleteRecords(array(
    "table" => "example"
    ,"cond" => "WHERE id > 7"
));
print_r($c);