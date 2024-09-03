<?php

namespace Utility;

class csrf {

    private $sessionIndex = 'token';

    public function __construct(string $sessionIndex = 'token'){

        $this->sessionIndex = $sessionIndex;

    }


    /**
     * setTokenId()
     * Creo un token casuale
     * @param void
     * @return string il token
     */
    protected function createTokenId():string {
        $_SESSION[$this->sessionIndex.'_id'] = $this->random(10);
        return $_SESSION[$this->sessionIndex.'_id'];
    }


    /**
     * setToken()
     * Creo il token.
     * @param void
     * @return string
     */
    protected function createToken():string {
        $_SESSION[$this->sessionIndex.'_value'] = bin2hex(random_bytes(32));
        return $_SESSION[$this->sessionIndex.'_value'];
    }


    /**
     * getTokenId()
     * Questa funzione prende l'id token dalla sessione utenti, se non ne è già stato creato uno, dopo di che genera un token casuale.
     * @param bool regenerate
     * @return string il token
     */
    public function getTokenId(bool $regenerate = false):string {
        if($regenerate === true):
            unset($_SESSION[$this->sessionIndex.'_id']);
        endif;
        return isset($_SESSION[$this->sessionIndex.'_id']) && !empty($_SESSION[$this->sessionIndex.'_id'])
                    ? $_SESSION[$this->sessionIndex.'_id']
                    : $this->createTokenId();
    }


    /**
     * getToken()
     * Questa funzione ottiene il valore del token, se non è già stato creato, dopo di che ne genera uno.
     * @param bool regenerate
     * @return string
     */
    public function getToken(bool $regenerate = false):string {
        if($regenerate === true):
            unset($_SESSION[$this->sessionIndex.'_id']);
        endif;
        return isset($_SESSION[$this->sessionIndex.'_value']) && !empty($_SESSION[$this->sessionIndex.'_value'])
                ? $_SESSION[$this->sessionIndex.'_value']
                : $this->createToken();
    }


    /**
     * checkValid()
     * Questa funzione è utilizzata per controllare se l'id token e il valore del token sono validi. 
     * Lo fa controllando i valori della richiesta GET o POST con i valori memorizzati nella variabile della sessione utenti.
     * @param string metodo di invio
     * @return bool
     */
    public function checkValid(string $method):bool {
        if($method == 'post' || $method == 'get'):
            $post = $_POST;
            $get = $_GET;
            if(isset(${$method}[$this->getTokenId()]) && !empty(${$method}[$this->getTokenId()]) && hash_equals(${$method}[$this->getTokenId()],$this->getToken())):
                return true;
            else:
                return false;
            endif;
        else:
            return false;
        endif;
    }


    /**
     * formNames()
     * Questa è la seconda difesa di cui si parla in questo articolo contro l’attacco CSRF. 
     * Questa funzione genera nomi casuali per i campi del modulo. 
     * @param array i valori "name" dei campi input del form
     * @param bool ricreare i nomi casuali
     * @return array i name modificati
     */
    public function formNames(array $names, bool $regenerate):array {
        $values = array();
        foreach ($names as $n):
            if($regenerate == true):
                unset($_SESSION[$n]);
            endif;
            $s = isset($_SESSION[$n]) ? $_SESSION[$n] : $this->random(10);
            $_SESSION[$n] = $s;
            $values[$n] = $s;
        endforeach;
        return $values;
    }


    /**
     * random()
     * Questa funzione genera una stringa casuale utilizzando il file Random di Linux per aumentare l’entropia.
     * @param int lunghezza della stringa
     * @return string la stringa casuale
     */
    private function random(int $len):string {
        if (function_exists('openssl_random_pseudo_bytes')):
            $byteLen = intval(($len / 2) + 1);
            $return = substr(bin2hex(openssl_random_pseudo_bytes($byteLen)), 0, $len);
        elseif (@is_readable('/dev/urandom')):
            $f=fopen('/dev/urandom', 'r');
            $urandom=fread($f, $len);
            fclose($f);
            $return = '';
        endif;
        
        if (empty($return)):
            for ($i=0;$i<$len;++$i):
                if (!isset($urandom)):
                    if ($i%2==0):
                        mt_srand(time()%2147 * 1000000 + (double)microtime() * 1000000);
                    endif;
                    $rand=48+mt_rand()%64;
                else:
                    $rand=48+ord($urandom[$i])%64;
                endif;
        
                if ($rand>57): $rand+=7; endif;
                if ($rand>90): $rand+=6; endif;
        
                if ($rand==123): $rand=52; endif;
                if ($rand==124): $rand=53; endif;
                $return.=chr($rand);
            endfor;
        endif;
        
        return $return;
    }
}