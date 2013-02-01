<?php
error_reporting(0);
header('Content-type: application/json');

require_once('recaptchalib.php');
require_once('utils.php');

if(isset($_GET["recaptcha_response_field"])) {
        
				$resp = recaptcha_check_answer (
								$recaptcha_key["private"],
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
												if($dirarr[$space_name] === null)
            {
																// add the space to the main directory
																$dirarr[$space_name] = $url;
																$dirjson = json_encode($dirarr);
																file_put_contents($file, $dirjson);
																
                // cache the json
                cache_json_from_argument($space_name, $space_json);
																
																// add the space to the public directory
																// TODO: check if the URL must be replaced by the cache URL
																//       in order to use the cache
																$public_directory = file_get_contents($file . ".public");
																$public_directory = json_decode($public_directory, true);
																$public_directory[$space_name] = $url;
																$public_directory_json = json_encode($public_directory);
																file_put_contents($file . ".public", $public_directory_json);
																
																// create the cron(s)
																create_new_cron($space_name);
																
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