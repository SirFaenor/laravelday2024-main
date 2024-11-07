<?php 
/**
 * ###########################################################################
 * class.Form
 *  *---------------------------------------------------------------------------
 * -> Valida il form
 * -> Memorizza i dati
 * -> Crea una coppia nome_colonna -> valore per salvare i dati in DB 
 *     !!! LA SANITIZZAZIONE DELLE STRINGHE VA GESTITA ESTERNAMENTE 
 *         ALLA CLASSE, IN FASE DI SALVATAGGIO DEI DATI
 * -> Invia una mail con i dati
 * 
 * ###########################################################################
 * 
 * Updates 
 * -03.12.2020 by Jacopo Viscuso
 *     -> validazione password
 *     -> fix vari
 * 
 * -19.05.2016 by Jacopo Viscuso
 *     -> inserita validazione per i campi di tipo data
 *     -> rimosso il fix della stringa che va gestito esternamente
 * 
 * -07.04.2016 by Jacopo Viscuso
 *     -> nella validazione del nome file dal valore in post recupero solo il basename (chrome ed edge passano tutto il percorso e il caratter ':' genera errore)
 * 
 * -17.09.2015 by Jacopo Viscuso
 *     -> anticipato il parsing dell'xml in fase di costruzione in modo da poter cambiare attributi specifici degli input prima della validazione
 * 
 * - 22.10.2015 by Jacopo Viscuso
 *     -> aggiunta gestione allegati: metodi uploadAttachments() e clearAttachments() e proprietà pubblica $arAttachments)
 */

namespace Site;
use Exception;
use SimpleXMLElement;
class Form {

    public $sessionIndex;                   // indice di sessione da usare per il form
    private $objXml;                        // oggetto xml che conterrà le informazioni sui campi input
    
    public $arFormInputs = array();         // array che conterrà tutte le informazioni sui campi di input
    public $arSqlInputs = array();          // array per la query di inserimento in db
    public $arAttachments = array();        // array che conterrà gli allegati
    private $attachmentsDir = '';           // directory che contiene gli allegati
    private $uploadBaseDir = '';            // directory che contiene gli allegati
    public $max_file_size = '5242880';      // peso massimo per il file

    private $ajScript = '';                 // script per l'invio del form ajax
    public $arResponse = array('result' => 0, 'data' => array(), 'msg' => '');  // array che contiene la risposta di validazione
    public $redirectUrl = '';               // url per il reindirizzamento
    private $isSubmitted = 0;

    private $Lang;
    private $Da;
    private $File;
    private $params = array('path_to_xml_file' => null, 'form_name' => 'contatti');

    public $tableName;                      // nome della tabella in cui salvare i dati

    private $passwordSettings = array(
                                        'minlength'     => 8
                                        ,'maxlength'    => 30
                                        ,'mode'         => 'Ln'
                                    );

    private $mailHead = null;               // corpo della mail
    private $mailBody = null;               // corpo della mail
    private $mailFoot = null;               // corpo della mail
    public $arMailInfo = array(             // informazioni per compilare il mail body
        'SITE_NAME'     => ''
        ,'SITE_COLOR'   => '#808080'
        ,'MAIL_TITLE'   => 'RICHIESTA INFORMAZIONI'
        ,'SITE_MAIL'    => ''
    );

    private $csrf = NULL;                   // uso il controllo csrf (delego ad una classe esterna la gestione)
    private $useCsrf = true;
    private $inputsPrepared = false;


    /**
     * COSTRUTTORE
     * imposto di valori del form da sessione se presenti (valori, errori)
     * @param obj istanza di LangManager per gestione lingue
     * @param array parametri da unire a quelli inseriti
     * @return void
     */
    public function __construct($Lang, $params)
    {

        $this->Lang         = $Lang;

        $this->params = array_merge($this->params,$params);

        $this->sessionIndex = $this->params['form_name'];
        $this->arFormInputs = !empty($_SESSION[$this->sessionIndex.'_form']['data']) 
                                    ? $_SESSION[$this->sessionIndex.'_form']['data'] 
                                    : array();
        $this->arResponse   = !empty($_SESSION[$this->sessionIndex.'_form']['response']) 
                                    ? $_SESSION[$this->sessionIndex.'_form']['response']
                                    : array('result' => 0,'data' => array(), 'msg' => '');
        $this->redirectUrl  = !empty($_SESSION[$this->sessionIndex.'_form']['redirect_url']) 
                                    ? $_SESSION[$this->sessionIndex.'_form']['redirect_url']
                                    : '';

        $this->isSubmitted  = !empty($_SESSION[$this->sessionIndex.'_form']['has_post']) 
                                    ? $_SESSION[$this->sessionIndex.'_form']['has_post']
                                    : 0;

        $path_to_xml_file   = isset($this->params['path_to_xml_file']) && $this->params['path_to_xml_file'] !== null ? $this->params['path_to_xml_file'] : getcwd().'/'.$this->sessionIndex.'_fields.xml';

        if(file_exists($path_to_xml_file)):
            $this->setObjXml($path_to_xml_file,true);
        endif;

        if($this->useCsrf === true):
            $this->csrf = new \Utility\csrf(hash('sha256',$this->sessionIndex));
        endif;
    }


