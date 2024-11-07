<?php 

if(pathinfo($_SERVER['REQUEST_URI'],PATHINFO_EXTENSION) != 'txt'):
    http_response_code(301);
    header('Location: /robots.txt');
    exit;
endif;
header("Content-Type: text/plain; charset=UTF-8");
?>
Sitemap: <?php echo $App->Config['site']['url']; ?>/sitemap.xml

User-agent: *
Allow: /
Disallow: /app_logs/
Disallow: /webmail/
Disallow: /stats/
Disallow: /area_amministrazione/
Disallow: /ckeditor/
Disallow: /cron/
Disallow: /pages/
Disallow: /development/

Disallow: /browser_expired.php
Disallow: /error.php
Disallow: /cookie_alert.php
Disallow: /not_found.php

Noindex: /maintenance.php
Noindex: /comingsoon.php

<?php
# pagine in lingua
foreach($App->Lang->getPublicLanguages() as $kl => $v):
	$App->Lang->switchLanguage($v['suffix']);

	echo 'Noindex: '.$App->Lang->returnL('privacy').PHP_EOL;
	echo 'Noindex: '.$App->Lang->returnL('cookies').PHP_EOL;
endforeach;

