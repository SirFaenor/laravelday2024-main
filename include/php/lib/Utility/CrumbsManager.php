<?php

/**
 * Classe per gestire token per proteggere le richieste ajax da CSRF/XRF
 */

namespace Utility;

class CrumbsManager {

    private $salt = 'abcdefghijklmnopqrstuvwxyz';
    private $uid;

    /**
     * __construct()
     * Imposto un uid condiviso che va a costituire il crumb
     */
    public function __construct(string $uid = 'webservice', string $salt = null){
        $this->uid = $uid;
        if($salt):
            $this->salt = $salt;
        endif;
    }

    /**
     * challenge()
     * @param string contiene la stringa base
     * @return string hash
     */
    protected function challenge($data){
        return hash_hmac('md5', $data, $this->salt);
    }

    /**
     * issue_crumb
     * @param int durata
     * @param valore contestuale
     * @return string crumb
     */
    public function issue_crumb($ttl, $action = -1) {

        // ttl
        $i = ceil(time() / $ttl);

        // return crumb
        return substr($this->challenge($i . $action . $this->uid), -12, 10);
    }

    /**
     * verify_crumb
     * @param int durata
     * @param crumb
     * @param valore contestuale
     * @return bool
     */
    public function verify_crumb($ttl, $crumb, $action = -1) {
        // ttl
        $i = ceil(time() / $ttl);
        // verify crumb
        return substr($this->challenge($i . $action . $this->uid), -12, 10) == $crumb || substr($this->challenge(($i - 1) . $action . $this->uid), -12, 10) == $crumb;
    }
}
