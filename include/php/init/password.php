<?php
if(!defined('PASSWORD_RESET_EXPIRES_MINUTES')):
    define('PASSWORD_RESET_EXPIRES_MINUTES',15); // 15 minuti
endif;

if(!defined('PASSWORD_MINLENGTH')):
    define('PASSWORD_MINLENGTH',8);
endif;

if(!defined('PASSWORD_MAXLENGTH')):
    define('PASSWORD_MAXLENGTH',30);
endif;

if(!defined('PASSWORD_MODE')):
    define('PASSWORD_MODE','Ln');
endif;
