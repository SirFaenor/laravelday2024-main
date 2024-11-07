<?php

return [
    "url" => getenv("WEBSERVICE_URL") ?: '',
    "token" => getenv("WEBSERVICE_TOKEN") ?: '',
];