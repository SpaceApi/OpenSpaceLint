<?php
error_reporting(0);
header('Content-type: application/json');

require_once('recaptchalib.php');
require_once('utils.php');

$publickey = "6LdMsM4SAAAAAItMjpij0bI7j_2wIqlGlehNBlL3";
$privatekey = "6LdMsM4SAAAAAM6wchNIVZRmbgAU_ZKvsdclFHMe";

if(isset($_GET["recaptcha_response_field"])) {
        
				$resp = recaptcha_check_answer (
								$privatekey,
								$_SERVER["REMOTE_ADDR"],
								stripslashes(strip_tags($_GET["recaptcha_challenge_field"])),
								stripslashes(strip_tags($_GET["recaptcha_response_field"]))
				);
			
				$response = array("ok" => false, "message" => "");
				
				if ($resp->is_valid){
								$response["ok"] = true;
								
								// TODO: filter these variables
								//$url = stripslashes(strip_tags($_GET["url"]));
								$url = filter_var($_GET['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
								
								// get the space name from the space json loaded server-side
								// TODO: maybe cache it for some further check purposes?
								$data = get_data($url);
								$space_data = $data->content;
								$space_json = json_decode($space_data);
								$space_name = stripslashes(strip_tags($space_json->{'space'}));

								if(empty($url) || empty($space_name)){
												$response["ok"] = false;
												$response["message"] = "Incomplete data were sent to the server.";
								}
								else{
												$file = "spacehandlers/directory.json";
												$dirjson = file_get_contents($file);
												$dirarr = json_decode($dirjson, true);
												
												//if(!in_array($url, $dirarr)){
												if($dirarr[$space_name] === null) {
																$dirarr[$space_name] = $url;
																$dirjson = json_encode($dirarr);
																file_put_contents($file, $dirjson);
																$response["message"] = "The space got added to the directory.";
												}
												else
																$response["message"] = "The space is already in the directory.";                            
								}
				}
				else
								$response["message"] = $resp->error;
				
				//print_r($response);
				echo json_encode($response);        
}
?>