<?php
/**
 * @author Emanuele Fornasier
 * @link http://www.atrio.it
 * @version 2016/03/15
 * @license MIT License
 */

namespace Box;

use Exception;

/**
 * Router component.
 */
class Router {
    

    /**
     * Array of links 
     * 
     * @var array
     */
    protected $linksCollection = array();


    /**
     * Array of links patterns
     * 
     * @var array
     */
    protected $linkPatternsCollection = array();
    

    /**
     * Last matched route
     * 
     * @var array
     */
    protected $match = array(
        "name" => null
        ,"type" => null
        ,"file" => null
        ,"params" => array()
    );


    /**
     * Array of last visited pages
     * 
     * @var array
     */
     private $arReferrers = array(
        'save'      => true         // do I want to save this Page
        ,'max'      => 10           // maximum number of links
        ,'URIs'     => array()      // urls list
     );


    /**
     * set referrers list
     *
     * @param void
     * @return void
     */
    public function __construct ()
    {
        $this->arReferrers['URIs'] = isset($_SESSION['ar_referrers']) ? $_SESSION['ar_referrers'] : array();
    }


    /**
     * if set, I add this page to referrers list
     *
     * @param void
     * @return void
     */
    public function __destruct ()
    {
        $thisUri = $_SERVER['REQUEST_URI'];

        // se non è un URI o è l'ultimo salvato non salvo nuovi url
        if($thisUri === null || (count($this->arReferrers['URIs']) > 0 && $this->arReferrers['URIs'][0] != $thisUri)):
            $this->arReferrers['save'] = false;
        endif;

        if($this->arReferrers['save'] === true){            // se salvo i referer

            if(count($this->arReferrers['URIs']) >= $this->arReferrers['max']):    // verifico che non ce ne siano troppi, se è così rimuovo quelli in eccesso
                $offset = $this->arReferrers['max'] - 1;
                $length = count($this->arReferrers['URIs']) - $this->arReferrers['max'] + 1;
                array_splice($this->arReferrers['URIs'], $offset, $length);
            endif;

            array_unshift($this->arReferrers['URIs'],$thisUri);
            $_SESSION['ar_referrers'] = $this->arReferrers['URIs'];
        }
    }


    /**
     * Adds a named route to internal collection
     * 
     * @param   string $routeName
     * @param   string $source
     * @param   string $destination
     * @return  true
     */
    public function addRoute($routeName, $source, $destination)
    {

        if (isset($this->linksCollection[$routeName])) {
            throw new Exception(__CLASS__." - ".$routeName." already exists in links collection.");
        }

        $this->linksCollection[$routeName] = array($routeName, $source , $destination);

        return true;

    }


    /**
     * Adds a route pattern to internal collection
     * 
     * @param   string $routeName
     * @param   string $sourcePattern
     * @param   string $destination
     * @return  null
     */
    public function addRoutePattern($routeName, $sourcePattern, $destination)
    {
        
        if (isset($this->linkPatternsCollection[$routeName])) {
            throw new Exception(__CLASS__." - ".$routeName." already exists in patterns collection.");
        }

        $this->linkPatternsCollection[$routeName] = array($routeName, $sourcePattern , $destination);

        return true;

    }


    /**
     * Match a request against links and link patterns
     *
     * @param  string $requestUri
     * @return string $target 
     * @return array $match = array(
     *      file => filepath
     *      params => request's params array  
     * )
     */
    public function matchRequest($requestUri)
    {

        $target = null;

        $request = parse_url($requestUri, PHP_URL_PATH);


        /**
         * Loops through links collection 
         */
        foreach($this->linksCollection as $k => $v):

            if($target != null): break; endif;
            // remove hash
            $v[1] = preg_replace('/\#(.*)$/', '', parse_url($v[1], PHP_URL_PATH));

            $target = $v[1] == $request ? $v[2] : null;
            $name = $target ? $v[0] : null;

            $this->match["type"] = 'link';
           
        endforeach;


        /**
         * Loops through patterns collection 
         */
        foreach($this->linkPatternsCollection as $k => $v):

            if($target != null): break; endif;
            
            // remove hash
            $v[1] = preg_replace('/\#(.*)$/', '', parse_url($v[1], PHP_URL_PATH));

            $matchPreg = $v[1];
            $replaceMent = $v[2];

            //$target = $v[1] && preg_match("#".$v[1]."#", $request, $preg_matches) ? $v[2] : null;
            
            if (preg_match("#".$matchPreg."#", $request)) :

                $target = preg_replace("#".$matchPreg."#", $replaceMent, $request);
                
                $this->match["type"] = 'pattern';

            endif;

        endforeach;

        if ($target) :
            
            $this->match["name"] = $v[0];
         
            $cps = parse_url($target);
            
            $this->match["file"] = $cps["path"];
            
            $this->match["params"] = array();

            // Splits query string into params
            if (isset($cps["query"])) {parse_str($cps["query"], $this->match["params"]);}
        
        endif;

        return $target ? $this->match : null;
    }


    /**
     * Do we want to save this page?
     *
     * @param  bool $save
     * @return void
     * 
     */
    public function saveReferrer($save = true){
        $this->arReferrers['save'] = $save;
    }


    /**
     * Returns last n visited url in index
     *
     * @param  int $key
     * @return string $url 
     * 
     */
    public function getReferrer($key = 0){
        
        if(!isset($this->arReferrers['URIs'][$key])):
            trigger_error('['.__METHOD__.'] Chiave referrer <code>'.$key.'</code> inesistente, ritorno il primo disponibile <pre>'.print_r($this->arReferrers['URIs'],1).'</pre>',E_USER_NOTICE);
            return count($this->arReferrers['URIs'][0]) > 0
                        ? $this->arReferrers['URIs'][0]
                        : null;
        else:
            return $this->arReferrers['URIs'][$key];
        endif;

    }

}

