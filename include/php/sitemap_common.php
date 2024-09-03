<?php
/**
 * Istanzia classe Sitemap e inserisce pagine base
 * Viene incluso da pages/sitemap.php per sitemap utente
 * e da /sitemap.xml.php per sitemap dei motori (in quest'ultimo vengono aggiunte 
 * anche altre pagine )
 */


/**
 * Pagine principali
 */
$Sitemap->add("homepage_".$App->Lang->lgSuff, $App->Lang->returnL("homepage"), $App->Lang->returnT("home"), array("changefreq" => 'daily', "priority" => 1));
$Sitemap->add("park_".$App->Lang->lgSuff, $App->Lang->returnL("park"), $App->Lang->returnT("nav_park")); 
$Sitemap->add("tavern_".$App->Lang->lgSuff, $App->Lang->returnL("tavern"), $App->Lang->returnT("nav_tavern")); 
$Sitemap->add("story_".$App->Lang->lgSuff, $App->Lang->returnL("story"), $App->Lang->returnT("nav_story")); 
$Sitemap->add("hours_info_".$App->Lang->lgSuff, $App->Lang->returnL("hours_info"), $App->Lang->returnT("nav_hours_info")); 
$Sitemap->add("videogallery_".$App->Lang->lgSuff, $App->Lang->returnL("videogallery"), $App->Lang->returnT("nav_videogallery")); 

