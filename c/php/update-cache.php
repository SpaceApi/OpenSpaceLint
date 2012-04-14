<?php
error_reporting(0);

/*
ini_set('display_errors',0);
ini_set('log_errors',1);
ini_set('error_log','/absolute/path/to/the/openspace.log');
*/

/*
// test to see if the connection gets closed and if the script continues to be executed
header("Content-Length: 0");
header("Connection: close");
flush();
*/

require_once("utils.php");
require_once("create-keys.php");

// download and cache all the JSONs
update_cache(true);

// create a JSON with two lists of
// what members are used of what spaces
$lists = list_space_array_keys();
file_put_contents("cache/array_keys.json", json_encode($lists));

?>