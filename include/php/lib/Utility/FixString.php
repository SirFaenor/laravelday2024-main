<?php

namespace Utility;
class FixString {

	private $HTMLPurifierPath;

	/*
	* ======================================================================
	*
	*  __construct() 
	*  -----------
	*
	* ======================================================================
	*/
	public function __construct($HTMLPurifierPath) {

		$this->HTMLPurifierPath = $HTMLPurifierPath;

	}

	//////////////////////////////////////////////////////////////
	// == FIX STRINGA ==
	public function fix($string, $htmlType = 1){
		
		$string = trim($string);
	
		require_once($this->HTMLPurifierPath);
		$config = \HTMLPurifier_Config::createDefault();
		
	
		////////////////
		// Tolgo il backslash, altrimenti HTMLPurifier elimina tutto l'attributo
		$string = str_replace('\"', '"', $string);
		////////////////


		// Switch nel caso si necessiti di specificare nuove configurazioni
		switch ($htmlType) :

			default : {
				$config->set('HTML.AllowedElements', "table,caption,tr,td,tbody,thead,tfoot,th,em,u,strong,br,p,a,ul,li,ol,span,h1,h2,h3,h4,h5,h6,sup,sub,iframe");
			}
		endswitch;

		$config->set('Attr.AllowedRel', 'blank');
		$config->set('Core.EscapeInvalidChildren', 'true');
		$config->set('Core.EscapeInvalidTags', 'true');
		$config->set('Attr.AllowedFrameTargets', '_blank');
		$config->set('AutoFormat.RemoveEmpty.RemoveNbsp', 'true');
		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube(?:-nocookie)?.com/embed/|player.vimeo.com/video/|www.slideshare.net/slideshow)%'); //allow YouTube and Vimeo
			
		$config->set('AutoFormat.AutoParagraph', false);
		
		$purifier = new \HTMLPurifier($config); // Inizializzo
		$string = $purifier->purify($string); // Purifico
	
		$string = htmlentities($string,  ENT_QUOTES, 'utf-8');
	
		$string = addcslashes($string, "=");
		$string = addcslashes($string, "[");
		$string = addcslashes($string, "]");
		$string = addcslashes($string, "#");
		
		// Ripristino alcuni elementi
		$string = str_replace('&quot;', '\"', $string); // Se abilitato non converte il "quot", crea problemi nei campi tipo text
		$string = str_replace('&amp;', '&', $string);
		$string = str_replace('&lt;', '\<', $string);
		$string = str_replace('&gt;', '\>', $string);
		
