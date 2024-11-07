<?php

namespace Utility;
class FixFile {

    //////////////////////////////////////////////////////////////
    // == Estensioni File ==
    public function fileExtension($fileName) {
        $ext_file = pathinfo($fileName);
        return $ext_file['extension'];
    } 
    //////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////
    // == Cancellazione File ==
    public function fileDelete($fileDel){
        
        if(file_exists($fileDel)){
            unlink($fileDel);
        }
        unset($fileDel);
    }

    //////////////////////////////////////////////////////////////
    // == Cancellazione File ==
    public function dirDelete($dir){
        if(is_dir($dir)){
            $handle = opendir($dir);
            while (false!==($FolderOrFile = readdir($handle))){
                if($FolderOrFile != "." && $FolderOrFile != ".."){
                    if(is_dir($dir.'/'.$FolderOrFile)){$this->dirDelete($dir.'/'.$FolderOrFile);}else{unlink($dir.'/'.$FolderOrFile);}
                }
            }
            closedir($handle);
            if(rmdir($dir)){
                return true;
            }else{
                throw new \Exception('['.__METHOD__.'] <br />La cartella non è stata cancellata',E_USER_WARNING);
            }
        }
    } 
    //////////////////////////////////////////////////////////////

    /*
    * ======================================================================
    *
    *  fileMove() 
    *  -----------
    *  Spostamento file
    *
    * ======================================================================
    */
    public function fileMove($origin, $destination){
        
        if(file_exists($origin)){
            if(copy($origin, $destination)){
                $this->fileDelete($origin);
            } else {
                throw new \Exception("['.__METHOD__.'] Errore di copiatura ".$origin." => ".$destination);
            }
        } else {
            throw new \Exception("['.__METHOD__.'] File non trovato ".$origin);
        }
        
        return true;
    }


    //////////////////////////////////////////////////////
    // == Nome file ==
    public function fileName($string){
        $string = strtolower($string);
        
        $string = mb_detect_encoding($string) != 'UTF-8' ? utf8_decode($string) : $string;
        
        $string = str_replace(" ", "-", $string);

        $ext = pathinfo($string,PATHINFO_EXTENSION);
        $string = pathinfo($string,PATHINFO_FILENAME);

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
    
        return $string.(strlen($ext) ? '.'.$ext : '');
        
    }
    //////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////
    // == Rinomina File (incremento numerico) ==
    public function fileRename($file){
        
        $nf = 0;// per uscire dal ciclio in caso di ok
        $i = 0;// variabile ad incremento
        $x = '';// per costruire il nome con la variabile ad incremento
        $fileName = "";
        
        $infoFile = pathinfo($file);
        $path = $infoFile['dirname'].'/';
        $name = $infoFile['filename'];
        $ext = $infoFile['extension'];

        do{
            if(!file_exists($path.$this->fileName($name).$x.'.'.$ext)){
                $fileName = $this->fileName($name).$x.'.'.$ext;
                $nf = 1;
            }
            
            $x = '_'.$i;
            $i++;
        }while($nf == 0);
        
        return  $fileName;
    }
    //////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////
    // == Rinomina File (con nome specifico) ==
    public function fileRenameName($file, $newName){
        
        $infoFile = pathinfo($file);
        $name = $infoFile['filename'];
        $ext = $infoFile['extension'];

        $infoFile2 = pathinfo($newName);
        $name2 = $infoFile2['filename'];

        $fileName = $name2.'.'.$ext;
        
        return  $fileName;
    }

    //////////////////////////////////////////////////////////////
    // == Carica i files ==
    public function fileUpload($nome_file_tmp,$path_e_file_finale){
        
        if(is_uploaded_file($nome_file_tmp)):
            if(!move_uploaded_file($nome_file_tmp, $path_e_file_finale)) return false;
            else return true;
        else:
            return false;
        endif;

    }



}
?>