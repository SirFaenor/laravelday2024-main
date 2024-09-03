<?php

$Sitemap = new Sitemap();

foreach ($App->Lang->getPublicLanguages() as $lg) :
    $App->Lang->switchLanguage($lg["suffix"]);
    require(DOCUMENT_ROOT.'/include/php/sitemap_common.php');
    require(DOCUMENT_ROOT.'/include/php/sitemap_xml.php');
endforeach;

/**
 * Output xml
 */
$Sitemap->printXml();

