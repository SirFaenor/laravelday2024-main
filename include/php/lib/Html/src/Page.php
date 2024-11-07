<?php
/**
 * 
 * class Html\Page
 * 
 * Helper to build an html document.
 * Features:
 *  - outputs common parts of html
 *  - includes and minifies (optional) javascript and css resources
 *  - sets document's title and meta tags 
 *
 *  Todo list:
 *  - true javascript deferring?
 *  - caching ?
 *  - ensure "only once" resource inclusion
 * 
 * @author     Emanuele Fornasier
 * @link       www.atrio.it
 * @version    2017/10/21
 * 
 */
namespace Html;

use \Exception;

class Page {
    
    /**
     * defaults
    */
    private $_settings = array(
        "minimize"          => false,
        "css_output_path"   => '/css',
        "js_output_path"    => '/js',
        "css_output_url"    => '/css',
        "js_output_url"     => '/js',
        "page_class"        => NULL,
        "js_defer"          => false,
        "document_root"     => '',
    );

    /**
     * Force compression for javascript and css
     */
    public $debugMode = false;

    
    /**
     * Basic structure
     * You can customize these strings here or outside
     */
    public $TPLdeclaration = <<<HTML
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
HTML;
   
    public $TPLbodyOpening = <<<HTML
</head>
<body>
HTML;
    
    public $TPLbodyClosing = <<<HTML
</body>
</html>
HTML;
    
    
    /**
    * @var array _arSources
    * 
    * Stores sources
    * 
    */
    private $_arSources = array(
        "css_min" => array()           // these will be minified !
        ,"js_min" => array()            // these will be minified !
        ,"css" => array()
        ,"js" => array()
        ,"ld_json" => array()
    );
    

    /**
    * @var array _arExtraCode
    * 
    * Stores extra js/css code that will be inijected into the document
    * 
    */
    private $_arExtraCode = array(
        "css" => array()
        ,"js" => array()
        ,"ld_json"  => array()
    );   

    
    /**
     * vars
     * 
     */
    private $_title = '';
    private $_arMetas = array();
    private $_arLinks = array();
    
    /**
     * output check
     */
    private $_outputStarted = false;
    
    /**
     * set to true by open(), to prevent
     * misusing open() and close()
     */
    private $_openedByMe = false;

    private $_bodyOpeningAfter;
    private $_bodyClosingBefore;
    private $_headClosingBefore;

    private $_jsIndex = -1; // counter to mantain keys order when merging js and js_min
    private $_cssIndex = -1; // counter to mantain keys order when merging css and css_min
    
    private $_jsMinKey = -1;  //counter to store output position of the minified js file tag.
    private $_cssMinKey = -1; // counter to store output position of the minified css file tag.

    private $_maxCharToInline;   // if I have an external resource I will inline it if shorter than

    private $_arMicrodata = array();

    public $SD_PAGE;            // nome del file (senza estensioni che conterrà i dati strutturati)

    private $assets;            // array con gli assets da unire e da cui generare un unico file
    private $joinedAssetsFilepath; // percorso del file con gli assets uniti


    /**
     * Validates options to overwrite defaults. 
     * 
     * @param array $options
     * @return null
     * @throw
     */
    public function __construct(array $options = array(), $_maxCharToInline = 300) {

        if (count($options)) :
            // merge options
            foreach ($options as $key => $value): 
                if (array_key_exists($key, $this->_settings)) :
                    $this->_settings[$key] = $value;
                endif;
            endforeach;
        endif;


    }


    /**
     * 
     * Sets document <title> tag.
     * 
    */
    public function title($title) {
        
        $this->_checkOutput();

        $this->_title = $title;
    }


    /**
     * Sets documents meta  tag
     *
     * @param array $attributes
     */
    public function meta(array $attributes) {
        
        $this->_checkOutput();
        
       /* if (isset($attributes["name"]) && $attributes["name"] == 'description') {
            return $this->description($attributes["content"]);
        } */
        
        $this->_arMetas[$attributes["name"]] = $attributes;


    }


    /**
     * Sets a link tag
     *
     * @param array $attributes
     */
    public function link(array $attributes) {
        
        $this->_checkOutput();
        
        $this->_arLinks[] = $attributes;
    }


