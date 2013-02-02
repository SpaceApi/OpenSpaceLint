<?php

$config = realpath(dirname(__FILE__) . "/../../config.php");
require_once($config);
error_reporting( ($debug_mode) ? E_ALL : 0 );

header('Content-type: application/json');

require_once(dirname(__FILE__) . '/KLoggerInstance.php');

require_once(dirname(__FILE__) . '/recaptchalib.php');
require_once(dirname(__FILE__) . '/utils.php');

if(isset($_GET["recaptcha_response_field"]))
{
				$resp = recaptcha_check_answer (
								$recaptcha_key["private"],
								$_SERVER["REMOTE_ADDR"],
								stripslashes(strip_tags($_GET["recaptcha_challenge_field"])),
								stripslashes(strip_tags($_GET["recaptcha_response_field"]))
				);
				
				$response = array("ok" => false, "message" => "");
				
				if ($resp->is_valid)
				{								
								$response["ok"] = true;
								
								// TODO: filter these variables
								//$url = stripslashes(strip_tags($_GET["url"]));
								$logger->logDebug("Filtering the URL");
								$url = filter_var($_GET['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
								
								// get the space name from the status json (being crawled from the
								// space server by OpenSpaceLint itself server-side)
								// TODO: maybe cache it for some further check purposes?
								$data = get_data($url);
								$space_data = $data->content;
								$space_json = json_decode($space_data);
								$space_name = stripslashes(strip_tags($space_json->{'space'}));

								if(empty($url) || empty($space_name))
								{
												$response["ok"] = false;
												$response["message"] = "Incomplete data were sent to the server.";
								}
								else
								{
												$private_directory = "spacehandlers/directory.json";
												$private_directory_json = file_get_contents($private_directory);
												$private_directory_array = json_decode($private_directory_json, true);
												
												if($private_directory_array[$space_name] === null)
            {
																$logger->logDebug("There's no space '$space_name' yet in the directory");
																
																// add the space to the main directory
																$logger->logDebug("Adding the space to the private directory");
																$private_directory_array[$space_name] = $url;
																$private_directory_json = json_encode($private_directory_array);
																file_put_contents($private_directory, $private_directory_json);
																
                // cache the json
                cache_json_from_argument($space_name, $space_json);
																
																// add the space to the public directory
																// TODO: check if the URL must be replaced by the cache URL
																//       in order to use the cache
																$logger->logDebug("Adding the space to the public directory");
																$public_directory = file_get_contents($private_directory . ".public");
																$public_directory = json_decode($public_directory, true);
																$public_directory[$space_name] = $url;
																$public_directory_json = json_encode($public_directory);
																file_put_contents($private_directory . ".public", $public_directory_json);
																
																// create the cron(s)
																create_new_cron($space_name);
																
																$response["message"] = "The space got added to the directory.";
												}
												else
												{
																$logger->logDebug("There's already a space '$space_name' in the directory");
																$response["message"] = "The space is already in the directory.";                            
												}
								}
				}
				else
								$response["message"] = $resp->error;
				
				$logger->logInfo(
								"Sending this reponse back to the client:\n",
								print_r($response, true)
								);
				echo json_encode($response);        
}