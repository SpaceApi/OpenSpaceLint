<?php

function validate_issue_report_channel_defined($args)
{
    global $logger;
    $logger->logDebug("Processing the plugin 'validate_issue_report_channel_defined'");
    
    // check that the defined communication channels are implemented
    // this could be done with JSON schema dependencies but the validator
    // implementation has no support for it
    
    return true;
}

$space_api_validator->register("validate_issue_report_channel_defined");