    /**
     * Prepend document's description meta tag
     *
     * @param string $description
     */
    public function description($description) {
        
        $this->_checkOutput();
        
        $this->meta(array("name" => "description", "content" => $description));

    }


    /**
     * Shorthand for "keywords" metatag
     *
     * @param string $description
     */
    public function keywords($keywords) {
        
        $this->_checkOutput();

        $this->meta(array("name" => "keywords", "content" => $keywords));

    }


    /**
     * Shorthand for <link rel="alternate">
     * 
     * @param array $alternates array of alternates {language Tag} => {url}
     */
    public function alternates($alternates) {
        foreach ($alternates as $lgTag => $url) {
            $this->link(array(
                "rel" => "alternate"
                ,"href" => $url
                ,"hrefLang" => $lgTag
            ));
        }

        return true;
    }


    /**
     *Adds a linked ld+json resource.
     * @param string $src -> attribute "src" of a <script> tag
    */
    public function includeLdJson($src) {
        $this->_checkOutput();        
        $this->_arSources['ld_json'][] = $src;
    }



    /**
     * Adds a linked js resource.
     * @param string $url : "src" attribute of a <script> tag
     * @param bool $min : true/false to mark for compression
     * @param string $filepath filesystem filepath
    */
    public function includeJs($url, $min = false, $filepath = null) {
        $this->_checkOutput();

        if ($min && !$filepath) {
            throw new Exception('Filepath missing for js "'.$url.'"');
        }
        
        $dest = $min ? 'js_min' : 'js';

        $this->_jsIndex++;

        if ($min) {
            $this->_jsMinKey = $this->_jsIndex;
        }

        $this->_arSources[$dest][$this->_jsIndex]["url"] = $url;
        $this->_arSources[$dest][$this->_jsIndex]["path"] = $filepath;

    }


    /**
     * Adds a linked css resource.
     * @param string $url  : "href" attribute of a <link> tag
     * @param bool $min : true/false to mark for compression
     * @param string $filepath filesystem filepath
     */
    public function includeCss($url, $min = false, $filepath = null) {
        $this->_checkOutput();
        
        if ($min && !$filepath) {
            throw new Exception('Filepath missing for css "'.$url.'"');
        }

        $dest = $min ? 'css_min' : 'css';
        
        $this->_cssIndex++;

        if ($min) {
            $this->_cssMinKey = $this->_cssIndex;
        }
      

        $this->_arSources[$dest][$this->_cssIndex]["url"] = $url;
        $this->_arSources[$dest][$this->_cssIndex]["path"] = $filepath;
      
    }
   

    /**
     * Adds plain json+ld code in a separate <script> tag.
     * @param string $code
    */
    public function addLdJson($code) {
        
        $this->_checkOutput();

        $this->_arExtraCode["ld_json"][] = $code;
    }


    /**
     * Adds plain js code in a <script> tag.
     * @param string $code
     */
    public function addJs($code) {
        
        $this->_checkOutput();

        $this->_arExtraCode["js"][] = $code;    
    }
   

    /**
     * Adds plain css code in a separate <style> tag.
     * @param string $code
     */
    public function addCss($code) {
        
        $this->_checkOutput();

        $this->_arExtraCode["css"][] = $code;    
    }


    /**
     * Sets <body>'s class attributes    
     * @param string $class
     */
    public function addClass($class) {

        $this->_checkOutput();

        $this->_settings['page_class'] = $class;
    }


    /**
     * Css compressor
     */
    private function _minifyCss($content) {
        
        /* comments */ 
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
       
        /* tabs, rows, spaces etc. */
        $content = str_replace(array("\r\n","\r","\n","\t"), '', $content);
        
        /* other spaces */
        $content = preg_replace('!\s*(,|:)\s*!', '$1', $content);
        $content = preg_replace(array('(( )+{)','({( )+)'), '{', $content);
        $content = preg_replace(array('(( )+})','(}( )+)','(;( )*})'), '}', $content);
        $content = preg_replace(array('(;( )+)','(( )+;)'), ';', $content);

        return $content;
    }
    

