<?php
/**
 * Crea un client API autenticato, singleton.
 */
class ApiClientFactory {

    /**
     * Istanza del client
     */
    protected static $client;


    /**
     * Token API
     */ 
    protected string $token;


    /**
     * Crea un client al bisogno,
     * restituisce sempre la stessa istanza
     */
    public static function make() {
        if (!self::$client) {
            self::$client = self::createClient();
        }

        return self::$client;
    }

    /**
     * Creazione del client
     */
    protected static function createClient() : \GuzzleHttp\Client {

        // accesso a container dell'applicazione
        $container = App::r();

        return new \GuzzleHttp\Client([
            'base_uri' => $container->Config['api']['url'],
            'headers' => [
                'Authorization' => 'Bearer ' . $container->Config['api']['token'],
            ],
        ]);
    }

}