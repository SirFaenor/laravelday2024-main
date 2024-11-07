<?php
namespace Html;

use Exception; 

/**
 * Genera html con lista di selezione pagine
 */
class WidgetPager 
{
    
    protected $_pageStart;
    protected $_pageEnd;
    protected $_pageCount;
    protected $_currentPage;
    protected $_preUrl;
    protected $_postUrl;
    protected $_htmlId;


    /**
     * @param $currentPage pagina corrente
     * @param $pageCount numero totale pagine
     * @param $visiblePages numero di pagine visibile tra i due separatori, null per averle tutte visibili
     * @param $preUrl url che deve precedere il numero di pagina
     * @param $postUrl url che deve seguire il numero di pagina
     * @param $htmlId attributo id da inserire nell'html
     */
    public function __construct($currentPage, $pageCount, $visiblePages, $preUrl, $postUrl, $htmlId = '') {
        
        if (!$pageCount || !$currentPage) {
            throw new Exception("Errore parametri.");
        }


        // memorizzo variabili
        $this->_pageCount = $pageCount;
        $this->_currentPage = $currentPage;
        $this->_preUrl = $preUrl;
        $this->_postUrl = $postUrl;
        $this->_htmlId = $htmlId;
        
        $this->sepLeft = '';
        $this->sepRight = '';
        
        // pagine di partenza e di fine (le pagine intermedie, escluse la prima e l'ultima che mostro sempre!)
        $this->_pageStart = 2; 
        $this->_pageEnd = $this->_pageCount - 1;

        // se ho pagine intermedie da visualizzare
        if ($visiblePages !== null && $this->_pageEnd - $this->_pageStart > 0) { 

            $this->sepLeft = '<li class="sep sep_left"><a>...</a></li>'.PHP_EOL;
            $this->sepRight = '<li class="sep sep_right"><a>...</a></li>'.PHP_EOL;

            // aggiorno i valori di partenza
            $this->_pageStart = $this->_pageStart + ($visiblePages/2);
            $this->_pageEnd = $this->_pageStart + $visiblePages;
            
            // se la pagina corrente è minore dell'indice di partenza intermedio la devo mostrare
            if ($this->_currentPage > 1 && $this->_currentPage <= $this->_pageStart) :
                $this->_pageStart = 2;
                $this->_pageEnd = $this->_pageStart + $visiblePages;
                $this->sepLeft = '';
            endif;
                
            // se la pagina corrente è maggiore dell'indice di partenza intermedio la devo mostrare
            if ($this->_currentPage > $this->_pageEnd) :
                $this->_pageEnd = $pageCount - 1;
                $this->_pageStart = $this->_pageEnd - $visiblePages;
                $this->sepRight = '';
            endif;

            // se tra l'indice intermedio finale e l'ultima pagina c'è differenza di due (es 4 e 6), mostro la pagina restante
            if ($this->_pageCount - $this->_pageEnd  == 2) :
                $this->_pageEnd++;
                $this->sepRight = '';
            endif;
            
            // se tra l'indice intermedio iniziale e la prima pagina c'è differenza di due (es 3 e 1), mostro la pagina restante
            if ($this->_pageStart - 1 == 2) :
                $this->_pageStart--;
                $this->sepLeft = '';
            endif;

        }
  
    }

    /**
     * crea e restituisce html 
     */
    public function render() {

        // niente da mostrare
        if (!$this->_pageStart || !$this->_pageEnd || $this->_pageCount == 1) {
            return '';
        }
        
        $id_html = $this->_htmlId ? ' id='.$this->_htmlId  : '';
        echo '      <div'.$id_html.'>'.PHP_EOL;
        echo '          <ul>'.PHP_EOL;
        //echo '            <li id="pag">pag.</li>'.PHP_EOL;           
        
        // prima pagina
        $current = $this->_currentPage == 1 ? ' class="current"' : '';
        echo '  <li'.$current.'><a href="'.$this->_preUrl.'1'.$this->_postUrl.'">1</a></li>'.PHP_EOL; 
        echo $this->sepLeft;
    

        // pagine intermedie
        for($i = $this->_pageStart; $i <= $this->_pageEnd; $i++){
            if($i == $this->_currentPage) {
                //echo '    <li><a class="current" href="#">'.sprintf("%02d",$i).'</a></li>'.PHP_EOL;}
                echo '  <li class="current"><a href="#">'.$i.'</a></li>'.PHP_EOL;}
            else{
                echo '  <li><a href="'.$this->_preUrl.$i.$this->_postUrl.'">'.$i.'</a></li>'.PHP_EOL;
            }
        }
        
        // ultima pagina
        echo $this->sepRight;
        $current = $this->_pageCount == $this->_currentPage ? ' class="current"' : '';
        echo '  <li'.$current.'><a href="'.$this->_preUrl.$this->_pageCount.$this->_postUrl.'">'.$this->_pageCount.'</a></li>'.PHP_EOL; 
        echo '  </ul>'.PHP_EOL;
        echo "</div>".PHP_EOL;

    }

}