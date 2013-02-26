<?php

function validate_headers_hosting_consideration($args)
{
    global $logger;
    $logger->logDebug("Processing the plugin 'validate_headers_hosting_consideration'");
    
    // check Access-Control-Allow-Origin header to be '*'
    // check Cache-Control header to be 'no-cache'
    // check Content-type to be 'application/json'

    return true;
}

$space_api_validator->register("validate_headers_hosting_consideration");