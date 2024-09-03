<?php
/**
 * Risolutore globale per l'istanza di App
 * in uso.
 * Inserito da manu per comodità per evitare refactroing più pesante.
 */
class App
{
    
    protected static $app;

    public function __construct($app) {
        self::$app = $app;
    }

    public static function r() {
        return self::$app;
    }


}