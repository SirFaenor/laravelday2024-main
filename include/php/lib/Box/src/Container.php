<?php
/**
 * @author  Emanuele Fornasier
 * @link    http://http://www.atrio.it
 * @version 2016-10-08
 * @package Box
 * @license MIT License
 */

namespace Box;

use \Box\Exceptions\Core as BoxCoreExceptions;

/**
 * Iniettore di dipendenze, contenitore di servizi.
 * 
 * Memorizza servizi (componenti fissi non modificabili dell'applicazione) registrandoli con una closure
 * (che verrà chiamata solo all'occorrenza) oppure oggetti già istanziati.
 * Opera come factory per la creazione di istanze multiple registrandone una closure di creazione.
 * E' possibile registrare una closure per la creazione di qualsiasi componente, per la registrazione
 * di controller o per qualsiasi altra cosa (es. una routine)
 * 
 * Viene esteso da App, ma può essere usato indipendentemente.
 * 
 * @throws \Box\Exceptions\Core\*
 *
 */
class Container
{

    /**
     * @var array $services elenco servizi registrati
     */
    private $_services = array();


    /**
     * @var array $factories elenco factories registrate
     */
    private $_factories = array();


    /**
     * Memorizza un componente fisso interno.
     * Posso passare un oggetto già istanziato o una closure di creazione di un oggetto,
     * nel qual caso verrà instanziata al volo solo quando il componente sarà richiesto.
     * Per ogni chiamata allo stesso componente ritorna sempre la stessa istanza.
     * 
     * @param  @alias nome identificativo del componente (con cui verrà richiamato)
     * @param  @objectOrcallable istanza dell'oggetto o funzione di creazione, null per annullare
     * 
     * @return void
     */
    public function store($alias, $objectOrcallable)
    {

        if (isset($this->_services[$alias])) {
            throw new BoxCoreExceptions\ServiceAlreadyRegisteredException($alias);
        }

        $this->_services[$alias]["instance"] = $objectOrcallable;
        $this->_services[$alias]["inited"] = false;


        if ($this->_services[$alias] == null) {unset($this->_services[$alias]);}

        return;
        
    }


    /**
     * Ritorna il servizio.
     * Chiamata da __get()
     * Se il componente non è istanziato, tenta di istanziarlo con eventuali argomenti.
     * Il componente deve essere disponibile nello scope essendo stato esplicitamente incluso
     * o perchè rientra nel pattern di autoload.
     * 
     * Il primo e unico argomento che riceve la closure di creazione di un servizio
     * è l'istanza di Container in cui viene registrata.
     * 
     * @param string $name service name
     * @return object $service
     */
    private function getStored($name)
    {

        if (!isset($this->_services[$name])) {
            throw new BoxCoreExceptions\ServiceNotRegisteredException($name);
            return false;
        }

        $service = $this->_services[$name];

        // se il componente è già inizailizzato lo posso restituire subito
        if ($this->_services[$name]["inited"] == true) {return $this->_services[$name]["instance"];}

        // se il componente è una closure, la eseguo e ritorno l'istanza
        if (is_callable($this->_services[$name]["instance"])) {
            
            $this->_services[$name]["instance"] = call_user_func($this->_services[$name]["instance"]);
            $this->_services[$name]["inited"] = true;

            return $this->_services[$name]["instance"];
        }

        // imposto flag inizializzato e restituisco qualsiasi cosa abbia in istanza
        $this->_services[$name]["inited"] = true;
        return $this->_services[$name]["instance"];

    }


    /**
     * Memorizza una closure di creazione di un'istanza 
     * che verrà invocata all'occorrenza
     * 
     * @param  string $alias alias della classe
     * @param  string @objectOrcallable istanza dell'oggetto o funzione di creazione
     */
    public function factory($alias, $callable)
    {

        if (isset($this->_factories[$alias])) {
            throw new BoxCoreExceptions\ServiceAlreadyRegisteredException($alias);
        }
    
        $this->_factories[$alias] = $callable;
        
    }
    

    /**
     * Esegue una funzione precedentemente registrata
     * attraverso factory() inoltrandogli tutti i parametri ricevuti
     * dopo $alias.
     * 
     * @param  string $alias chiave della funzione invocabile come registrata
     * @return istance
     */
    public function create($alias)
    {

        if(!isset($this->_factories[$alias])) {
            throw new BoxCoreExceptions\ServiceNotRegisteredException($alias);
        }

        $factory = $this->_factories[$alias];

        // se ho ricevuto con create parametri extra
        // li passo alla $factory
        $params = array();
        $args = func_get_args();
        $argNum = count($args);
        if ($argNum > 1) :
            for ($i=1;$i<$argNum;$i++) :
                $params[] = $args[$i];
            endfor;
        endif;


        return call_user_func_array($factory,$params);
        
    
    }

    
    /** 
     * Evita sovrascrittura da esterno dei componenti memorizzati,
     * in modo da avere la certezza di utilizzare sempre il componente registrato.
     * v. __get()
     * 
     * @throws \Box\Exceptions\Core\CannotModifyServiceException
     */
    public function __set($k, $v)
    {
        throw new BoxCoreExceptions\CannotModifyServiceException($k);
    }


    /**
     * Consente di recuperare un componente attraverso la
     * sintassi $container->nomeComponente.
     * 
     * @return self::getStored()
     */
    public function __get($name)
    {
       return  self::getStored($name);
    }


    /**
     * Controlla se il componente è registrato.
     * (tentare di chiamare un componente non registrato
     * generebbe un'eccezione)
     */
    public function has($name) {
        return isset($this->_services[$name]);
    }

}