<?php
error_reporting(0);

// load the config
$config = realpath(dirname(__FILE__) . "/../../config.php");
require_once($config);

/**
 * cURLs a website and if open_basedir is set or safe_mode enabled
 * then the FOLLOWLOCATION mode is done "manually".
 * Reused and modified zsalab's implementation.
 */
function curl_exec_follow($ch, &$maxredirect = null, $timeout = 15) {
				
				//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // timeout in seconds
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // timeout in seconds
				
    $mr = $maxredirect === null ? 5 : intval($maxredirect);
    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
    } else {
	
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        if ($mr > 0)
	{
	    $original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $newurl = $original_url;
	    
            $rch = curl_copy_handle($ch);
	    
            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            do
	    {
                curl_setopt($rch, CURLOPT_URL, $newurl);
                $header = curl_exec($rch);
                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                    if ($code == 301 || $code == 302) {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $newurl = trim(array_pop($matches));
			
			// if no http(s) present then the new url
			// is relative and thus nees some extra care
			if(!preg_match("/^https?:/i", $newurl)){
			    $newurl = $original_url . $newurl;
			}	    
			
                    } else {
                        $code = 0;
                    }
                }
            } while ($code && --$mr);
            
	    curl_close($rch);
            
	    if (!$mr)
	    {
                if ($maxredirect === null)
                    trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
                else
                    $maxredirect = 0;
                
                return false;
            }
            curl_setopt($ch, CURLOPT_URL, $newurl);	    
        }
    }
    return curl_exec($ch);
}

/**
 * Loads data from the given URL. If $limit is false data of more than 10 megs can be returned.
 *
 */
function get_data($url, $limit = true)
{
				global $second_stage_proxy;
				
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				
				// for faster debugging just comment out the following line
				// to use curl_exec instead of curl_exec_follow
				$follow = true;
				$data = (isset($follow) ) ? curl_exec_follow($ch) : curl_exec($ch);
				
				// if $data is NULL or empty then the host might use a non-standard port
				// which is blocked by the shared host's firewall => proxy the proxy :-)
				if(is_null($data) || empty($data)){
							// http://evening-snow-4067.herokuapp.com/?url=
							//curl_setopt($ch, CURLOPT_URL, $second_stage_proxy . $url);
							curl_setopt($ch, CURLOPT_URL, $second_stage_proxy . $url);
							$data = (isset($follow) ) ? curl_exec_follow($ch) : curl_exec($ch);
				}
				
				$info = curl_getinfo($ch);
				curl_close($ch);
				
				$contentLength = intval($info['download_content_length']);
				$status = intval($info['http_code']);
				
				if ($status >= 400) {
				echo '{ "result": "URL returned bad status code ' . $status . '.", "error": true }';
				return null;
				}
				
				if ( $limit && $contentLength >= 52428800) {
				echo '{ "result": "URL content length greater than 10 megs (' . $contentLength . '). Validation not available for files this large.", "responseCode": "1" }';
				return null;
				}
				
				$response = new StdClass();
				$response->status = $status;
				$response->length = $contentLength;
				$response->url = $info['url'];
				$response->content = $data;
				
				return $response;
}

/**
 * Recursively remove a directory.
 * 
 * Source: http://us.php.net/manual/en/function.rmdir.php#108113 (modified)
 */
function rrmdir($dir) {
				
				if(null !== $dir && is_dir($dir))
				{
								$glob = glob($dir . '/*');
								if($glob)
								{
												foreach( $glob as $file)
												{
																if(is_dir($file))
																				rrmdir($file);
																else
																				unlink($file);
												}
								}
								
								rmdir($dir);
				}
}

/**
 * Cache one JSON file from a URL. It's considered as a fail if
 * the JSON cannot be fetched from the space server. A report email
 * is then sent.
 *
 * @param string $space The space name
 * @param string $url   The URL to the space's JSON
 * @param boolean $report Flag that tells this function to write a report or not at the end
 */
function cache_json_from_url($space, $url, $send_report_email = false)
{				
				//require_once(dirname(__FILE__) . "/NiceFileName.class.php");
				
				//$file_name = NiceFileName::json($space);
				
				$response = get_data($url);

				// if the response _and_ the data are not null, empty or false
				// write it to the file
				//
				// note: I know I shouldn't do write such a condition, but hey ...
				//       ... why not to write it as such one time?
				if(	true
								&& $response !== null
								&& (($data = $response->content) !== false)
								&& (null !== json_decode($data))
								)
				{
								//file_put_contents("cache/". $file_name, $data);
								cache_json_from_argument($space, $data);
								$success = true;
				}
				else
								$success = false;
								
				// should we write a report?
				if($send_report_email)
				{
								require_once("CacheReport.class.php");
								$reportfile = new CacheReport();
								$reportfile->report($space, $success);
				}
}

/**
 * Cache one JSON passed as an argument.
 */
function cache_json_from_argument($space, $data)
{
				require_once(dirname(__FILE__) . "/NiceFileName.class.php");
				
				$cache_file_name = NiceFileName::json($space);
				$cache_file_path = dirname(__FILE__) . "/cache";
				$cache_file = $cache_file_path . "/". $cache_file_name;
				
				switch(gettype($data))
				{
								case "string":
												
												// check if the string is really a json
												if( null !== json_decode($data) )
																file_put_contents($cache_file, $data);												
												break;
								
								case "array":
								case "object":
												
												file_put_contents($cache_file, json_encode($data));
												break;
								
								default:
												
												; // do nothing
				}
				
				$lists = list_space_array_keys();
				file_put_contents("cache/array_keys.json", json_encode($lists));
}

