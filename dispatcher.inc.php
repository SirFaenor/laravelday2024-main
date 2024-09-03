<?php
if ($fullPath !== NULL && file_exists($fullPath)) :

    
    /**
     * controllo cache
     */
    //get a unique hash of this file (etag)
    $etagFile = md5_file($fullPath);
    $lastModified = filemtime($fullPath);
    //get the HTTP_IF_MODIFIED_SINCE header if set
    $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
    //get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
    $etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
    if (@strtotime($ifModifiedSince) == $lastModified || $etagHeader == $etagFile):
        http_response_code(304);
        exit;
    endif;


    $fileExtension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    /**
     * tipo file
     */
    $contentTypes = array(
        "gif" => ["content_type" => "image/gif", "max_age" => 604800]
        ,"png" => ["content_type" => "image/png", "max_age" => 604800]
        ,"jpeg" => ["content_type" => "image/jpeg", "max_age" => 86400]
        ,"jpg" => ["content_type" => "image/jpeg", "max_age" => 86400]
        ,"svg" => ["content_type" => "image/svg+xml", "max_age" => 604800]
        ,"css" => ["content_type" => "text/css; charset=utf-8", "max_age" => 604800]
        ,"js" => ["content_type" => "application/javascript; charset=utf-8", "max_age" => 604800]
        ,"pdf" => ["content_type" => "application/pdf", "max_age" => 604800]
        ,"woff2" => ["content_type" => "font/woff2", "max_age" => 31536000]
        ,"woff" => ["content_type" => "font/woff", "max_age" => 31536000]
        ,"eot" => ["content_type" => "font/eot", "max_age" => 31536000]
        ,"ttf" => ["content_type" => "font/ttf", "max_age" => 31536000]
        ,"otf" => ["content_type" => "font/otf", "max_age" => 31536000]
        ,"mp4" => ["content_type" => "application/mp4", "max_age" => 31536000]
    );


    /**
     * impostazione cache
     */
    $max_age = !empty($contentTypes[$fileExtension]["max_age"]) ? $contentTypes[$fileExtension]["max_age"] : 86400;   // durata della cache per il file
    $ctype = !empty($contentTypes[$fileExtension]["content_type"]) ? $contentTypes[$fileExtension]["content_type"] : 'application/octet-stream';
    $expires = gmdate('D, j M Y H:i:s',time()+$max_age).' GMT';

        //set etag-header
        header('Content-Description: File Transfer');
        header('Accept-Ranges: bytes');
        header("Etag: ".$etagFile);
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");

        //make sure caching is turned on
        header("ExpiresActive: On");
        header("ExpiresDefault: access plus 1 month");
        header("Expires: ".$expires);
        header('Pragma: cache');
        header('Cache-Control: public max-age='.(string)$max_age);    // 1 giorno
        #header('Content-Length: '.filesize($fullPath));
        header('Content-Type: '.$ctype);


        /**
         * output
         */
        readfile($fullPath);
        ob_end_flush();
        exit;
 
endif;


/**
 * Not found finale
 * (da aggiornare in base a progetto)
 */
http_response_code(404);
exit;