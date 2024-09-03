<?php 

$db = array(
        "host"                          =>      getenv("DB_HOST") ?: "localhost"
        ,"user"                          =>      getenv("DB_USERNAME") ?: "aipioppi_usr"
        ,"password"                      =>      getenv("DB_PASSWORD") ?: "CZ:feb&~=iQ<o28!RJEyrgCqyZ!p?L>H4bnhI:zR"
        ,"database"                      =>      getenv("DB_DATABASE") ?: "aipioppi_2019"
        ,"charset"                       =>      "utf8"
);


return $db;
