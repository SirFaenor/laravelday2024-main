<?php
$data = array();
$schema = array();

if(isset($App)):

	#############################################
	# DATI STRUTTURATI SECONDO schema.org

	/* reference													*/	$schema[0]['@context'] 						= 'https://schema.org/';
																		$schema[0]['@type'] 						= 'Organization';
	/* nome dell'azienda											*/	$schema[0]['legalName']						= $App->Config['company']['name'];
																		$schema[0]['vatID']							= $App->Config['company']['data']['vat'];
																		$schema[0]['taxID']							= '';
																		$schema[0]['REA']							= '';
																		$schema[0]['capital']						= '';
	/* indirizzo del sito web 										*/	$schema[0]['url'] 							= $App->Config['site']['url'];
	#
	/* indirizzo dell'azienda (via, numero, cap, città, nazione)	*/	$schema[0]['address']['@type'] 				= 'PostalAddress';
																		$schema[0]['address']['streetAddress'] 		= $App->Config['company']['address'];
																		$schema[0]['address']['postalCode'] 		= $App->Config['company']['zip'];
																		$schema[0]['address']['addressLocality'] 	= $App->Config['company']['city'];
																		$schema[0]['address']['addressRegion'] 		= $App->Config['company']['region'];
																		$schema[0]['address']['addressCountry'] 	= $App->Config['company']['country'];
	#
	/* contatti dell'azienda                                        */	$schema[0]['ContactPoint']['@type'] 		= 'ContactPoint';
																		$schema[0]['ContactPoint']['contactType'] 	= 'billing support';

																		$schema[0]['ContactPoint']['availableLanguage']['@type'] 	= 'Language';
																		$schema[0]['ContactPoint']['availableLanguage']['name'] 	= array('Italian');

																		$schema[0]['ContactPoint']['telephone']		= $App->Config['company']['phone'][0];
																		$schema[0]['ContactPoint']['faxNumber']		= '';
																		$schema[0]['ContactPoint']['email']			= $App->Config['company']['mail'][0];
																		$schema[0]['ContactPoint']['url']			= $App->Config['site']['url'].$App->Lang->returnL('hours');


	#############################################
	# DATI NON STRUTTURATI (da utilizzare per replace in testi di lingue traduzioni)



	/* titolo del sito web 											*/	$legal_data['title'] 			=	$App->Config['site']['name'];
	/* indirizzo del sito web 										*/	$legal_data['miosito'] 			=	$schema[0]['url'];
	/* nome dell'azienda											*/	$legal_data['azienda'] 			=	$schema[0]['legalName'];
	/* contatti dell'azienda                                        */	$legal_data['contatti'] 		=	$App->Lang->returnT('label_telefono').': '.$schema[0]['ContactPoint']['telephone'].' - '.$App->Lang->returnT('label_email').' '.$schema[0]['ContactPoint']['email'];
	/* indirizzo dell'azienda (via, numero, cap, città, nazione)	*/	$legal_data['indirizzo'] 		=	$schema[0]['address']['streetAddress'].' - '.$schema[0]['address']['postalCode'].' '.$schema[0]['address']['addressLocality'].' ('.$schema[0]['address']['addressRegion'].') - '.$schema[0]['address']['addressCountry'];
	/* dati fiscali dell'azienda, come codice fiscale e partita iva */	$legal_data['dati_fiscali'] 	=	'<abbr title="'.$App->Lang->returnT('label_piva').'">'.$App->Lang->returnT('label_piva_abbr').'</abbr>: '.$schema[0]['vatID'];
	/* link assoluto per i dati fiscali di ogni sito 				*/	$legal_data['link_privacy'] 	=	$schema[0]['url'].$App->Lang->returnL('privacy');

endif;
