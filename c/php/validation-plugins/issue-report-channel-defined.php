<?php

// check that the defined communication channels are implemented
// this could be done with JSON schema dependencies but the validator
// implementation has no support for it
function validate_issue_report_channel_defined($space_api_file, &$errors, &$warnings, &$valid_versions, &$invalid_versions)
{
    global $logger;
    $logger->logDebug("Processing the plugin 'validate_issue_report_channel_defined'");
    
    $obj = $space_api_file->json();
    
    // the issue-report-channels field got introduced in v0.13. we define a new array for this while
    // remove the prefix '0.' so that we can define a ordinal order because 0.8 is less than 0.13
    // in the specs but mathematically 0.8 greater than 0.13.
    $versions_of_interest = preg_replace("/0./", "", $valid_versions);
    
    // remove versions prior 13
    foreach($versions_of_interest as $index => $version)
        if($version < 13)
            array_splice($versions_of_interest, $index, 1);
    
    // we need this variable because we cannot use $obj->issue-report-channels directly
    $issue_report_channels = "issue-report-channels";
    
    // iterate over all the versions where this check makes sense
    foreach($versions_of_interest as $version)
    {
        $extended_version = "0.$version";
        
        if(property_exists($obj, "issue-report-channels"))
        {            
            foreach($obj->$issue_report_channels as $index => $channel)
            {                
                if(! (property_exists($obj, "contact") && property_exists($obj->contact, $channel)) )
                {
                    // remove the version from the valid versions array
                    $pos = array_search("0.$version", $valid_versions);
                    if($pos !== false)
                        array_splice($valid_versions, $pos, 1);
                        
                    // add it to the invalid versions array if
                    // it's not yet present
                    if( false === array_search("0.$version", $invalid_versions))
                        $invalid_versions[] = "0.$version";
                    
                    // get the error message array of the current iterated version
                    if(property_exists($errors, $extended_version))
                    {
                        $a = $errors->$extended_version;
                    }
                    else
                        $a = array();
                    
                    // create the error object consisting of a message and a description
                    $err = new stdClass;
                    $err->msg = "The communication channel '$channel' isn't defined in your contact section.";
                    $err->description = "";
                    
                    // add the new error object
                    $a[] = $err;
                    
                    // assign the new array to the errors object
                    $errors->$extended_version = $a;
                }
            }
        }
    }
    
    return true;
}

$space_api_validator->register("validate_issue_report_channel_defined");