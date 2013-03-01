<?php

function validate_headers_hosting_consideration($space_api_file, &$errors, &$warnings, &$valid_versions, &$invalid_versions)
{
    global $logger;
    $logger->logDebug("Processing the plugin 'validate_headers_hosting_consideration'");
    
    $space_name = $space_api_file->name();
    $url = $space_api_file->status_url();
    
    if($url == "")
    {
        $directory = new PrivateDirectory;
        $url = $directory->get_url($space_name);
    }
    
    if($url != "")
    {
        $headers = DataFetch::get_headers($url);

        if(!isset($headers["Access-Control-Allow-Origin"]))
            $warnings[] = "The 'Access-Control-Allow-Origin' header (with a preferred value of '*') is not set!";
            
        if(isset($headers["Access-Control-Allow-Origin"]) && $headers["Access-Control-Allow-Origin"] != "*")
            $warnings[] = "The value of the 'Access-Control-Allow-Origin' header should be '*'.";

        if(!isset($headers["Content-Type"]))
            $warnings[] = "The 'Content-Type' header (with a preferred value of 'application/json') is not set!";
            
        if(isset($headers["Content-Type"]) && strpos($headers["Content-Type"], "application/json") === false)
            $warnings[] = "The value of the 'Content-Type' header should be 'application/json'.";
            
        if(!isset($headers["Cache-Control"]))
            $warnings[] = "The 'Cache-Control' header (with a preferred value of 'no-cache') is not set!";
            
        if(isset($headers["Cache-Control"]) && $headers["Cache-Control"] != "no-cache")
            $warnings[] = "The preferred value of the 'Cache-Control' header is 'no-cache'.";
    }
    else
        $logger->logNotice("The url for the space could not be determined.");
}

$space_api_validator->register("validate_headers_hosting_consideration");