	  	return $string;
	}
	//////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////
	// == Testo limitato PAROLE ==
	public function maxWord($string, $max = NULL, $strip = 1){

		if($strip):
			$string_x = strip_tags($string);
		endif;
		$arStringX 	= explode(' ', $string_x);
		$string2 	= is_int($max) && count($arStringX) > $max ? ' &hellip;' : ''; 
		$arStringX 	= is_int($max) ? array_slice($arStringX,0,$max) : $arStringX;
		$string2 	= implode(' ',$arStringX).$string2;
		
		return trim($string2);
	
	}
	//////////////////////////////////////////////////////////////

	
	//////////////////////////////////////////////////////////////
	// == Escape semplice, senza Purifier ==
	public function escape($string,$connection) {
		return mysqli_real_escape_string($connection,$string);	
	}
	//////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////////////////
	// == Testo limitato LETTER ==
	function maxLetter($string, $max){

		return strlen($string) > $max ? substr($string, 0, $max).'&hellip;' : trim($string);

	}
	//////////////////////////////////////////////////////////////

	/**
	 * codeValidator()
	 * verifica una password/codice sulla base dei parametri passati
	 * @param string password to validate
	 * @param int min password length
	 * @param int max password length
	 * @param string what caracters code must contain
	 * @return true || string with error code
	 */
	public function codeValidator($code, $minlength = 1, $maxlength = 50, $mode = 'Ln'){
		if(!$code):	// password empty
			return array('code' => 'empty');
		endif;

		if(strlen($code) < $minlength): // shorter than min lentgh
			return array('code' => 'minlength','data' => $minlength);
		endif;

		if(strlen($code) > $maxlength): // greater than max lentgh
			return array('code' => 'maxlength','data' => $maxlength);
		endif;

		if(preg_match('/[ \s]/',$code)): // greater than max lentgh
			return array('code' => 'spaces');
		endif;

		// pattern validation
		$response = true;

		// se ha un carattere non contemplato
		if(preg_match('/[^lLnNcC]/',$mode)):
			trigger_error('['.__METHOD__.'] Il pattern per validare questa stringa non è corretto <code>'.$mode.'</code>',E_USER_WARNING);
			$mode = 'Ln';
		endif;

		$arMode = str_split($mode);
		foreach($arMode as $v):
			switch($v):
				case 'l':
					if(!preg_match('/[a-z]/',$code)):
						$response = false;
					endif;
				break;
				case 'L':
					if(!preg_match('/[a-z]/',$code)):
						$response = false;
					endif;
					if(!preg_match('/[A-Z]/',$code)):
						$response = false;
					endif;
				break;
				case 'n':
				case 'N':
					if(!preg_match('/[0-9]/',$code)):
						$response = false;
					endif;
					break;
				case 'c':
					if(!preg_match('/[\-\.!\(\)\?]/',$code)):
						$response = false;
					endif;
					break;
				case 'C':
					if(!preg_match('/[|\-_\.:!\(\)\/\\\\?\*]/',$code)):
						$response = false;
					endif;
					break;
				default:
					$response = false;
			endswitch;
		endforeach;

		return $response === true ?: array('code' => 'mode','data' => $mode);

	}

	//////////////////////////////////////////////////////
	// == Generatore di codici ==
	public function codeGenerator($length = 8, $mode = 'Ln', $pattern = ''){

		$letters = $numbers = '';	// divido in lettere e numeri per creare dei patterns

		if(!strlen($mode)) $mode = 'Ln';

		$arMode = str_split($mode);

		foreach($arMode as $M):
			switch($M):
				case 'l':
					$letters .= 'abcdefghijklmnopqrstuvxzyw';
					break;
				case 'L':
					$letters .= 'abcdefghijklmnopqrstuvxzywABCDEFGHIJKLMNOPQRSTUVWXYZ';
					break;
				case 'n':
				case 'N':
					$numbers .= '0123456789';
					break;
				case 'c':
					$letters .= '-.!()?';
					break;
				case 'C':
					$letters .= '|-_.:!()/\?*';
					break;
				default:
					if(!strlen($letters)) $letters .= 'abcdefghijklmnopqrstuvxzyw';
					if(!strlen($numbers)) $numbers .= '0123456789';
			endswitch;
		endforeach;

		$all = $letters.$numbers;
		$limit = strlen($all);
		
		$arCodeRand =  array();
		if(!strlen($pattern)):						# se non ho passato alcun pattern
			$to_search = $letters.$numbers;			// la stringa in cui cercare sono tutti i caratteri
			$format = array_fill(1,$length,"s");	// genero un array con n valori stringa
		else:										# se mi arriva un pattern
			$format = str_split($pattern);		// creo un array con i valori nel pattern
			if($format[0] != '') array_unshift($format,'');	// casomai il primo valore non sia vuoto ne inserisco uno vuoto
		endif;

		$i = 0;
		while(++$i <= $length):						// inizio la gereazione del codice
			if(strlen($pattern)):					// se è stato impostato un pattern ridefinisco ad ogni ciclo il tipo di dato con cui creare quel carattere
				$to_search = isset($format[$i]) && $format[$i] == 'd' ? $numbers : $letters;
			endif;
			$num = rand(1,strlen($to_search));
			$arCodeRand[] = substr($to_search, ($num-1), 1);
		endwhile;
		
		return vsprintf(implode('%',$format),$arCodeRand);
	}
	//////////////////////////////////////////////////////

	//////////////////////////////////////////////////////
	// == Peso File (conversione byte) ==
	function weightFile($size){
	   if(is_int($size) || is_float($size)){
		   $count = 0;
		   $format = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
		   while(($size / 1024) > 1 && $count < 8)  {
			   $size = $size / 1024;
			   $count++;
		   }
		   $decimals = ($size < 10 ) ? 1 : 0;
		   $weight = number_format($size, $decimals, ',', '.')." ".$format[$count];
		   return $weight;
	   }else{return false;}
	}
	//////////////////////////////////////////////////////

	public function dbDateToHumanDate($date, $separator = '/'){
		return implode($separator,array_reverse(explode('-',$date)));
	}

	public function dbDatetimeToHumanDatetime($datetime, $date_separator = '/', $time_separator = ':'){
		$date = substr($datetime,0,10);
		$time = substr($datetime,11);
		return implode($date_separator,array_reverse(explode('-',$date))).' '.implode($time_separator,explode(':',$time));
	}

	public function formatUrl($string){

        $string = strtolower($string);
        
        $string = mb_detect_encoding($string) != 'UTF-8' ? utf8_decode($string) : $string;
        
        $string = str_replace(" ", "-", $string);

        $pattern = array();
        $pattern[0] = htmlentities('/(À|Á|Â|Ã|Ä|Å)/', ENT_NOQUOTES, 'utf-8');
        $pattern[1] = htmlentities('/(à|á|â|ã|ä|å)/', ENT_NOQUOTES, 'utf-8');
        $pattern[2] = htmlentities('/(Æ)/', ENT_NOQUOTES, 'utf-8');
        $pattern[3] = htmlentities('/(æ)/', ENT_NOQUOTES, 'utf-8');
        $pattern[4] = htmlentities('/(þ)/', ENT_NOQUOTES, 'utf-8');
        $pattern[5] = htmlentities('/(Þ)/', ENT_NOQUOTES, 'utf-8');
        $pattern[6] = htmlentities('/(Ç|Č)/', ENT_NOQUOTES, 'utf-8');
        $pattern[7] = htmlentities('/(ç|č)/', ENT_NOQUOTES, 'utf-8');
        $pattern[8] = htmlentities('/(Ð)/', ENT_NOQUOTES, 'utf-8');
        $pattern[9] = htmlentities('/(ð)/', ENT_NOQUOTES, 'utf-8');
        $pattern[10] = htmlentities('/(È|É|Ê|Ë|Ě)/', ENT_NOQUOTES, 'utf-8');
        $pattern[11] = htmlentities('/(è|é|ê|ë|ě)/', ENT_NOQUOTES, 'utf-8');
        $pattern[12] = htmlentities('/(Ì|Í|Î|Ï)/', ENT_NOQUOTES, 'utf-8');
        $pattern[13] = htmlentities('/(ì|í|î|ï)/', ENT_NOQUOTES, 'utf-8');
        $pattern[14] = htmlentities('/(Ñ)/', ENT_NOQUOTES, 'utf-8');
        $pattern[15] = htmlentities('/(ñ)/', ENT_NOQUOTES, 'utf-8');
        $pattern[16] = htmlentities('/(Ò|Ó|Ô|Õ|Ö|Ø)/', ENT_NOQUOTES, 'utf-8');
        $pattern[17] = htmlentities('/(ò|ó|ô|õ|ö|ø)/', ENT_NOQUOTES, 'utf-8');
        $pattern[18] = htmlentities('/(Œ)/', ENT_NOQUOTES, 'utf-8');
        $pattern[19] = htmlentities('/(œ)/', ENT_NOQUOTES, 'utf-8');
        $pattern[20] = htmlentities('/(Ř)/', ENT_NOQUOTES, 'utf-8');
        $pattern[21] = htmlentities('/(ř)/', ENT_NOQUOTES, 'utf-8');
        $pattern[22] = htmlentities('/(Š)/', ENT_NOQUOTES, 'utf-8');
        $pattern[23] = htmlentities('/(š|ß)/', ENT_NOQUOTES, 'utf-8');
        $pattern[24] = htmlentities('/(Ù|Ú|Û|Ü)/', ENT_NOQUOTES, 'utf-8');
        $pattern[25] = htmlentities('/(ù|ú|û|ü)/', ENT_NOQUOTES, 'utf-8');
        $pattern[26] = htmlentities('/(Ÿ|Ý)/', ENT_NOQUOTES, 'utf-8');
        $pattern[27] = htmlentities('/(ÿ|ý)/', ENT_NOQUOTES, 'utf-8');
        $pattern[28] = htmlentities('/(Ž)/', ENT_NOQUOTES, 'utf-8');
        $pattern[29] = htmlentities('/(ž)/', ENT_NOQUOTES, 'utf-8');
        $pattern[30] = htmlentities('/(\(|\)|\>|\<|\*|\/|\\|:|\"|\+)/', ENT_NOQUOTES, 'utf-8');
        $pattern[31] = htmlentities('/(\?)/', ENT_NOQUOTES, 'utf-8');
        $pattern[32] = htmlentities('/(@)/', ENT_NOQUOTES, 'utf-8');
        $pattern[33] = htmlentities('/(\.)/', ENT_NOQUOTES, 'utf-8');
        $pattern[34] = htmlentities('/(\')/', ENT_NOQUOTES, 'utf-8');
        $pattern[35] = htmlentities('/(\,)/', ENT_NOQUOTES, 'utf-8');
        $pattern[36] = htmlentities('/(--)/', ENT_NOQUOTES, 'utf-8');
        $pattern[37] = htmlentities('/(---)/', ENT_NOQUOTES, 'utf-8');
        $pattern[38] = htmlentities('/(:)/', ENT_NOQUOTES, 'utf-8');
        $pattern[39] = htmlentities('/(&)/', ENT_NOQUOTES, 'utf-8');
        
        $replace = array();
        $replace[0] = 'A';
        $replace[1] = 'a';
        $replace[2] = 'AE';
        $replace[3] = 'ae';
        $replace[4] = 'B';
        $replace[5] = 'b';
        $replace[6] = 'C';
        $replace[7] = 'c';
        $replace[8] = 'D';
        $replace[9] = 'd';
        $replace[10] = 'E';
        $replace[11] = 'e';
        $replace[12] = 'I';
        $replace[13] = 'i';
        $replace[14] = 'N';
        $replace[15] = 'n';
        $replace[16] = 'O';
        $replace[17] = 'o';
        $replace[18] = 'OE';
        $replace[19] = 'oe';
        $replace[20] = 'R';
        $replace[21] = 'r';
        $replace[22] = 'S';
        $replace[23] = 's';
        $replace[24] = 'U';
        $replace[25] = 'u';
        $replace[26] = 'y';
        $replace[27] = 'y';
        $replace[28] = 'Z';
        $replace[29] = 'z';
        $replace[30] = '_';
        $replace[31] = '';
        $replace[32] = '-at-';
        $replace[33] = '-';
        $replace[34] = '-';
        $replace[35] = '-';
        $replace[36] = '-';
        $replace[37] = '-';
        $replace[38] = '';
        $replace[39] = '-and-';
       
        $string = htmlentities($string, ENT_NOQUOTES, 'UTF-8');
        
        $string = preg_replace($pattern, $replace, $string);
        $string = preg_replace('/^-|-$/','',$string);
    
        return $string;
	}


	public function replaceSpecial($stringa){
		
		$stringa = html_entity_decode($stringa, ENT_QUOTES, 'UTF-8');
		$stringa = str_replace('&', '&amp;', $stringa);
		$stringa = str_replace('&', '&amp;', $stringa);
		
		return $stringa;
		
	}

}

?>