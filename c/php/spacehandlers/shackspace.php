<?php

$url = "http://shackspace.de/sopen/text/en";

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
$open = (bool) preg_match("/open/", $data);

$arr["api"] = "0.12";
$arr["space"] = "Shackspace";
$arr["logo"] = "http://rescue.shackspace.de/images/logo_shack_brightbg_highres.png";
$arr["icon"] = array(
	"open" => "http://http://shackspace.de/sopen.gif",
	"closed" => "http://http://shackspace.de/sopen.gif");
$arr["url"] = "http://shackspace.de";
$arr["address"] = "Ulmer Strasse 255, 70327 Stuttgart, Germany";
$arr["open"] = $open;

header('Content-type: application/json');
echo json_encode($arr);

?>