<?php 

$to_return = array();
if(is_array($schema) && count($schema) > 0):
	foreach($schema as $S):
		unset($S['capital']);
		unset($S['REA']);
		$to_return[] = $S;
	endforeach;
endif;

$LocalBusiness = array();
$LocalBusiness['@type'] 		= 'LocalBusiness';
$LocalBusiness['name'] 			= $App->Config['company']['name'];
$LocalBusiness['image'] 		= $App->Config['site']['url'].'/imgs/layout/svg_fallback/logos/'.$App->Config['site']['code'].'-logo-it.png';

$LocalBusiness['knowsLanguage']['@type'] 	= 'Language';
$LocalBusiness['knowsLanguage']['name'] 	= array('Italian','English','Deutsch');

$LocalBusiness['address']['@type'] 				= 'PostalAddress';
$LocalBusiness['address']['streetAddress'] = $App->Config['company']['address'];
$LocalBusiness['address']['postalCode'] 	= $App->Config['company']['zip'];
$LocalBusiness['address']['addressLocality']= $App->Config['company']['city'];
$LocalBusiness['address']['addressRegion'] = $App->Config['company']['region'];
$LocalBusiness['address']['addressCountry']= $App->Config['company']['country'];


$LocalBusiness['telephone']		= $App->Config['company']['phone'][0];
$LocalBusiness['email']			= $App->Config['company']['mail'][0];

$LocalBusiness['currenciesAccepted']= 'EUR';
$LocalBusiness['openingHours']	= array('Mo-Fr 08:30-13:30', 'Mo-Fr 14:30-18:30');
$LocalBusiness['url']			= $App->Config['google']['maps']['link'];

$to_return[] = $LocalBusiness;

return $to_return;