    /**
     * DISTRUTTORE
     * Memorizzo di dati in sessione
     * @param void
     * @return void
     */
    public function __destruct()
    {
        $_SESSION[$this->sessionIndex.'_form'] = array(
                                                    'data'          => $this->arFormInputs
                                                    ,'response'     => $this->arResponse
                                                    ,'redirect_url' => $this->redirectUrl
                                                    ,'has_post'     => $this->isSubmitted
                                                );
    }


    /**
     * setObjXml()
     * Imposto l'oggetto xml
     * @param string xmlStr: stringa xml con le informazioni sui campi del form
     * @param bool is_path_to_file: la stringa sopra è un percorso ad un file xml?
     * @return void
     */
    public function setObjXml($xmlStr,$is_path_to_file = false)
    {
        $this->objXml           = $is_path_to_file !== false ?  new SimpleXMLElement($xmlStr,NULL,TRUE) : new SimpleXMLElement($xmlStr);
        $this->tableName        = $this->objXml->attributes()->table ? "{$this->objXml->attributes()->table}" : 'contatti';
        $this->uploadBaseDir    = $this->objXml->attributes()->upload_dir ? "{$this->objXml->attributes()->upload_base_dir}" : '/file_public';

        $this->useCsrf          = $this->objXml->attributes()->csrf 
                                            ? ($this->objXml->attributes()->csrf === 'false' ? false : true)
                                            : true;
    }


    /**
     * getArFormInputs()
     * Restituisco l'array con tutte le chiavi valore
     * @param void
     * @return array
     */
    public function getArFormInputs()
    {
        return $this->arFormInputs;
    }


    /**
     * prepareInputs()
     * Popolo l'array con tutte le informazioni sugli inputs in modo da manipolarle
     * @param bool force Sovrascrivo le impostazioni eventualmente modificate
     * @return void
     */
    public function prepareInputs(bool $force = false)
    {

        if(!$this->objXml):
            throw new \Exception('['.__METHOD__.'] Non è presente alcun oggetto xml con i campi del form <code>'.$this->sessionIndex.'</code>',E_USER_ERROR);
            return;    
        endif;

        if(!$this->inputsPrepared || $force === true):

            $this->arFormInputs = array(); // resetto input

            foreach($this->objXml->children() as $input):

                $inputName      = "{$input['name']}";           // converto il nome da oggetto a stringa

                foreach($input[0]->attributes() as $field => $value):
                    switch($field):
                        case 'mailfield':
                            $this->arFormInputs[$inputName]["{$field}"]    = !"{$value}" ? true : false;
                            break;
                        default:
                            $this->arFormInputs[$inputName]["{$field}"]     = "{$value}";
                    endswitch;
                endforeach;
                $this->arFormInputs[$inputName]['compare_field']= $input['compare_field'] ? "{$input['compare_field']}" : "";
                if ((bool)$input['ignore_label'] !== true) :
                    $this->arFormInputs[$inputName]['label']        = $input['label'] !== NULL ?  $this->Lang->returnT("{$input['label']}") : $this->Lang->returnT('label_'.$inputName);
                else :
                    $this->arFormInputs[$inputName]['label'] = '';
                endif;
                $this->arFormInputs[$inputName]['upload_dir']   = $input['upload_dir'] !== NULL ? $_SERVER['DOCUMENT_ROOT'].$this->uploadBaseDir."/{$input['upload_dir']}" : $_SERVER['DOCUMENT_ROOT'].$this->uploadBaseDir.'/'.$inputName;
                if(isset($this->arFormInputs[$inputName]['attachments'])):
                    $this->arAttachments[$inputName] = $this->arFormInputs[$inputName]['attachments'];
                endif;

            endforeach;

            $this->inputsPrepared = true;
        endif;

    }


    /**
     * freshInputs()
     * Popolo l'array con tutte le informazioni sugli inputs in modo da manipolarle
     * @param bool force Sovrascrivo le impostazioni eventualmente modificate
     * @return void
     */
    public function freshInputs(){
        $this->prepareInputs(true);
    }


    /**
     * getValue()
     * Restituisce il valore di un campo dato il nome del campo.
     * Restituisce una stringa vuota se il campo non esiste
     * @param string l'attributo 'name' del campo di cui recuperare il valore
     * @return string
    */
    public function getValue($inputName)
    {
        return strlen($inputName) && array_key_exists($inputName, $this->arFormInputs) && isset($this->arFormInputs[$inputName]['value']) ? $this->arFormInputs[$inputName]['value'] : '';
    }


