<?php 

$db = array(
        "host"                          =>      getenv("DB_HOST") ?: "localhost"
        ,"user"                          =>      getenv("DB_USERNAME") ?: ""
        ,"password"                      =>      getenv("DB_PASSWORD") ?: ""
        ,"database"                      =>      getenv("DB_DATABASE") ?: ""
        ,"charset"                       =>      "utf8"
);


return $db;
