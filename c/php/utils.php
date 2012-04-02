<?php
error_reporting(0);

require_once("../../config.php");

/**
 * cURLs a website and if open_basedir is set or safe_mode enabled
 * then the FOLLOWLOCATION mode is done "manually".
 * Reused and modified zsalab's implementation.
 */
function curl_exec_follow($ch, &$maxredirect = null, $timeout = 7) {
				
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
 */
function get_data($url, $limit = true){
				
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
 * Cache one JSON file.
 */
function cache_json($space, $url){
				
				// filter some characters which could cause some trouble
				// instead of preg_replace strtr() would be an alternative
				$file_name = preg_replace("/[^a-zA-Z0-9]/i", "_", $space);
				$file_name = strtolower($file_name) . ".json";
				
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
								file_put_contents("cache/". $file_name, $data);
				}
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
																																					
																								cache_json($space, $url);																			
																								break;
																				
																				default:
																								
																								// @parent
																								
																								pcntl_wait($status); //Protect against Zombie children
																								
																}
												}
												else
																cache_json($space, $url);
								}
								//*/
				}
				else
				{
								// TODO: implement the single json cache
				}
}

?>