    /**
     * setValue()
     * Imposta il valore di un campo dati
     * @param string l'attributo 'name' del campo di cui impostare il valore
     * @param string il valore da impostare
     * @return bool
     */
    public function setValue($inputName,$newValue)
    {
        return $this->arFormInputs[$inputName]['value'] = $newValue;
    }


    /**
     * setMandatory()
     * Imposto un campo come obbligatorio
     * @param string l'attributo 'name' del campo di cui impostare il valore
     * @param int obbligatorio
     * @return bool
     */
    public function setMandatory(string $name = '',int $mandatory = 1){
        if(!array_key_exists($name,$this->arFormInputs)):
            throw new \Exception('['.__METHOD__.'] Campo non esistente '.$name,E_USER_ERROR);
        endif;
        return $this->arFormInputs[$name]['obbl'] = $mandatory;
    }


    /**
     * getError()
     * Restituisce la stringa con il testo dell'errore dato il nome del campoo stringa vuota se il campo non esiste
     * @param string l'attributo 'name' del campo di cui recuperare il valore
     * @return string
     */
    public function getError($inputName)
    {
        return strlen($inputName) && array_key_exists($inputName, $this->arFormInputs) ? $this->arFormInputs[$inputName]['error'] : '';
    }


    /**
     * hasError()
     * -> Se viene passato $inputName, verifica se il campo ha un errore
     * -> Se non viene passato un parametro, verifica se il form ha errori
     * @param string | null l'attributo 'name' del campo di cui recuperare il valore
     * @return bool
     */
    public function hasError($inputName = null)
    {
        
        if($inputName !== null):
            return array_key_exists($inputName, $this->arFormInputs) && isset($this->arFormInputs[$inputName]['error']) && strlen($this->arFormInputs[$inputName]['error']) ? true : false;
        else:
            return $this->arResponse['result'] === 0 && strlen($this->arResponse['msg']) ? true : false;
        endif;
    }


    /**
     * echoErrors()
     * fa un echo della stringa degli errori
     * @param void
     * @return void
     */
    public function echoErrors()
    {
        echo $this->arResponse['result'] === 0 && strlen($this->arResponse['msg']) ? $this->arResponse['msg'] : '';
    }


    /**
     * echo del campo input da inserire nel 
     * @param bool regenerate rigenero il token?
     * @return output
     */
    public function csrf($regenerate = false,$return = false){
        if($return){
            return array(
                'id'        => $this->csrf->getTokenId($regenerate)
                ,'value'    => $this->csrf->getToken($regenerate)
            );
        } else {
            echo '  <input type="hidden" name="'.$this->csrf->getTokenId($regenerate).'" value="'.$this->csrf->getToken($regenerate).'">'.PHP_EOL;
        }
    }


    /**
     * setPasswordSetting()
     * sovrascrive un parametro della password
     * @param string nome del parametro da sovrascrivere
     * @param string valore del parametro da sovrascrivere
     * @return object $this
     */
    public function setPasswordSetting($name,$value){
        switch($name):
            case 'mode':
                if($value):
                    $this->passwordSettings['mode'] = $value;
                else:
                    trigger_error('['.__METHOD__.'] Parametro '.$name.' non può essere vuoto',E_USER_WARNING);
                endif;
                break;
            case 'minlength':
                $this->passwordSettings['minlength'] = $value > 0 && $value < $this->passwordSettings['maxlength'] ? $value : $this->passwordSettings['minlength'];
                break;
            case 'maxlength':
                $this->passwordSettings['maxlength'] = $value && $value > $this->passwordSettings['minlength'] ? $value : $this->passwordSettings['maxlength'];
                break;
            default:
                trigger_error('['.__METHOD__.'] Parametro '.$name.' inesistente',E_USER_WARNING);
        endswitch;
        return $this;
    }


