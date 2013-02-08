<?php

error_reporting(0);

$url = "http://chaos-inkl.de/raumstatus.php";

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

$data = get_data($url);
$closed = (bool) preg_match("/geschlossen/", $data);

$arr["api"] = "0.13";
$arr["space"] = "Chaos inKL.";
$arr["logo"] = "http://chaos-inkl.de/logo.png";
$arr["icon"] = array(
	"open" => "http://chaos-inkl.de/logo.png",
	"closed" => "http://chaos-inkl.de/logo.png");
$arr["url"] = "http://chaos-inkl.de";
$arr["address"] = "Rudolf-Breitscheid-Straße 65, 70327 Kaiserslautern, Germany";
$arr["open"] = !$closed;
$arr["lon"] =  7.763390;
$arr["lat"] = 49.440880;
$arr["contact"] = array(
	"email" => "info@chaos-inkl.de",
	"irc" => "irc://irc.hackint.eu/c3kl",
	"ml" => "main-subscribe@lists.chaostreff-kaiserslautern.de",
	"twitter" => "@chaos_inkl"
	);
$arr["cache"] = array(
        "schedule" => "m.02");

header('Content-type: application/json');
echo json_encode($arr);

?>
