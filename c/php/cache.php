<?php

header('Content-type: application/json');

$config = realpath(dirname(__FILE__) . "/../../config.php");
require_once($config);
error_reporting( ($debug_mode) ? E_ALL : 0 );

// if somebody called this script from the full path and not from
// the rewritten URL then the space variable might be missing
if(! isset($_GET["space"]) )
{
    echo '{ "no": "space"}';
    exit;
}
    
require_once(dirname(__FILE__) . "/NiceFileName.class.php");

$file = "cache/" . NiceFileName::json($_GET["space"]);

// return the cached json if it's present in the directory (whitelisting)
if( file_exists($file) )
    echo file_get_contents($file);
else
    echo '{ "no": "space"}';