    /**
     * validate()
     * Valida il form sulla base delle impostazioni ottenute dal file xml
     * @param void
     * @return void
     */
    public function validate()
    {
        
        $this->arResponse['msg'] = '';                      // resetto il messaggio con gli errori
        $this->isSubmitted = 1;

        if(!$this->csrf->checkValid('post')):
            throw new \Exception('['.__METHOD__.'] Possibile attacco malevono (CSRF)',E_USER_ERROR);
        endif;
        
        // preparazione input
        $this->prepareInputs();
        
        foreach($this->arFormInputs as $name => &$input):
            
            // obbligatorietà condizionale
            // obbligatorio se nomecampo, se !nomecampo
            // obbligatorio se nomecampo_AND_altronomecampo, se nomecampo_OR_altronomecampo
            if(!empty($input['obbl_if'])):
                
                // modalità di associazione
                $arCompare = array('AND','OR');
                
                // ottengo tutti i possibili campi confronto
                $arObblIf = preg_split('/\_('.implode('|',$arCompare).')\_/',$input['obbl_if'],NULL,PREG_SPLIT_DELIM_CAPTURE);

                $operatorCompare = NULL;

                /**
                 * ciclo l'array ottenuto
                 * l'obbligatorietà sarà definita di volta in volta sulla base dell'operatore definito di volta in volta nell'iterazione precedente
                 */
                foreach($arObblIf as $k => $obbl):

                    // se è uno degli operatori di confronto
                    if(array_search($obbl,$arCompare) !== false):
                        $operatorCompare = $obbl;
                    else:
                        // chiave dell'elemento di confronto
                        $post_k = substr($obbl,0,1) == '!' ? (string)substr($input['obbl_if'],1) : (string)$input['obbl_if'];

                        // definisco l'obbligatorietà
                        $thisCond = 
                            substr($obbl,0,1) == '!'
                            ? (isset($_POST[$post_k]) && strlen($_POST[$post_k]) > 0 ? 0 : 1)   // obbligatorio se non presente $post_k
                            : (isset($_POST[$post_k]) && strlen($_POST[$post_k]) > 0 ? 1 : 0);  // obbligatorio se presente $post_k

                        if($k == 0):    // la prima condizione sovrascrive quanto impostato nell'xml
                            $input['obbl'] = $thisCond;
                        else:
                            // di seguito ci si basa sull'oberatore di confronto ottenuto nell'iterazione precedente
                            switch($operatorCompare):
                                case 'OR':
                                    $input['obbl'] = $thisCond || $input['obbl'];
                                default:
                                    $input['obbl'] = $thisCond && $input['obbl'];
                            endswitch;
                        endif;
                    endif;
                endforeach;
            endif;

            $input['value'] = !empty($_POST[$name]) ? $_POST[$name] : '';
            
            if(!isset($input['type'])): 
                trigger_error('['.__METHOD__.'] All\'input manca l\'attributo type <pre>'.print_r($input,1).'</pre>',E_USER_WARNING);
                continue; 
            endif;

            switch($input['type']):                     // a seconda del tipo di campo imposto gli elementi diversamente
            
                case 'email':
                    $input['error'] = $this->_check_mail($input['value'], $input['label'], $input['obbl']);
                    break;
                    
                case 'number':
                    $input['error'] = $this->_check_moduli($input['value'], 1, $input['label'], $input['obbl']);
                    break;
                    
                case 'tel':
                    $input['error'] = $this->_check_moduli($input['value'], 5, $input['label'], $input['obbl']);
                    break;
                    
                case 'url':
                    $input['error'] = $this->_check_moduli($input['value'], 6, $input['label'], $input['obbl']);
                    break;
                    
                case 'date':
                    $GLOBALS['format'] = $format = isset($input['format']) ? $input['format'] : 'dd/mm/aaaa';
                    $input['error'] = $this->_check_date($input['value'], $format, $input['label'], $input['obbl']);
                    break;
                    
                case 'file': 

                    // verifico che esista e casomai includo la classe per il caricamento dei files
                    try {

                        $this->File = new \Utility\FixFile();
                        $allowed = strlen($input['allowed']) ? explode(',',$input['allowed']) : array();
                        $input['value'] = !empty($_FILES[$name]['name']) ? $_FILES[$name]['name'] : '';
                        if(!strlen($input['value'])) $input['value'] = $_POST[$name.'_filename'];
                        $input['error'] = !empty($_POST[$name.'_filesize']) ? '<strong>'.$input['label'].'</strong>'.$this->Lang->returnT('error_form_filesize').number_format(($this->max_file_size/1048576),2).'MB <br>' : '';
                        $input['error'] .= $this->_check_file($input['value'], $input['label'], $allowed, $input['obbl']);
                        $input['value'] = $this->File->fileName($input['value']);

                    } catch(\Exception $e){
                        trigger_error('['.__METHOD__.'] Errore FixFile: '.$e->getMessage(),E_USER_WARNING);
                        $GLOBALS['sitemail'] = $sitemail = $this->arMailInfo['SITE_MAIL'];
                        $input['error'] = $this->Lang->returnT('error_form_insert_data');
                    }
                    break;
                    
                case 'privacy':
                    $input['error'] = $this->_check_privacy($input['value'], $input["label"]);
                    break;

                case 'password':
                    $arParams = array(
                        'pass'  => $input['value']
                        ,'obbl' => $input['obbl']
                        ,'label'=> $input['label']
                        ,'minlength' => (array_key_exists('minlength',$input) && $input['minlength'] ? $input['minlength'] : $this->passwordSettings['minlength'])
                        ,'maxlength' => (array_key_exists('maxlength',$input) && $input['maxlength'] ? $input['maxlength'] : $this->passwordSettings['maxlength'])
                        ,'mode' => (array_key_exists('mode',$input) && $input['mode'] ? $input['mode'] : $this->passwordSettings['mode'])
                    );
                    if(array_key_exists('compare_field',$input) && strlen($input['compare_field'])):
                        $arParams['compare'] = true;
                        $arParams['pass2'] = $_POST[$input['compare_field']];
                    endif;
                    $input['error'] = $this->_check_password($arParams);
                    break;

                default:
                    $input['error'] = $this->_check_moduli($input['value'], 3, $input['label'], $input['obbl']);
            endswitch;

            if(array_key_exists('tabfield',$input) && !empty($input['tabfield'])): 
                $this->arSqlInputs[$input['tabfield']] = $input['value'];
            endif;
            $this->arResponse['msg'] .= $input['error'];
        endforeach;

        $this->redirectUrl = !empty($_POST['redirect']) ? $_POST['redirect'] : $this->Lang->returnL($this->sessionIndex);
        $this->arResponse['data'] = $this->arFormInputs;

    }


