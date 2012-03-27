<?php
error_reporting(0);
header('Content-type: application/json');

/**
 * cURLs a website and if open_basedir is set or safe_mode enabled
 * then the FOLLOWLOCATION mode is done "manually".
 * Reused and modified zsalab's implementation.
 * http://www.php.net/manual/de/function.curl-setopt.php#102121
 */
function curl_exec_follow($ch, &$maxredirect = null) {
    
    $mr = $maxredirect === null ? 5 : intval($maxredirect);
    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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

/************************************************************************************/

$url = filter_var($_POST['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);

if (!$url || !preg_match("/^https?:/i", $url)) {
    echo '{ "result": "Invalid URL. Please check your URL and try again.", "error": true }';
    return;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// for faster debugging just comment out the following line
// to use curl_exec instead of curl_exec_follow
$follow = true;
$data = (isset($follow) ) ? curl_exec_follow($ch) : curl_exec($ch);

$info = curl_getinfo($ch);
curl_close($ch);

if($data === false) {
	echo '{ "result": "Unable to parse URL. Please check your URL and try again.", "error": true }';
	return;
}

$contentLength = intval($info['download_content_length']);
$status = intval($info['http_code']);

if ($status >= 400) {
	echo '{ "result": "URL returned bad status code ' . $status . '.", "error": true }';
	return;
}

if ($contentLength >= 52428800) {
	echo '{ "result": "URL content length greater than 10 megs (' . $contentLength . '). Validation not available for files this large.", "responseCode": "1" }';
	return;
}

$response = new StdClass();
$response->status = $status;
$response->length = $contentLength;
$response->url = $info['url'];
$response->content = $data;

echo json_encode($response);

?>