    /**
     * Javascript compressor
     */
    private function _minifyJs($content) {
        
        /* modify addresses to avoid false comments  */
        $search = array("https://","http://","//www");
        $replace = array("https:/","http:/","/www");
        $content = str_replace($search, $replace, $content);

        /* comments */
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        $content = preg_replace('/( )*\/\/(.*)\\n/', '', $content);
            
        /* abs, rows, spaces etc. */
        $content = str_replace(array("\r\n","\r","\t","\n"), '', $content);
        

        /* other spaces */
        $content = preg_replace('!\s*(\(|\)|,|=|\|\|)\s*!', '$1', $content);

        $content = preg_replace(array('(( )+{)','({( )+)'), '{', $content);
        $content = preg_replace(array('(( )+})','(}( )+)','(;( )*})'), '}', $content);
        $content = preg_replace(array('(;( )+)','(( )+;)'), ';', $content);
        
        /* restore addresses  */
        $search = array("https:/","http:/","/www");
        $replace = array("https://","http://","//www");
        $content = str_replace($search, $replace, $content);

        return $content;

    }
    

    /**
     * Setups css and js. 
     * Check if there was an upddate from the last compression date.
     */
    private function _setupOutput() {
        

        if ($this->_settings["minimize"]) :

            if ($this->debugMode) {
                $minCss = true;
                $minJs = true;
            } else {
                $minCss = false;
                $minJs = false;
            }

            $dirCss = scandir($this->_settings["css_output_path"]);
            $dirJs = scandir($this->_settings["js_output_path"]);
            $css = preg_grep("/style.all.min\.([0-9]+)\.css/", $dirCss);
            $js = preg_grep("/func.all.min\.([0-9]+)\.js/", $dirJs);

            $timeStampCss = $css ? preg_split("/min\.(.*)\.css/", array_pop($css), NULL, PREG_SPLIT_DELIM_CAPTURE) : NULL;
            $timeStampCss = $timeStampCss ? $timeStampCss[1] : NULL;

            $timeStampJs = $js ? preg_split("/min\.(.*)\.js/", array_pop($js), NULL, PREG_SPLIT_DELIM_CAPTURE) : NULL;
            $timeStampJs = $timeStampJs ? $timeStampJs[1] : NULL;

            $fileCss = $this->_settings["css_output_path"].'/style.all.min.'.$timeStampCss.'.css';
            $fileJs = $this->_settings["js_output_path"].'/func.all.min.'.$timeStampJs.'.js';


            // loop css
            $arFile = array();
            $arFile["css"] = array();
            foreach ($this->_arSources["css_min"] as $key => $file) {
                    
                if (file_exists($file["path"])) :
                    $fileMod = date('YmdHis',filemtime($file["path"]));
                    
                    if ($fileMod > $timeStampCss) :
                        $minCss = true;
                    endif;

                    $arFile["css"][] = $file["path"];    
                endif;
            }


            // loop js
            $arFile["js"] = array();
            foreach ($this->_arSources["js_min"] as $key => $file) {

                if (file_exists($file["path"])) :
                    $fileMod = date('YmdHis',filemtime($file["path"]));
                    if ($fileMod > $timeStampJs) :
                        $minJs = true;
                    endif;    

                    $arFile["js"][] = $file["path"];    
              
                endif;
                
            }

            // minimize css if there was un update
            if ($minCss) :
                $str_css = '';
                foreach ($arFile["css"] as $filepath) :
                    
                    if (file_exists($filepath)) :
                        $file_contents = file_get_contents($filepath);

                        $str_css .= $this->_minifyCss($file_contents);
                    endif;
                        
                endforeach;

                if (file_exists($fileCss)) {@unlink($fileCss);} // delete old file
                $timeStampCss = date('YmdHis');
                file_put_contents($this->_settings["css_output_path"].'/style.all.min.'.$timeStampCss.'.css', $str_css); // scrivo nuovo file con nuovo timestamp
            endif;


            // minimize js if there was un update
            if ($minJs) :
                $str_js = '';
                foreach ($arFile["js"] as $filepath) :

                    if (file_exists($filepath)) :
                        
                        $file_contents = file_get_contents($filepath);
                        $str_js .= $this->_minifyJs($file_contents);

                    endif;

                endforeach;

                if (file_exists($fileJs)) {@unlink($fileJs);} // delete old file
                $timeStampJs = date('YmdHis');
                file_put_contents($this->_settings["js_output_path"].'/func.all.min.'.$timeStampJs.'.js', $str_js); // scrivo nuovo file con nuovo timestamp
            endif;

            // Store compressed css
            $this->_arSources["css"][$this->_cssMinKey]["url"] = $this->_settings["css_output_url"].'/style.all.min.'.$timeStampCss.'.css';
           
            // Store compressed js
            $this->_arSources["js"][$this->_jsMinKey]["url"] = $this->_settings["js_output_url"].'/func.all.min.'.$timeStampJs.'.js';

            ksort($this->_arSources["css"]);
            ksort($this->_arSources["js"]);
        else :

            // no minimize
            // merges the arrays with different keys and order the resulting one
            // this preserve the order in which resources are provided

            foreach ($this->_arSources["js_min"] as $key => $item): 
                $this->_arSources["js"][$key] = $item;      
            endforeach;
            ksort($this->_arSources["js"]);

            foreach ($this->_arSources["css_min"] as $key => $item): 
                $this->_arSources["css"][$key] = $item;      
            endforeach;
            ksort($this->_arSources["css"]);
          
        endif;
        


    }