    /**
     * _check_moduli()
     * Funzione per validare valori di tipo
     * -> nomi
     * -> numero
     * -> url
     * -> testo generico
     * -> nome file
     * -> telefono
     * @param string valore del campo input
     * @param string tipo di campo
     * @param string etichetta (label) del campo
     * @param int il campo è obbligatorio (0|1)
     * @return void | string se ci sono errori
     */
    public function _check_moduli($form_post, $tipo, $nome_campo, $obbligatorio = 0) 
    { 

        $da_cercare = array (
            0 => '([\+\*\?\[\^\]\$\{\}\=\!\<\>\|\:@])'              // nomi 
            ,1 => '([^0-9\+])'                                      // numeri
            ,2 => '([^0-9a-zA-Z\.\-\(\)\s])'                        // indirizzi    
            ,3 => '([\<\>])'                                        // vari
            ,4 => '([\+\*\?\[\^\]\$\{\}\=\!\<\>\|\:@])'             // file
            ,5 => '([^0-9\.\+\-\/\s])'                              // telefono
        ) ;
        
        if ($obbligatorio > 0 && empty($form_post)):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_mandatory").'<br />';
        endif;
        
        if(preg_match($da_cercare[$tipo], $form_post)):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_tipo_".$tipo).'<br />';
        endif;
                
    }


    /**
     * _check_file()
     * Funzione per verificare se il file è di un tipo consentito
     * @param string valore del campo input
     * @param string etichetta (label) del campo
     * @param array contiene i tipi di file consentiti
     * @param int il campo è obbligatorio (0|1)
     * @return void | string se ci sono errori
     */
    public function _check_file($form_post, $nome_campo, $allowed = array(), $obbligatorio = 0) 
    { 

        $form_post = basename(str_replace('\\','/',$form_post));

        if ($obbligatorio > 0 && empty($form_post)) {
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_mandatory").'<br />';
            exit;
        }
        
        if(preg_match('([\+\*\?\[\^\]\$\{\}\=\!\<\>\|\:@])', $form_post)):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_tipo_4").'<br />';
            exit;
        endif;

        if(array_search(pathinfo($form_post, PATHINFO_EXTENSION), $allowed) === false):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_tipo_7").implode(', ', $allowed).'<br />';
            exit;
        endif;

    } 


    /**
     * _check_mail()
     * Funzione per verificare la correttezza dell'indirizzo email
     * @param string valore del campo input
     * @param string etichetta (label) del campo
     * @param int il campo è obbligatorio (0|1)
     * @return void | string se ci sono errori
     */
    public function _check_mail($m, $nome_campo, $obbligatorio) 
    {

        if ($obbligatorio > 0 && empty($m)):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_mandatory").'<br />';
            exit;
        endif;

        
        if(!empty($m) && !preg_match("/^[\w\-_\.]+@[\w\-_\.]+\.[a-zA-Z]{2,}$/", $m)):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_mail_0").'<br />';
        endif;
    }


    /**
     * _check_date()
     * Funzione per verificare la correttezza della data
     * @param string valore del campo input
     * @param string formato della data
     * @param string etichetta (label) del campo
     * @param int il campo è obbligatorio (0|1)
     * @return void | string se ci sono errori
     */
    public function _check_date($form_post, $format, $nome_campo, $obbligatorio = 0) 
    {
        $format_reg_expr = preg_replace('#\/#','\/',$format);   // trasformo il formato in espressione regolare
        $format_reg_expr = preg_replace('#[dma]#','\d',$format_reg_expr);

        if ($obbligatorio > 0 && empty($form_post)):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_mandatory").'<br />';
            exit;
        endif;
        
        if(!empty($form_post) && !preg_match("#".$format_reg_expr."#", $form_post)):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_date_format").'<br />';
        endif;
    }


    /**
     * _check_privacy()
     * Funzione per verificare l'accettazione della privacy
     * @param string valore del campo input
     * @param string etichetta (label) del campo
     * @return void | string se ci sono errori
     */
    public function _check_privacy($checkbox, $label = '') 
    {
        if (empty($checkbox)) :
            return $label ? '<strong>'.$label.'</strong>: '.$this->Lang->returnT("error_form_mandatory").'<br>' : $this->Lang->returnT("error_form_privacy").'<br>';
        endif;
    }


