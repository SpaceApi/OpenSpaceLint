<?php
error_reporting(0);

header('Content-type: application/json');

require_once('utils.php');

$url = filter_var($_POST['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);

// data sent with the GET method?
if(empty($url))
    $url = filter_var($_GET['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);

if (!$url || !preg_match("/^https?:/i", $url)) {
    echo '{ "result": "Invalid URL. Please check your URL and try again.", "error": true }';
    return;
}

$response = get_data($url);

// if status >= 400 and contentLength >= 52428800
// then null is returned and error messages written
// to the output
if($response === null)
    return;
    
$data = $response->content;

if($data === false || is_null($data)) {
				echo '{ "result": "Unable to fetch your JSON file. Please check your server.", "error": true }';
    //echo '{ "result": "Unable to parse URL. Please check your URL and try again.", "error": true }';
    return;
}

echo json_encode($response);