    /**
     * Check if output has already started.
     * Called by methods that must be invoked before output starts.  
     */
    private function _checkOutput() {
        if ($this->_outputStarted) :

            $caller = debug_backtrace();

            throw new Exception("[".__CLASS__."] Output already started, you cannot use '".$caller[1]["function"]."()' now!");
        endif;
    }

    
    
    /**
     * Prepends custom code to <body> (right after "<body>")
     */
    public function prependHtml($code) {
        $this->_checkOutput();

        $this->_bodyOpeningAfter .= $code;
    }
    

    /**
     * Appends custom code to <body> (right before "</body>")
     */
    public function appendHtml($code) {
        $this->_checkOutput();

        $this->_bodyClosingBefore .= $code;
    }


    /**
     * Appends custom code to <head> (right before "</head>")
     */
    public function appendHead($code) {
        $this->_checkOutput();
        
        $this->_headClosingBefore .= PHP_EOL.$code;
    }


    /**
     * Starts document output with doctype declaration, <head> section, and opening <body> tag, plus prepended custom html.
     * You can change standard output by editing $this->_declaration and $this->_bodyOpening.
    */
    public function open() {
        
        $this->_outputStarted = true;

        $this->_openedByMe = true;

        // doctype
        echo $this->TPLdeclaration."\n";

        $this->outputHead();
        
        // document start
        if ($this->_settings['page_class']) {
            $this->TPLbodyOpening = str_replace('<body>', '<body class="'.$this->_settings['page_class'].'">', $this->TPLbodyOpening);
        }
        
        echo $this->TPLbodyOpening."\n";
        
        echo $this->_bodyOpeningAfter;

    }


    /**
     * Closes document body and html, plus appended custom html
     * You can change standard output by editing $_bodyClosing.
    */
    public function close() {
            
        if (!$this->_openedByMe) :
            throw new Exception(__CLASS__." Cannot use close(), open() missing.");
        endif;

        // page append
        echo $this->_bodyClosingBefore;
        
        $this->outputFoot();

        // closing
        echo $this->TPLbodyClosing;

    }