    /**
     * _check_password()
     * Verifica di password
     * @request Fixstring per validare il codice
     * @param array con tutti i parametri da utilizzare
     * @return void | string in caso di errore
     */
    public function _check_password ($arParams = array()) 
    {   
        $pass = array_key_exists('pass',$arParams) ? $arParams['pass'] : '';
        $label = array_key_exists('label',$arParams) ? $arParams['label'] : '';
        $obbl = array_key_exists('obbl',$arParams) ? $arParams['obbl'] : '';
        $minlength = $arParams['minlength'];
        $maxlength = $arParams['maxlength'];
        $mode = $arParams['mode'];
        $compare = array_key_exists('compare',$arParams) ? (bool)$arParams['compare'] : false;

        try {

            $this->FixString = new \Utility\FixString();
            $response = $this->FixString->codeValidator($pass,$minlength,$maxlength,$mode);
            if(true !== $response):
                switch($response['code']):
                    case 'empty':
                        if($obbl):
                            return '<strong>'.$label.'</strong>: '.$this->Lang->returnT('error_form_password_empty').' <br>';
                        endif;
                        break;
                    case 'spaces':
                        return '<strong>'.$label.'</strong>: '.$this->Lang->returnT('error_form_password_s').' <br>';
                        break;
                    case 'minlength':
                    case 'maxlength':
                        return '<strong>'.$label.'</strong>: '.$this->Lang->returnT('error_form_password_'.$response['code'],array('n' => $response['data'])).' <br>';
                        break;
                    case 'mode':
                        $arMode = str_split($response['data']);
                        $explain = [];
                        foreach($arMode as $M):
                            switch($M):
                                case 'l':
                                    $explain[] = $this->Lang->returnT('error_form_password_small_caps',array('n' => 1));
                                    break;
                                case 'L':
                                    $explain[] = $this->Lang->returnT('error_form_password_all_caps',array('n' => 1));
                                    break;
                                case 'n':
                                case 'N':
                                    $explain[] = $this->Lang->returnT('error_form_password_numbers',array('n' => 1));
                                    break;
                                case 'c':
                                    $explain[] = $this->Lang->returnT('error_form_password_special',array('list' => '-.!()?'));
                                    break;
                                case 'C':
                                    $explain[] = $this->Lang->returnT('error_form_password_special',array('list' => '|-_.:!()/\?*'));
                                    break;
                            endswitch;
                        endforeach;
                        return '<strong>'.$label.'</strong>: '.$this->Lang->returnT('error_form_password_mode',array('explain' => implode(', ',$explain))).' <br>';
                        break;
                    default: // di base ritorno un errore e avverto admin
                        return '<strong>'.$label.'</strong>: '.$this->Lang->returnT('error_form_generic').' <br>';
                endswitch;
            endif;

        } catch(\Exception $e){
            trigger_error('['.__METHOD__.'] Errore FixString->codeValidator(), non è stato possibile validare il formato della password: '.$e->getMessage(),E_USER_WARNING);
        }

        if(true === $compare):
            $pass2 = array_key_exists('pass2',$arParams) ? $arParams['pass2'] : '';
            if (empty($pass2) ):
                return '<strong>'.$label.'</strong>: '.$this->Lang->returnT("error_form_password_1").'.<br />';
            elseif ($pass != $pass2):
                return  '<strong>'.$label.'</strong>: '.$this->Lang->returnT("error_form_password_2").'<br />';
            endif;
        endif;
    }


    /**
     * _check_captcha()
     * Validazione captcha
     * @param string valore del campo input
     * @param string etichetta (label) del campo
     * @return void | string in caso di errore
     */
    public function _check_captcha($form_post,$nome_campo) 
    {

        if (empty($form_post)):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT("error_form_mandatory").'<br />';
            exit;
        endif;
        
        if(strtolower($form_post) !== strtolower($_SESSION[$this->sessionIndex.'_captcha']['code'])):
            return '<strong>'.$nome_campo.'</strong>: '.$this->Lang->returnT('error_form_tipo_9').'<br />';
        endif;
    }


    /**
     * uploadAttachments()
     * Imposto il caricamento dei files
     * @param string directory di caricamento dei files
     * @param string name del file da caricare
     * @param int numero di bytes massimo caricabile
     * @return void
     */
    public function uploadAttachments($upload_dir = NULL, $inputName = NULL,$max_file_size = NULL) 
    {

        if($upload_dir === NULL) return false;
        if($inputName !== NULL && !isset($this->arFormInputs[$inputName])) return false;
        if($max_file_size !== NULL && is_int($max_file_size)) $this->max_file_size = $max_file_size;

        // se ho un file
        if(sizeof($_FILES > 0)):
            
            $this->attachmentsDir = $upload_dir;
            $GLOBALS['sitemail'] = $sitemail = $this->arMailInfo['SITE_MAIL'];

            // verifico che esista e casomai includo la classe per il caricamento dei files
            if(!$this->File):
                try {
                    $this->File = new \Utility\FixFile();                    
                } catch(\Exception $e){
                    trigger_error('['.__METHOD__.'] Errore FixFile: '.$e->getMessage(),E_USER_WARNING);
                    $this->arResponse['msg'] = $this->Lang->returnT('error_form_insert_data').'<br>'.$this->Lang->returnT('errore_parsing_file');
                    return false;
                }
            endif;

            if(strlen($inputName)):
                if(!isset($_FILES[$inputName])):
                    $this->arResponse['msg'] = $this->Lang->returnT('error_form_insert_data').'<br>'.$this->Lang->returnT('error_form_file_upload');
                    return false;
                endif;
                return $this->uploadFile($_FILES[$inputName],$inputName);
            else:
                if(count($_FILES)):
                    foreach($_FILES as $k => $FILE):
                        $this->uploadFile($FILE,$k);
                    endforeach;
                    return true;
                endif;
            endif;

        endif;
    }


