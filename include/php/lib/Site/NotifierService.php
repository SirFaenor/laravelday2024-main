<?php
namespace Site;

use Exception;
class NotifierService
{

    /**
     * @var string $mode 
     * l = scrive log
     * s = manda mail
     * ls = scrive log e manda mail
     */
    protected $mode = 'ls'; 
    
    /**
     * @var string $logDirPath directory di log
     */
    protected $logDirPath;


    /**
     * Ultimo codice messaggio inviato
     */
    protected $lastMessageSubject;


    /**
     * @param string $logDirPath directory di log
     * @param boolean $mode imposta modalità debug
     */
    public function __construct($logDirPath, $mode) {

        $this->logDirPath = $logDirPath;
        if (!file_exists($this->logDirPath) || !is_dir($this->logDirPath)) {throw new Exception("Directory log errata: ".$this->logDirPath);}
        $this->mode = $mode;
    }


    /**
     * Ultimo passaggio di invio notifica
     * 
     * @param PhpMailer $mail istanza di phpmailer
     * @param string $subject 
     */
    public function notifyDispatch(\PHPMailer\Phpmailer\Phpmailer $mail, $subject) {

        if (strpos($this->mode, "l") !== false) {$this->log($mail, $subject);}
        
        if (strpos($this->mode, "s") !== false) {$mail->Send();}

        return true;

    }
    
  
    /**
     * Log dell'invio di una notifica.
     * Crea due file, un txt contentente tutto il messaggio MIME (v. metodo Phpmailer)
     * e un hmtl con solo il body del messaggio
     * 
     * @param PhpMailer $mail istanza di phpmailer
     * @param string $subject soggetto interno (verrà inserito nel nome del file)
     * @todo gestione errori
     */
    protected function log($mail, $subject) {
        
        $html = $mail->Body;

        $mail->preSend();
        $mime = $mail->getSentMIMEMessage();

        $this->lastMessageSubject = $subject.'-'.date("YmdHis");

        file_put_contents($this->logDirPath.'/'.$this->lastMessageSubject.'.html' , $html);
        file_put_contents($this->logDirPath.'/'.$this->lastMessageSubject.'.txt' , $mime);

        return true;
             
    }



    /**
     * Ritorna codice dell'ultimo messaggio
     */
    public function getLastMessageSubject()
    {
        return $this->lastMessageSubject;
    }
    

}