    /**
     * Outputs <head>
     * Called by open() but can be used separately.
     */
    public function outputHead() {
        
        // title and metas
        
        if (\strlen($this->_title)) {echo '<title>'.$this->_title.'</title>'."\n";}
        
        if ($this->_arMetas) :
            
            // description and keywords first
            $this->_arMetas = array_merge(["description" => '',"keywords" => ''], $this->_arMetas);

            foreach ($this->_arMetas as $attributes): 
                if (!$attributes) {continue;}
                echo '<meta';
                foreach ($attributes as $attribute => $value): 
                    echo  ' '.$attribute.'="'.$value.'"';
                endforeach;
                echo '/>'."\n";
            endforeach;
        endif;

        // combine and minimize resources if needed
        $this->_setupOutput();

        // output css
        foreach ($this->_arSources["css"] as $key => $file): 

            # VERIFICO SE METTERE PICCOLI CSS INLINE
            $css_file_contents  = $file['path'] && file_exists($file['path']) ? file_get_contents($file['path']) : NULL;

            echo $css_file_contents != NULL && strlen($css_file_contents) <= $this->_maxCharToInline
                    ? $this->_writeCssExtracode($css_file_contents).PHP_EOL   
                    : '<link href="'.$file["url"].'" type="text/css" rel="stylesheet">'.PHP_EOL;

        endforeach;
        foreach ($this->_arExtraCode["css"] as $code): 
            echo $this->_writeCssExtracode($code);   
        endforeach;        

        // output links
        if ($this->_arLinks) :
            foreach ($this->_arLinks as $attributes): 
                echo '<link';
                foreach ($attributes as $attribute => $value): 
                    echo  ' '.$attribute.'="'.$value.'"';
                endforeach;
                echo '/>'.PHP_EOL;
            endforeach;
        endif;
        
        // output json+ld
        if(isset($this->_arExtraCode["ld_json"]) && count($this->_arExtraCode["ld_json"]) > 0):
            foreach ($this->_arExtraCode["ld_json"] as $code): 
                 echo '<script type="application/ld+json">'.PHP_EOL.'//<!--'.PHP_EOL.$code.PHP_EOL.'//-->'.PHP_EOL.'</script>'.PHP_EOL;
            endforeach;
            foreach ($this->_arSources["ld_json"] as $key => $src): 
                echo '<script type="application/ld+json">'.PHP_EOL.
                     '//<!--'.PHP_EOL;
                    include($src);
                echo '//-->'.PHP_EOL.
                     '</script>'.PHP_EOL;
            endforeach;
        endif;
        
        // output js (no deferring)
        if (!$this->_settings["js_defer"]) :

            foreach ($this->_arSources["js"] as $key => $file): 
                # VERIFICO SE METTERE PICCOLI JS INLINE
                $js_file_contents  = file_exists($file['path']) ? file_get_contents($file['path']) : NULL;
                echo $js_file_contents != NULL && strlen($js_file_contents) <= $this->_maxCharToInline
                        ? $this->_writeJsExtracode($js_file_contents).PHP_EOL   
                        : '<script type="text/javascript" src="'.$file["url"].'"></script>'.PHP_EOL;
            endforeach;
            foreach ($this->_arExtraCode["js"] as $code): 
                 echo $this->_writeJsExtracode($code);   
            endforeach;

        endif;

    // extra
        echo $this->_headClosingBefore;

    }


    /**
     * Outputs page foot (for javascript deferring)
     * Called by open() but can be used separately.
     */
    public function outputFoot() {
        
        // output js (deferring)
        if ($this->_settings["js_defer"]) :
            foreach ($this->_arSources["js"] as $key => $file): 
                echo '<script src="'.$file["url"].'"></script>'."\n";   
            endforeach;
            foreach ($this->_arExtraCode["js"] as $code): 
                 echo '<script>'."\n".$code."\n".'</script>'."\n";   
            endforeach;
        endif;

        if ($this->_settings["js_defer"]) :
            
            // converting php array into a string declaring a valid javascript array
            $jsArray = '[';
            foreach ($this->_arSources["js"] as $key => $file): 
                $jsArray .= "'".$file["url"]."',";
            endforeach;
            $jsArray = rtrim($jsArray,',');
            $jsArray .= ']';

        endif;

    }


    /**
    * setLang
    * imposta l'attributo lang nel tag html
    * @param string $lgSuff (codice lingua)
    * @return void
    */
    public function setLang($lgSuff){
        if(!$lgSuff || $this->_outputStarted !== false): return false; endif;
        $this->TPLdeclaration = str_replace('<html>','<html lang="'.$lgSuff.'">',$this->TPLdeclaration);        
    }



    /**
     * returns plain css code
     * @param string $code
     * @return string
     */
    private function _writeCssExtracode($code){
        if($code !== NULL && strlen($code) > 0):
            return $this->_settings["minimize"] != 0 
                        ? '<style type="text/css">'.PHP_EOL.'/*<!--*/'.PHP_EOL.$this->_minifyCss($code).PHP_EOL.'/*-->*/'.PHP_EOL.'</style>'.PHP_EOL
                        : '<style type="text/css">'.PHP_EOL.'/*<!--*/'.PHP_EOL.$code.PHP_EOL.'/*-->*/'.PHP_EOL.'</style>'.PHP_EOL;
        else:
            return '';
        endif;
    }