    /**
     * uploadFile()
     * Carico i singoli files
     * @param array file da caricare ($_FILES[x])
     * @param string name del file da caricare
     * @return void
     */
    private function uploadFile($FILE,$inputName){
        
        $nome_file =            $FILE['name'];
        $nome_file_tmp =        $FILE['tmp_name'];
        $peso_file_caricato =   $FILE['size'];
        
        if($nome_file):
            $GLOBALS['sitemail'] = $sitemail = $this->arMailInfo['SITE_MAIL'];

            # VERIFICO LA DIMENSIONE
            if($peso_file_caricato >= $this->max_file_size || $peso_file_caricato = 0):
                $this->arResponse['msg'] = $nome_file.$this->Lang->returnT('error_form_filesize').number_format($this->max_file_size / 1048576, 2).' MB';
                return false;
            endif;

            # RINOMINO IL FILE NEL CASO ESISTA GIÀ
            $nome_file_finale = $this->File->fileRename($this->attachmentsDir.'/'.$nome_file);
            $path_e_file_finale = $this->attachmentsDir.'/'.$nome_file_finale;

            if (is_array($nome_file_finale)):
                $this->arResponse['msg'] = $this->Lang->returnT('error_form_filename').'<br>'.$this->Lang->returnT('error_form_insert_data');
                return false;
            endif;

            if($this->File->fileUpload($nome_file_tmp,$path_e_file_finale) == false):
                $this->arResponse['msg'] = $this->Lang->returnT('error_form_file_upload').'<br>'.$this->Lang->returnT('error_form_insert_data');
                return false;
            endif;

            $this->arAttachments[$inputName] = 
            $this->arFormInputs[$inputName]['attachments'] = 
            $path_e_file_finale;
            return true;
        else:
            return false;
        endif;
    }


    /**
     * clearAttachments()
     * Cancello cli allegati caricati
     * @param bool cancello gli allegati una volta inviata la mail? -> nel caso la directory sia la root li cancello sempre
     * @return void
     */
    public function clearAttachments($always = false) 
    {

        if($this->attachmentsDir == '' || $this->attachmentsDir == '/') $always = true;
        if($always !== false):
            foreach($this->arAttachments as $FileAttached):
                $this->File->fileDelete($FileAttached);
            endforeach;
        endif;

    }


    /**
     * isAjax()
     * Sto inviando il form mediante ajax?
     * @param void
     * @return bool
     */
    public function isAjax()
    {
        return !empty($_POST['is_ajax']) && $_POST['is_ajax'] > 0 ? true : false;
    }


    /**
     * hasPost()
     * Il form è già stato inviato almeno una volta?
     * @param void
     * @return bool
     */
    public function hasPost()
    {
        return $this->isSubmitted ? true : false;
    }


    /**
     * errorRedirect()
     * Reindirizzamento in caso di errore.
     * -> se ajax faccio un echo della risposta
     * -> altrimenti reindirizzo alla pagina del form
     * @param void
     * @return void
     */
    public function errorRedirect()
    {
        if($this->isAjax()):
            echo json_encode($this->arResponse);
        else:
            header('Location: '.$this->redirectUrl);
        endif;
        exit;
    }


    /**
     * successRedirect()
     * Reindirizzamento in caso di successo.
     * -> se ajax faccio un echo della risposta
     * -> altrimenti reindirizzo alla pagina di conferma (sessionIndex+'_invio')
     * @param bool preserve_msg mantengo il messaggio (dovrò cancellarlo manualmente dopo): utile nel caso di redirect js
     * @param bool preserve_redirect mantengo l'url di redirezione (dovrò cancellarlo manualmente dopo)
     * @return void
     * @output void | string json message 
     */
    public function successRedirect(bool $preserve_msg = false, bool $preserve_redirect = false)
    {
        $this->arResponse['data'] = array();

        $this->isSubmitted = 0;
        if($this->isAjax()):
            $r = $this->arResponse;
            if($preserve_msg === false): $this->unsetResponse(); endif;
            if($preserve_redirect === false): $this->unsetRedirectUrl(); endif;
            echo json_encode($r);
        else:
            header('Location: '.$this->redirectUrl);
        endif;
        exit;
    }


