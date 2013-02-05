<?php

//ini_set('display_errors',0);
//ini_set('log_errors',1);
//ini_set('error_log','/absolute/path/to/the/openspace.log');

// test to see if the connection gets closed and if the script continues to be executed
//header("Content-Length: 0");
//header("Connection: close");
//flush();
    
require_once(realpath(dirname(__FILE__) . "/../../config.php"));
require_once(realpath(dirname(__FILE__) . "/utils.php"));
require_once(realpath(dirname(__FILE__) . "/NiceFileName.class.php"));

error_reporting( ($debug_mode) ? E_ALL : 0 );

// REMOTE_ADDR might not always contain the actual client address.
// this heavily depends on the server where OpenSpaceLint is deployed
if ( isset($_SERVER["SERVER_ADDR"]) && $_SERVER["SERVER_ADDR"] != $_SERVER["REMOTE_ADDR"] )
{
    header("Location: http://". $site_url . "/error.html");
}
else
{
    // the error reporting could be switched on as the script
    // is potentially only be run from localhost
    //error_reporting(E_ALL);
    
    require_once("utils.php");
    require_once("create-keys.php");
    
    if( isset($argv) && isset($argc) && $argc>1)
    {
        // if $argv is set then this script got called from the shell.
        // we expect that it got called from a cron job that wants
        // to update a single space api json in the local cache
        
        // if the space name originally is Pumping Station: One
        // $sanitized_space_name will be pumping_station__one
        $sanitized_space_name = $argv[1];
        
        $directory = json_decode(file_get_contents("spacehandlers/directory.json"), true);
        
        // walk through the whole directory
        foreach($directory as $space => $url)
        {            
            // find the entry we are looking for
            if(NiceFileName::get($space) == $sanitized_space_name)
            {   
                $url = $directory[$space];
                $json = cache_json_from_url($space, $url, true);
                
                // save either the cache url or the direct space url
                set_space_url_in_public_directory($json, $space, $url);
            
                // reschedule the cron according what's defined in the json
                $schedule = get_space_cron_schedule($space);
                change_scron_schedule($space, $schedule);
                
                // now end the loop
                break;
            }
        }
    }
    else
    {
        // download and cache all the JSONs
        update_cache(true);
        
        // create a JSON with two lists of
        // what members are used of what spaces
        $lists = list_space_array_keys();
        file_put_contents("cache/array_keys.json", json_encode($lists));
    }
}