    /**
     * returns plain js code
     * @param string $code
     * @return string
     */
    private function _writeJsExtracode($code){
        if($code !== NULL && strlen($code) > 0):
            return $this->_settings["minimize"] != 0 
                        ? '<script type="text/javascript">'.PHP_EOL.'//<!--'.PHP_EOL.$this->_minifyJs($code).PHP_EOL.'//-->'.PHP_EOL.'</script>'.PHP_EOL 
                        : '<script type="text/javascript">'.PHP_EOL.'//<!--'.PHP_EOL.$code.PHP_EOL.'//-->'.PHP_EOL.'</script>'.PHP_EOL;
        else:
            return '';
        endif;
    }


    /**
     * add microdata to the  array
     * @param string $microdata
     * @return void
     */
    public function addMicrodata($microdata){
        $this->_arMicrodata[] = $microdata;
    }


    /**
     * add microdata to the  array
     * @return void
     */
    public function microdataToPage(){
        if(is_array($this->_arMicrodata)):
            switch(count($this->_arMicrodata)):
                case 0: break;  // non faccio niente
                case 1:
                    $arGraph = $this->_arMicrodata[0];
                    break;
                default:
                    $arGraph = array(   
                        "@context"  => "http://schema.org"
                        ,"@graph"   => $this->_arMicrodata
                    );
            endswitch;
            if($this->_settings["minimize"] == 0):
                $this->addLdJson(json_encode($arGraph,JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            else:
                $this->addLdJson(json_encode($arGraph,JSON_UNESCAPED_SLASHES));
            endif;
        endif;
    }


    /**
     * set the assets to join and export in 1 file
     * @param $assets array with assets source list
     * @return $this
     */
    public function groupAssets($assets = array()){
        if(!is_array($assets) || !count($assets)):
            trigger_error('['.__METHOD__.'] La variabile <code>$assets</code> non è un array o è un array vuoto: <pre>'.print_r($assets,1).'</pre>',E_USER_ERROR);
        endif;

        $this->assets = array(); // resetto gli assets da raggruppare

        // divido percorso da url
        foreach($assets as $A):
            $assetInfo = array();
            if(is_array($A)):
                $assetInfo['url']        = isset($A['url']) ? $A['url'] : reset($A);
                $assetInfo['filepath']   = isset($A['filepath']) ? $A['filepath'] : reset($A);
            else:
                $assetInfo['url']        = $A;
                $assetInfo['filepath']   = $A;
            endif;
            $this->assets[] = $assetInfo; 
        endforeach;

        return $this;
    }


    /**
     * joins assets to one file
     * @param $filepath file pathname that will gather all assets content
     * @return $filepath 
     */
    public function assetsToFile($filepath){
        if(!is_array($this->assets) || !count($this->assets)):
            trigger_error('['.__METHOD__.'] La variabile <code>$assets</code> non è un array o è un array vuoto: <pre>'.print_r($this->assets,1).'</pre>: definisci prima gli assets e poi genera il file',E_USER_ERROR);
        endif;

        $this->joinedAssetsFilepath = $filepath;

        $globalContentToString = file_exists($filepath) && is_file($filepath) ? file_get_contents($filepath) : ''; # recupero la data di ultima modifica del file globale 

        $assetsToString = '';

        foreach($this->assets as $source):
            $assetsToString .= file_get_contents($source['filepath']);
        endforeach;

        if($globalContentToString != $assetsToString && file_put_contents($filepath, $assetsToString, LOCK_EX) === false):
            trigger_error('['.__METHOD__.'] Errore nella generazione del file unico con gli assets',E_USER_ERROR);
        endif;

        return $this;

    }


    /**
     * joins assets to one file
     * @param $filepath file pathname that will gather all assets content
     * @return $filepath 
     */
    public function includeAssets($type = NULL, $group = false, $minify = false, $url = '/'){
        switch($type):
            case 'css': $method = 'includeCss'; break;
            case 'js': $method = 'includeJs'; break;
            default:
                trigger_error('['.__METHOD__.'] Specificare un tipo di file in output',E_USER_ERROR);
        endswitch;

        if($group === false):
            foreach($this->assets as $source):
                $this->{$method}($source['url'],false,$source['filepath']);
            endforeach;
        else:
            $this->{$method}($url,$minify,$this->joinedAssetsFilepath);
        endif;
    }


}