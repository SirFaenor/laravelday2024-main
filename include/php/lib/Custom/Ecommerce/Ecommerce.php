<?php 

namespace Custom\Ecommerce;
class Ecommerce extends \Ecommerce\Ecommerce{

	protected $arPacks 		= array();		// pacchetti convenienza
	protected $FixNumber;
	protected $FixString;

	
	/**
	* 	setProducts()
	* 	popolo l'array con i prodotti
	*	@param string $cond : condizioni da aggiungere alla query per il recupero dei prodotti
	*	@return void
	*/
	public final function setProducts($cond = ''){

		// recupero i prodotti
		$this->arProducts = $this->Da->getRecords(array(
			'model'		=> 'PRODOTTI'
			,'cond'		=> 'AND XL.lang = ? '.trim($cond).' HAVING published ORDER BY X.posizione ASC'
			,'params'	=> array($this->Lang->lgId)
		));

		// imposto i dati aggiuntivi (come i prezzi per categoria utente, i tags)
		if($this->arProducts):
			foreach($this->arProducts as $kp => & $P):

				$P['ar_prezzi'] = 
				$P['ar_tags'] = 
				array();

				// ottengo i prezzi e li salvo per categoria
				$arPrezzi = $this->Da->getRecords(array(
					'table'		=> 'prodotto_rel_cliente_cat'
					,'cond'		=> 'WHERE id_main = ?'
					,'params'	=> array($P['id'])
				));
				if($arPrezzi):
					foreach($arPrezzi as $PR):
						$PR['discount'] 			= $PR['prezzo2'] > 0 && $PR['prezzo2'] < $PR['prezzo'] ? 100-ceil($PR['prezzo2']*100/$PR['prezzo']) : 0;

						// se ho una promozione, questa vince su eventuali altri prezzi
						if($this->Promotion):
							$PR['discount'] = $this->Promotion['perc_discount'];
							$PR['prezzo2_no_iva'] = ceil($PR['prezzo_no_iva']*(100-$this->Promotion['perc_discount']))/100;
							$PR['prezzo2_iva'] = ceil($PR['prezzo_iva']*(100-$this->Promotion['perc_discount']))/100;
							$PR['prezzo2'] = $PR['prezzo2_no_iva']+$PR['prezzo2_iva'];
						endif;

						$PR['prezzo_finale'] 		= $PR['prezzo2'] > 0 && $PR['prezzo2'] < $PR['prezzo'] ? $PR['prezzo2'] : $PR['prezzo'];
						$PR['prezzo_finale_iva']	= $PR['prezzo2_iva'] > 0 && $PR['prezzo2_iva'] < $PR['prezzo_iva'] ? $PR['prezzo2_iva'] : $PR['prezzo_iva'];
						$PR['prezzo_finale_no_iva'] = $PR['prezzo2_no_iva'] > 0 && $PR['prezzo2_no_iva'] < $PR['prezzo_no_iva'] ? $PR['prezzo2_no_iva'] : $PR['prezzo_no_iva'];
						$P['ar_all_prezzi'][$PR['id_sec']] = $PR;		// imposto i prezzi sulla base della categoria utente
						if($PR['id_sec'] == $this->User->getid_cat()):
							$P['ar_prezzi'] = $PR;
						endif;
					endforeach;
				endif;

				if(empty($P['ar_prezzi'])):
					throw new \Exception('['.__METHOD__.'] Non ci sono prezzi per questo prodotto <pre>'.print_r($P,1).'</pre>',E_USER_WARNING);
					unset($this->arProducts[$kp]);
				endif;

				// ottengo i tags
				$arTags = $this->Da->getRecords(array(
					'model'		=> 'TAGS'
					,'cond'		=> 'AND XL.lang = ? AND X.id IN (SELECT id_sec FROM prodotto_rel_tags WHERE id_main = ?) HAVING published ORDER BY X.id ASC'
					,'params'	=> array($this->Lang->lgId,$P['id'])
				));
				if($arTags):
					foreach($arTags as $T):
						$P['ar_tags'][$T['id']] = $T;
					endforeach;
				endif;
			endforeach;
		endif;
	}

}