/**
 * Renews the cache by downloading the all the space API JSONs
 * saved in the directory.
 *
 * If $full is true then the cache is updated at once.
 */
function update_cache($full = false){
				
				$file = "spacehandlers/directory.json";
				$dirjson = file_get_contents($file);
				$directory = json_decode($dirjson, true);
				
				if($full)
				{
								// empty the cache folder
								rrmdir("cache");
								mkdir("cache");
								
								//*
								// iterate over the directory and load all the JSONs
								foreach($directory as $space => $url)
								{
												// try to fork the process to speed up the caching
												if(function_exists("pcntl_fork"))
												{
																//trigger_error ( string $error_msg [, int $error_type = E_USER_NOTICE ] )
																
																// fork the current process
																switch($pid = pcntl_fork()){
																
																				case -1:
																								
																								// @fail
																								
																								die('could not fork');
																								break;
																				
																				case 0:
																								
																								// @child
																																					
																								cache_json_from_url($space, $url);																			
																								break;
																				
																				default:
																								
																								// @parent
																								
																								pcntl_wait($status); //Protect against Zombie children
																								
																}
												}
												else
																cache_json_from_url($space, $url);
								}
								//*/
				}
				else
				{
								// TODO: implement the single json cache
				}
}


/**
 * Iterate over the passed associative array and find
 * all array key combinations with the nested ones
 * concatenated with a dot.
 *
 * E.g. $arr[a][b] would result in the found keys a and a.b
 *
 * a=contact
 * b=phone
 * -------------
 * nested keys are: contact and contact.phone
 *
 * Numerical indices are skipped and don't appear in any concatenated key.
 * 
 */
function space_array_keys($array, &$keys, $ancestor_key = "")
{			
				foreach($array as $key => $value){
								
								// skip the numerical indices
								if(!is_numeric($key))
												$new_key = $ancestor_key
																				. ((!empty($ancestor_key)) ? "." : "")
																				. $key;
								else
												$new_key = $ancestor_key;
											
								// don't push the key if it is already present
								if(!in_array($new_key, $keys))									
												array_push($keys, $new_key);
												
								if(is_array($value))
												space_array_keys($value, $keys, $new_key);									
				}				
}


/**
 * Create an array with two lists listing what member a space uses.
 * The first array element contains a list sorted to the spaces
 * and the second is sorted to the members.
 */
function list_space_array_keys()
{
				$sorted_to_space = array();
				$sorted_to_member = array();
				
				// create a list of what members a certain space supports
				foreach (glob("cache/*.json") as $filename)
				{
								// be sure to not include the filters list itself
								// TODO: move the filters list file to another place
								if(! preg_match("~array_keys~", $filename))
								{
												$json = json_decode(file_get_contents($filename), true);				
												$members = array();
												space_array_keys($json, $members);
												$sorted_to_space[$json["space"]] = $members;
								}
				}
				
				// Create a list of what space uses a certain member.
				// Each element is an array containing spaces.
				foreach ($sorted_to_space as $space => $members)
				{
								foreach($members as $member)
								{
												$val = $sorted_to_member[$member];
												if(empty($val))
																$val = array();
																
												array_push($val, $space);
												$sorted_to_member[$member] = $val;
								}
				}
				
				/*
				foreach($sorted_to_space as $space => $keys)
								$sorted_to_member = array_merge($sorted_to_member, $keys);
				
				$sorted_to_member = array_unique($sorted_to_member);
				sort($sorted_to_member);
				*/
				
				return array($sorted_to_space, $sorted_to_member);
}

/**
 * Removes the execution in all the scron directories and
 * adds it according the passed cron schedule
 */
function change_scron_schedule($space, $cron_schedule)
{
				$cron_path = realpath(dirname(__FILE__) . "/../../cron/");
				
				// do nothing if the directory can't be read
				if(! $cron_path_handle = opendir($cron_path))
								return;
				
				$nice_file_name = preg_replace("/[^a-zA-Z0-9]/i", "_", $space);
				$nice_file_name = strtolower($nice_file_name);
				
				while (false !== ($schedule_dir = readdir($cron_path_handle)))
				{
								if ( strpos($schedule_dir, "scron") === 0 )
								{
												$schedule_dir = $cron_path . "/" . $schedule_dir;
												$cron = $schedule_dir . "/" . $nice_file_name;
												chmod($cron, 0644);
								}
				}
				
				chmod($cron_path . "/scron." . $cron_schedule . "/" . $nice_file_name, 0755);
}


/**
 * Creates a new cron for a space.
 */
function create_new_cron($space, $cron_schedule = "d.01")
{
				$cron_path = realpath(dirname(__FILE__) . "/../../cron/");
				$cron_template_file = $cron_path . "/cron_template";
				
				// do nothing if the directory can't be read
				if(! $cron_path_handle = opendir($cron_path))
								return;
				
				if(file_exists($cron_template_file))
				{
								$cron_template = file_get_contents($cron_template_file);
								$new_cron = str_replace("SSS", $space, $cron_template);
        
        $nice_file_name = preg_replace("/[^a-zA-Z0-9]/i", "_", $space);
        $nice_file_name = strtolower($nice_file_name);
        
								while (false !== ($schedule_dir = readdir($cron_path_handle)))
								{
												if ( strpos($schedule_dir, "scron") === 0 )
												{
																$schedule_dir = $cron_path . "/" . $schedule_dir;
																$cron = $schedule_dir . "/" . $nice_file_name;
																file_put_contents($cron, $new_cron);			
												}
								}
								
								change_scron_schedule($space, $cron_schedule);    
				}
}

?>