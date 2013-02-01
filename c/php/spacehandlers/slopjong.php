<?php

error_reporting(0);

$arr["api"] = "0.13";
$arr["space"] = "Slopjong";
$arr["logo"] = "http://openspace.slopjong.de/favicon.gif";
$arr["url"] = "http://slopjong.de";
$arr["open"] = false;

header('Content-type: application/json');
echo json_encode($arr);

?>