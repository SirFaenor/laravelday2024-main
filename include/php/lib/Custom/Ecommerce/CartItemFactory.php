<?php
/**
 * @author manu
 */
namespace Custom\Ecommerce;

use Utility;

use \Box\Exceptions as Exceptions;

/**
 * Factory per la creazione veloce delle entità che saranno poi passate al carrello.
 * Mappa le entità originarie con la tipologia nel carrello.
 */
class CartItemFactory 
{

    /**
     * @var array $repositories array dei repository per il recupero
     * delle voci originarie
     */
    protected $repositories = array();

    /**
     * @param $dataModels vedi dichiarazione variabile
     */
    public function __construct(array $repositories) {
        $this->repositories = $repositories;
    }

    /**
     * Crea item epr il carrello a partire 
     * dalla chiave type_id
     */
    public function createFromKey($key, $lgId) 
    {   

        // scompongo in tipo e id
        $arData = explode("_", $key);

        return $this->createFromTypeAndId($arData[0], $arData[1], $lgId);
 
    }

    /**
     * @param $type tipo oggetto
     * @param $id_item id originario dell'oggetto
     */
    public function createFromTypeAndId($type, $id_item, $lgId) {
        
        switch($type):
            
            // prodotto
            case 'pr':

                $Item = $this->repositories["products"]->withPrice()->showUnpublished()->loadById($id_item, $lgId);

                break;

            default:

                throw new Exceptions\BadRequestException('Tipo sconosciuto');

                break;

        endswitch;  

        return $Item;    


    }


}