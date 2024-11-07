<?php

namespace Utility;

class Logger {

    private $logBasePath;

    public function __construct($log_basepath = NULL){
        if(!$log_basepath || !is_dir($log_basepath)):
            trigger_error('['.__METHOD__.'] Devi impostare una directory valida dove salvare i files di log');
        endif;

        $this->logBasePath = $log_basepath;
    }

    public function log($message, $context = [], $filename = 'logs.txt'){
        $db = debug_backtrace();

        /**
        * localizzazione
        */
        $scriptName = '';
        if($db  && array_key_exists(0, $db)) {
            $scriptName .= ' in '.$db[0]["file"].' at line '.$db[0]["line"];
        }


        /**
        * info extra
        */
        $info = '';
        if($context) {
            $info = ' - '.print_r($context, true);
        }


        $fullMessage = "[".date("Y-m-d H:i:s")."] $message$scriptName$info".PHP_EOL;

        return file_put_contents($this->logBasePath.'/'.$filename, $fullMessage , FILE_APPEND | LOCK_EX);

    }
}