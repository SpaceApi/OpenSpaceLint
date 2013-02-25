#!/usr/bin/env php

<?php

include("../c/php/classes/Utils.class.php");

$json = file_get_contents("directory.json");
$dir = json_decode($json, true);
Utils::ksort($dir);

// json_encode without flags has some problems with special characters
// see blog post
$json = json_encode($dir);

$json = Utils::json_pretty_print($json);
file_put_contents("directory.json", $json);