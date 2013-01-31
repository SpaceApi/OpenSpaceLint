<?php

header('Content-type: application/json');

require_once("externalinput.php");
require_once("NiceFileName.class.php");

$directory = json_decode(file_get_contents("spacehandlers/directory.json"));

$input = popoon_classes_externalinput::basicClean($_GET["space"]);

// the space name with underscores replacing weird characters, this is used
// for 'good' filenames
$nice_space_name = NiceFileName::get($input);

$file = "cache/" . $nice_space_name . ".json";

// return the cached json if it's present in the directory (whitelisting)
if( isset($_GET["space"]) && file_exists($file) )
{
    $json = file_get_contents($file);
    echo $json;
    exit;
}

echo '{ "no": "space"}';

?>
