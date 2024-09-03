<?php 

$size = isset($_GET['size']) && $_GET['size'] != 'O' ? $_GET['size'].'_' : '';

$id = !empty($_GET['id']) ? $_GET['id'] : 0;
$file = !empty($_GET['file']) ? $_GET['file'] : 0;

$arMap = array(
    'parkmap_item_img'          => __DIR__.'/file_public/parkmap_item/'.$App->FixString->fix($id.'/'.$size.$file)
    ,'prodotto_img'          	=> __DIR__.'/file_public/prodotto/'.$App->FixString->fix($id.'/'.$size.$file)
    ,'menu_img'          	=> __DIR__.'/file_public/prodotto/'.$App->FixString->fix($id.'/'.$size.$file)
    ,'calendar_img'         	=> __DIR__.'/file_public/calendar/'.$App->FixString->fix($id.'/'.$file)
    ,'video_img'           		=> __DIR__.'/file_public/video/'.$App->FixString->fix($id.'/'.$size.$file)
    ,'warning_img' 				=> __DIR__.'/file_public/warning/'.$App->FixString->fix($id.'/'.$file)
    ,'docs' 					=> __DIR__.'/file_public/download/documenti/'.$_GET['file']
);

$fullPath = isset($arMap[$_GET['f']]) ? $arMap[$_GET['f']] : NULL;

require __DIR__.'/dispatcher.inc.php';

exit;
