<?php

class DataFetch
{
    /**
     * cURLs a website and if open_basedir is set or safe_mode enabled
     * then the FOLLOWLOCATION mode is done "manually".
     * Reused and modified zsalab's implementation.
     */
    private static function curl_exec_follow($ch, &$maxredirect = null, $timeout = 15)
    {
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // timeout in seconds
                    
        $mr = $maxredirect === null ? 5 : intval($maxredirect);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
        {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        }
        else
        {
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
                    
                    if (curl_errno($rch))
                        $code = 0;
                    else
                    {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302)
                        {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                    
                            // if no http(s) present then the new url
                            // is relative and thus nees some extra care
                            if(!preg_match("/^https?:/i", $newurl))
                                $newurl = $original_url . $newurl;
                        }
                        else
                            $code = 0;
                    }
                } while ($code && --$mr);
                    
                curl_close($rch);
                        
                if (!$mr)
                {
                    if ($maxredirect === null)
                        trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.',
                                      E_USER_WARNING);
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
     * @return null Returns null if the data could not be fetched. A JSON with an error message is
     *              output and not returned by the function.
     * @return stdClass object If no error occured an object that contains the status code, length,
     *                  url and the actual data
     *
     */
    public static function get_data($url, $limit = true)
    {
        global $logger;
        $logger->logDebug("Fetching data from '$url'");
        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, CURL_USER_AGENT);
        
        // for faster debugging just comment out the following line
        // to use curl_exec instead of curl_exec_follow
        $follow = true;
        $data = (isset($follow) ) ? self::curl_exec_follow($ch) : curl_exec($ch);
        
        // if $data is NULL or empty then the host might use a non-standard port
        // which is blocked by the shared host's firewall => proxy the proxy :-)
        if(is_null($data) || empty($data))
        {
            // http://evening-snow-4067.herokuapp.com/?url=
            //curl_setopt($ch, CURLOPT_URL, $second_stage_proxy . $url);
            curl_setopt($ch, CURLOPT_URL, SECOND_STAGE_PROXY_URL . $url);
            $data = (isset($follow) ) ? self::curl_exec_follow($ch) : curl_exec($ch);
        }
        
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $contentLength = intval($info['download_content_length']);
        $status = intval($info['http_code']);
        
        // TODO: json is output here, this is not a problem for the proxy delegator
        //       but where else is this method used?
        //       => SpaceApiFile, wherever this class is instantiated the output
        //          could break things
        if ($status >= 400)
        {
            echo '{ "result": "URL returned bad status code ' . $status . '.", "error": true }';
            return null;
        }
        
        // TODO: json is output here, this is not a problem for the proxy delegator
        //       but where else is this method used?
        //       => SpaceApiFile, wherever this class is instantiated the output
        //          could break things
        if ( $limit && $contentLength >= 52428800)
        {
            echo '{ "result": "URL content length greater than 10 megs (' . $contentLength .
            '). Validation not available for files this large.", "responseCode": "1" }';
            return null;
        }
        
        $response = new StdClass();
        $response->status = $status;
        $response->length = $contentLength;
        $response->url = $info['url'];
        $response->content = $data;
        
        return $response;
    }
}