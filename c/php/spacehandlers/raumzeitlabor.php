<?php

error_reporting(0);

// better use http://status.raumzeitlabor.de/api/full.json
$url = "http://status.raumzeitlabor.de/";

function get_data($url)
{
  $ch = curl_init();
  $timeout = 10;
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

$html = get_data($url);
$open = (bool) preg_match("/green.png/", $html);

$arr["api"] = "0.12";
$arr["space"] = "RaumZeitLabor";
$arr["logo"] = "http://raumzeitlabor.de/w/images/8/85/RaumZeitLabor_-_Logo_-_Schwarz.png";
$arr["icon"] = array(
	"open" => "http://status.raumzeitlabor.de/images/green.png",
	"closed" => "http://status.raumzeitlabor.de/images/red.png");
$arr["url"] = "http://raumzeitlabor.de";
$arr["address"] = "Boveristrasse 22-24, 68309 Mannheim, Germany";
$arr["open"] = $open;

header('Content-type: application/json');
echo json_encode($arr);

?>