<?php
error_reporting(0);

//ini_set('display_errors',0);
//ini_set('log_errors',1);
//ini_set('error_log','/absolute/path/to/the/openspace.log');

// test to see if the connection gets closed and if the script continues to be executed
//header("Content-Length: 0");
//header("Connection: close");
//flush();
    
require_once("../../config.php");

// REMOTE_ADDR might not always contain the actual client address.
// this heavily depends on the server where OpenSpaceLint is deployed
if ( $_SERVER["SERVER_ADDR"] != $_SERVER["REMOTE_ADDR"] )
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
        
        $space = $argv[1];
        $directory = json_decode(file_get_contents("spacehandlers/directory.json"));
        $url = $directory->$space;
        cache_json_from_url($space, $url);
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

?>