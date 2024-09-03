<?php

// FLAG SANDBOX (sandbox | production)
$mode = getenv("PAYPAL_MODE") ?: 'production';

# dati test àtrio
if ($mode === 'sandbox'): 
    
    return [
        "username" => "sb-msl43o16184297_api1.business.example.com"
        ,"password" => "PEMV6T7HJCYL4PSL"
        ,"signature" => "AG2eEk7w1WI45eMI4YL8UaaHAtJWAphoXcH2al.6mkhE7ZeQY0Lmou29"
        ,"endpoint" => "https://api-3t.sandbox.paypal.com/nvp"
        ,"url" => "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token="
    ];
    
elseif($mode == 'production') :

    return [
        "username" => getenv("PAYPAL_USERNAME") ?: ''
        ,"password" => getenv("PAYPAL_PASSWORD") ?: ''
        ,"signature" => getenv("PAYPAL_SIGNATURE") ?: ''
        ,"endpoint" => "https://api-3t.paypal.com/nvp"
        ,"url" => "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token="
    ];

endif;