    /**
     * getAjScript()
     * Restituisce lo script ajax
     * @param void
     * @return string
     */
    public function getAjScript()
    {
        return $this->ajScript;
    }


    /**
     * createMail()
     * Crea il corpo della mail da inviare.
     * @param void
     * @return string l'html della mail
     */
    public function createMail()
    {
        return $this->mailHead.$this->mailBody.$this->mailFoot;
    }


    /**
     * getMailHead()
     * Ritorna l'intestazione della mail
     * @param void
     * @return string
     */
    public function getMailHead($serverName = null){
        $serverName = $serverName === null ? 'http://'.$_SERVER['SERVER_NAME'] : $serverName;
        if($this->mailHead === null):
            $this->mailHead = '
                <!DOCTYPE html>
                <html lang="'.$this->Lang->lgSuff.'">
                <head>
                <meta charset="utf-8">
                <title>Documento senza titolo</title>
                <style type="text/css">
                body{
                    margin: 0px;
                    padding: 0px;
                    font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;
                    font-size: 12px;
                    height: 100%;
                    color: #000000;
                    text-align: left;
                }
                html{height: 100%;}
                table{
                    margin: 0px;
                    padding: 0px;
                    font-family: Arial, Helvetica, sans-serif, Verdana, Geneva;
                    font-size: 11px;
                    color: #000000;
                    text-align: left;
                }
                .contact_data .dispari { background: #DDDDDD; }
                .contact_data td {padding: 10px; vertical-align: top; }
                .contact_data td.title { font-weight: bold; text-align: right; }

                </style>
                </head>

                <body>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td align="center" valign="top">
                            <table width="96%" border="0" align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><img src="'.$serverName.'/immagini_layout/logo_mail.png" alt="'.$this->arMailInfo['SITE_NAME'].'" /></td>
                                </tr>
                                <tr>
                                    <td height="15">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>
                                        <table class="contact_data" width="100%" border="0" cellspacing="0" cellpadding="4" style="border: 1px solid '.$this->arMailInfo['SITE_COLOR'].';">
                                            <tr>
                                                <td height="40" colspan="2" bgcolor="'.$this->arMailInfo['SITE_COLOR'].'" style="color: #FFFFFF; font-size: 16px;">&nbsp; '.$this->arMailInfo['MAIL_TITLE'].'</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                            '.PHP_EOL;
        endif;
        return $this->mailHead;
    }


    /**
     * setMailHead()
     * Imposta l'intestazione della mail
     * @param string
     * @return void
     */
    public function setMailHead($str = ''){
        $this->mailHead = $str;
    }


    /**
     * getMailFoot()
     * Ritorna il piè pagina della mail della mail
     * @param void
     * @return string
     */
    public function getMailFoot(){
        if($this->mailFoot === null):
            $this->mailFoot = '                 </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                </body>
                </html>'.PHP_EOL;
        endif;
        return $this->mailFoot;
    }


    /**
     * setMailFoot()
     * Imposta il piè pagina della mail della mail
     * @param string
     * @return void
     */
    public function setMailFoot($str = ''){
        $this->mailFoot = $str;
    }


    /**
     * getMailBody()
     * Ritorna il corpo della mail
     * @param void
     * @return string
     */
    public function getMailBody(){
        if($this->mailBody === null):
            $this->mailBody = '         <table class="table_data">'.PHP_EOL;
            $counter = 0;
            foreach($this->arFormInputs as $I):
                if(array_key_exists('mailfield',$I) && $I['mailfield'] === false): 
                    continue;
                endif;
                $this->mailBody .= '        <tr class="'.(is_int($counter/2) ? 'pari' : 'dispari').'">
                                                <td class="title">'.$I['label'].'</td>
                                                <td>'.$I['value'].'</td>
                                            </tr>'.PHP_EOL;
                $counter++;
            endforeach;
            $this->mailBody .= '        </table>'.PHP_EOL;
        endif;
        return $this->mailBody;
    }


    /**
     * setMailBody()
     * Imposta il corpo della mail
     * @param string
     * @return void
     */
    public function setMailBody($str = ''){
        $this->mailBody = $str;
    }



    /**
     * unsetInputs()
     * Resetto tutti i valori di input
     * @param void
     * @return bool
     */
    public function unsetFormInputs()
    {
        return $this->arFormInputs = array();
    }

    /**
     * unsetResponse()
     * Resetto la risposta
     * @param void
     * @return bool
     */
    public function unsetResponse()
    {
        return $this->arResponse = array('result' => 0, 'msg' => '', 'data' => array());
    }

    /**
     * unsetRedirectUrl()
     * Resetto l'url redirect
     * @param void
     * @return bool
     */
    public function unsetRedirectUrl()
    {
        return $this->redirectUrl = '';
    }

}

