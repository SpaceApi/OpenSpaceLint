<?php

// because we make the code compatible with php 5.2 and we cannot
// use namespaces which makes it difficult to load classes from a package
// within the current class autoloader. in other words, with the class name we
// could not determine a package and so we use functions for each route
// in order to reduce the complexity instead of creating a class (in its
// own package) for every route (which is called delegator in the code)

class Route
{
    public static function execute($delegator, $action, $resource)
    {        
        global $logger;
        
        $logger->logInfo("The controller is run from the ". SAPI ." handler with the route:");
        $logger->logInfo("    delegator => ". $delegator);
        $logger->logInfo("    action    => ". $action);
        $logger->logInfo("    resource  => ". $resource);
    
        if(!empty($_GET))
        $logger->logInfo(
            "Other GET parameters sent to the controller are: ".
            str_replace("Array", "", print_r($_GET,true))
        );
        
        $action_supported = false;
        
        switch($delegator)
        {
            case "jsconfig": // (partly) external use expected     
                
                // this delegator provides the javascript config file config.js
                $action_supported = self::route_jsconfig($delegator, $action, $resource);
                break;
            
            case "cache": // (partly) external use expected
                
                $action_supported = self::route_cache($delegator, $action, $resource);
                break;
            
            case "cron": // internal use only
                
                $action_supported = self::route_cron($delegator, $action, $resource);
                break;
            
            case "directory": // (partly) external use expected
                
                $action_supported = self::route_directory($delegator, $action, $resource);
                break;
            
            case "proxy": // (partly) external use expected
                
                $action_supported = self::route_proxy($delegator, $action, $resource);
                break;
            
            case "status": // (partly) external use expected
            
                $action_supported = self::route_status($delegator, $action, $resource);
                break;
            
            case "apienv": // (partly) external use expected
                
                $action_supported = self::route_apienv($delegator, $action, $resource);                    
                break;
            
            case "specs": // (partly) external use expected
                
                $action_supported = self::route_specs($delegator, $action, $resource);                    
                break;
            
            case "filterkeys": // (partly) external use expected
            
                $action_supported = self::route_filterkeys($delegator, $action, $resource);
                break;
            
            // print the constants defined in this script, not the ones from the config
            // we don't accapt the apache handler to not expose too much information
            case "environment": // internal use only
                
                $action_supported = self::route_environment($delegator, $action, $resource);
                break;        
            
            case "validator":
                
                $action_supported = self::route_validator($delegator, $action, $resource);
                break;
            
            default:
                $logger->logError("No delegator has been set.");
        }
        
        if(!$action_supported)
            $logger->logError("The action ". $action ." is not supported by the ". $delegator ." delegator.");      
    }
    
    private static function route_jsconfig($delegator, $action, $resource)
    {
        switch($action)
        {
            case "get":
                
                global $logger;
        
                // in some javascript files we need certain settings from the
                // config.php so this script here generates the content for
                // config.js to be included before all other javascript files
                // which require these settings.
                
                header('Content-type: application/x-javascript');
                
                echo "var site_url = 'http://". SITE_URL ."';";
                echo "var recaptcha_public_key = '" . RECAPTCHA_KEY_PUBLIC . "';";
                
                // the specs versions
                $specs_versions = array();
                
                foreach(glob( SPECSDIR ."*.json") as $filename)
                {
                    $filename = basename($filename);
                    $specs_versions[] = str_replace(".json", "", $filename);
                }
                
                rsort($specs_versions);
                echo 'var versions = '. json_encode($specs_versions) . ';';
                
                break;
            
            default:
                return false;
        }
        
        return true;
    }
    
    private static function route_cache($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "update":
                
                // we only allow cache updates triggered from cli scripts such as cronjobs
                if(SAPI == "cli")
                    // $resource is the sanitized space name
                    Cache::update($resource);
                else
                    header("Location: http://". SITE_URL . "/error.html");
                
                break;
            
            case "get":
                
                header('Content-type: application/json');
                header('Access-Control-Allow-Origin: *');
                
                $cached = Cache::get($resource);
                
                if(!empty($cached))
                    echo $cached;
                else
                    echo '{ "no": "space"}';
                
                break;
            
            default:
                return false;
        }
        
        return true;
    }
    
    private static function route_cron($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "add":
                
                // only allow the creation of a new cron in the cli handler
                if(SAPI == "cli")
                {
                    // if the resource is 'all' this script was most probably
                    // called from the setup script while we assume that no
                    // space will never ever call itself 'all'.
                    if($resource == "all")
                    {
                        $logger->logNotice("Populating all the cron files");
                        
                        $directory = new PrivateDirectory;
                        
                        foreach($directory->get_stdClass() as $space => $url)
                        {
                            Cron::create($space);
                            $space_api_file = Cache::get_from_cache($space);
                            if(!$space_api_file->has_error())
                                Cron::set_schedule($space, $space_api_file->cron_schedule());
                            else
                                $logger->logWarn("Could not schedule the cron.");
                        }
                    }
                    else
                        // in fact this should never be executed because
                        // the single cron creation is done while a new
                        // hackerspace is added within another delegator
                        // (directory:add)
                        Cron::create($resource);
                }
                
                break;
            
            default:
                return false;
        }
        
        return true;
    }
    
    private static function route_directory($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "get":
                
                header('Content-type: application/json');
                header('Access-Control-Allow-Origin: *');
                
                $directory = new PublicDirectory;                        
                echo $directory->get();
                
                break;
            
            case "add":
                
                if(SAPI == 'cli' && defined("RUNNING_SETUP"))
                {
                    // this should be executed on installation time only
                    // the datatype of $resource may not a string but an
                    // array or an object, see the setup script to be sure
                    
                    $url = filter_var($resource, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);

                    if($url == "")
                    {
                        $logger->logDebug("You provided an empty URL");
                        break;
                    }
                    
                    $space_api_file = new SpaceApiFile($url);
                    $space_name = $space_api_file->name();
                    
                    if($space_api_file->has_error())
                    {
                        echo "Could not add the space \n";
                        $logger->logDebug($space_api_file->error());
                    }
                    else                        
                    {
                        $private_directory = new PrivateDirectory;
                        $public_directory = new PublicDirectory;
                        
                        if(! $private_directory->has_space($space_name))
                        {
                            $private_directory->add_space($space_api_file);
                            $public_directory->add_space($space_api_file);

                            Cron::create($space_api_file->name());
                            Cache::cache($space_api_file);
                            
                            FilterKeys::update();
                            
                            $logger->logDebug("The space got added to the directory.");
                        }
                        else
                            $logger->logDebug("The space is already in the directory.");
                    }
                }
                else
                {
                    // this is executed when somebody adds a space on the website,
                    // when deploying OpenSpaceLint the setup scripts are expected
                    // to have a copy of an existent (and complete) directoy in
                    // the setup directory
                    header('Content-type: application/json');
                    require_once( ROOTDIR . 'c/php/recaptchalib.php');
                    
                    if(isset($_GET["recaptcha_response_field"]))
                    {
                        $resp = recaptcha_check_answer (
                            RECAPTCHA_KEY_PRIVATE,
                            $_SERVER["REMOTE_ADDR"],
                            stripslashes(strip_tags($_GET["recaptcha_challenge_field"])),
                            stripslashes(strip_tags($_GET["recaptcha_response_field"]))
                        );
                        
                        $response = array("ok" => false, "message" => "");
                        
                        if ($resp->is_valid)
                        {
                            // this might be changed to false later
                            $response["ok"] = true;
                            
                            $url = filter_var($_GET['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
                            
                            // in this place we cannot pass a space name since we have only a URL
                            // but this should not be a problem because OpenSpaceLint will only
                            // give the user the possibility to add a space when it's been previosly
                            // validated
                            $space_api_file = new SpaceApiFile($url);
                            $space_name = $space_api_file->name();
                            
                            if($space_api_file->has_error())
                            {
                                $response["ok"] = false;
                                $response["message"] = $space_api_file->error();
                            }
                            else                        
                            {
                                $private_directory = new PrivateDirectory;
                                $public_directory = new PublicDirectory;
                                
                                if(! $private_directory->has_space($space_name))
                                {
                                    $private_directory->add_space($space_api_file);
                                    $public_directory->add_space($space_api_file);
    
                                    Cron::create($space_name);
                                    Cache::cache($space_api_file);
                                    
                                    $response["message"] = "The space got added to the directory.";
                                    
                                    // send an email to the admins
                                    Email::send("New Space Entry: ". $space_name, "",
                                        "The space '" . $space_name . "' has been added to the directory.");
                                }
                                else
                                    $response["message"] = "The space is already in the directory.";                            
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
                }
            
                break;
            
            default:
                return false;
        }
        
        return true;
    }
    
    private static function route_specs($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "get":
                
                header('Content-type: application/json');
                header('Access-Control-Allow-Origin: *');
                
                $specs_file = SPECSDIR . "$resource.json";
                
                if(file_exists($specs_file))
                {
                    $specs = file_get_contents($specs_file);
                    $specs_obj = json_decode($specs);
                    
                    if($specs_obj === null)
                    {
                        $logger->logDebug("Could not decode the specs json.");
                        echo '{"An error occured,":"please file a ticket here: https://github.com/slopjong/openspacelint/issues"}';
                        return;
                    }
                    
                    if(
                        property_exists($specs_obj, "properties") &&
                        property_exists($specs_obj->properties, "cache") &&
                        property_exists($specs_obj->properties->cache, "properties") &&
                        property_exists($specs_obj->properties->cache->properties, "schedule") &&
                        property_exists($specs_obj->properties->cache->properties->schedule, "enum")
                    )
                    {
                        $available_schedules = json_decode(CRON_AVAILABLE_SCHEDULES);
                        
                        if($available_schedules === null)
                        {
                            $logger->logDebug("Could not decode the json with the available schedules from the config");
                            echo '{"An error occured,":"please file a ticket here: https://github.com/slopjong/openspacelint/issues"}';
                            return;
                        }
                        
                        $specs_obj->properties->cache->properties->schedule->enum = $available_schedules;
                    }
                    
                    echo Utils::json_pretty_print(json_encode($specs_obj));
                }
                else
                    echo '{ "You chose a" : "bad specs version." }';
                
                break;
            
            default:
                return false;
        }
        
        return true;
    }
    
    private static function route_apienv($delegator, $action, $resource)
    {
        switch($action)
        {
            case "get":
                
                header('Content-type: application/x-javascript');
                
                $javascript = 'var apienv; $(document).ready(function () { apienv = JSV.createEnvironment("json-schema-draft-03");';
                
                foreach(glob( SPECSDIR ."*.json") as $filename)
                {
                    $json = file_get_contents($filename);
                    $filename = basename($filename);
                    $version = str_replace(".json", "", $filename);
                    $javascript .= 'var specs'. $version .'='. $json .';';
                    $javascript .= 'apienv.createSchema(specs'. $version .', undefined, "http://openspace.slopjong.de/specs'. $version .'");';
                }
                
                $javascript .= '});';
                
                echo $javascript;
                
                break;
            
            default:
                return false;
        }
        
        return true;
    }
    
    private static function route_filterkeys($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "get":
                
                header('Content-type: application/json');
                header('Access-Control-Allow-Origin: *');
                
                if(!file_exists(CACHEDIR . "filter-keys/filter_keys.json"))
                    FilterKeys::update();
                
                echo file_get_contents(CACHEDIR . "filter-keys/filter_keys.json");
                
                break;
            
            default:
                
                return false;
        }
        
        return true;
    }
   
    private static function route_environment($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "get":
                
                if( SAPI == "cli" )                    
                    Utils::print_config(CONFIGDIR . "config.php");
                    
                break;
            
            default:
                
                return false;
        }
            
        return true;
    }
    
    private static function route_proxy($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "get":
                
                // the code here is from the former proxy.php (and modified from the original proxy.php from jsonlint.com)
                
                header('Content-type: application/json');
                
                if(SAPI == 'apache')
                {
                    $url = filter_var($_POST['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
                    
                    // data sent with the GET method?
                    if(empty($url))
                       $url = filter_var($_GET['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
                    
                       
                    if (!$url || !preg_match("/^https?:/i", $url))
                    {
                        $logger->logNotice("Invalid URL provided");
                        echo '{ "result": "Invalid URL. Please check your URL and try again.", "error": true }';
                        exit();
                    }
                    
                    $data_fetch_result = DataFetch::get_data($url);
                    
                    
                    if($data_fetch_result->error_code() == DataFetchResult::BAD_STATUS)
                    {
                        echo '{ "result": "URL returned bad status code ' . $data_fetch_result->http_code() . '.", "error": true }';
                        exit();
                    }
                    
                    
                    if($data_fetch_result->error_code() == DataFetchResult::CONTANT_GREATER_10_MEGS)
                    {
                        echo '{ "result": "URL content length greater than 10 megs (' . $data_fetch_result->content_length() .
                            '). Validation not available for files this large.", "responseCode": "1" }';
                        
                        exit();
                    }
        
                    $data = $data_fetch_result->content();
                    
                    if($data === false || is_null($data))
                    {
                       echo '{ "result": "Unable to fetch your JSON file. Please check your server.", "error": true }';
                       exit();
                    }                    
                    
                    $response = new StdClass();
                    $response->status = $data_fetch_result->http_code();
                    $response->length = $data_fetch_result->content_length();
                    $response->url = $data_fetch_result->url();
                    $response->content = $data_fetch_result->content();
                    
                    echo json_encode($response);
                }
                
                break;
                
            default:
                    
                return false;
        }
        
        return true;
    }
    
    private static function route_status($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "get":
                
                // We go through the list in spaces.json and evaluate the pattern on the given url.
                // A space entry in that file looks like
                //
                // "Shackspace" : {
                //    "url" : "http://shackspace.de/sopen/text/en",
                //    "pattern" : "open",
                //    "inverse" : false
                // }
                //
                // With the inverse field we can specify whether the pattern checks against the
                // open or closed status or not.
                //
                // At the end the status will be appended to the corresponding space json file.
                
                header('Content-type: application/json');
                header('Access-Control-Allow-Origin: *');
                header('Cache-Control: no-cache');
                
                $spaces = file_get_contents(ROOTDIR . "c/spacehandlers/spaces.json");
                $spaces = json_decode($spaces);

                foreach($spaces as $space => $val)
                {
                    if($space == $resource)
                    {
                        $file = ROOTDIR . "c/spacehandlers/". NiceFileName::json($space);
                        $url = $val->url;
                        $pattern = $val->pattern;
                        $inverse = (bool) $val->inverse;
                        
                        // we no longer need to iterate over the other space handlers 
                        break;
                    }
                }

                if(isset($file) && file_exists($file))
                {
                    // we do no checks on the json, we assume it's validated with openspacelint
                    $spacejson = json_decode(file_get_contents($file));

                    // get the actual status if a url is provided
                    if(!empty($url))
                    {
                        $data_fetch_result = DataFetch::get_data($url);
                        $data = $data_fetch_result->content();

                        // the status in this place might still be open or close
                        $status = (bool) preg_match("/$pattern/", $data);

                        // with the inverse flag we know if we were checking the open or closed status
                        if($inverse)
                            $status = ! $status;

                        $spacejson->state->open = $status;
                    }

                    if(! property_exists($spacejson, "logo") ||
                        (property_exists($spacejson, "logo") && empty($spacejson->logo)))
                        $spacejson->logo = "http://" . SITE_URL . "/c/images/space-has-no-logo.png";

                    echo json_encode($spacejson);
                }
                
                break;
            
            default:
                
                return false;
        }
        
        return true;
    }
    
    private static function route_validator($delegator, $action, $resource)
    {
        global $logger;
        
        switch($action)
        {
            case "get":
            
                //$logger->logDebug(print_r($_GET, true));
            
                header('Content-type: application/json');
                header('Access-Control-Allow-Origin: *');
        
                /*
                if(isset($_GET["url"]))
                {
                    echo "url set";
                    exit();
                }
                else
                {
                    echo "url not set";
                    exit();
                }
                */
                
                $url = "";
                
                // did we get a space api url?
                if(isset($_GET["url"]))
                {
                    $url = $_GET["url"];
                    
                    // if the url originally contained a + this will end in a whitespace when
                    // we reach this place here, so turn it back to a + or filter_var will return
                    // an empty string. Examples for urls that possibly have a + are the urls
                    // to spacehandlers such as http://openspace.slopjong.de/status/Chaos+inKL.
                    $url = str_replace(" ", "+", $url);

                    $url = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
                    
                    $logger->logDebug($url);
                }
                
                // or did we get json data or a space name instead?
                if($url != "")
                {
                    $logger->logDebug("Validate a space from its url '$url'");
                    $mixed = $url;
                }
                else
                {                    
                    // when no url is passed the .htaccess then $resource should contain a string
                    // which is hopefully a space name. the cache GET parameter _explicitly_ tells
                    // us if we should validate the cached version
                    if(isset($_GET["cache"]))
                    {
                        $sanitized_space_name = NiceFileName::get($resource);
                        $space_file = "$sanitized_space_name.json";
                        $mixed = file_get_contents(STATUSCACHEDIR . $space_file);
                        
                        if($mixed === false)
                        {
                            echo '{"error" : "'. $resource .' is not cached"}';
                            exit;
                        }                        
                    }
                    // we got either a space name or raw json data if the cache GET parameter
                    // is missing, we're trying to find out  the url from the private directory is taken and validated
                    else
                    {
                        $resource = urldecode($_GET["json"]);
                        
                        if(null !== json_decode($resource))
                        {
                            $mixed = $resource;
                            $logger->logDebug("Validating raw json data from a frontend");
                        }
                        else
                        {
                            $logger->logDebug("Validating a space ('$resource:$mixed') from its name");
                            $sanitized_space_name = NiceFileName::get($resource);
                            $private_directory = new PrivateDirectory;
                            
                            if(!$private_directory->has_space($sanitized_space_name) && !Cache::is_cached($sanitized_space_name))
                            {
                                echo '{"error" : "'. $resource .' is not in the directory nor cached."}';
                                exit;
                            }
                            else if($private_directory->has_space($sanitized_space_name))
                            {
                                $mixed = $private_directory->get_url($sanitized_space_name, true);
                            }
                            else
                            {
                                $mixed = Cache::get($sanitized_space_name);
                            }
                        }
                    }
                }
                
                $space_api_file = new SpaceApiFile($mixed);
                
                if(!$space_api_file->has_error() || $space_api_file->error_code() == SpaceApiFile::OTHER)
                {
                    $space_validator = new SpaceApiValidator;
                    $space_validator->validate($space_api_file);
                    
                    $ret = new stdClass;
                    $ret->space = $space_api_file->name();
                    $ret->draft = "0.".SPECSDRAFTVERSION;
                    $ret->valid = $space_validator->get_valid_versions();
                    $ret->invalid = $space_validator->get_invalid_versions();
                    $ret->errors = $space_validator->get_errors();
                    $ret->warnings = $space_validator->get_warnings();
                    
                    echo Utils::json_pretty_print(json_encode($ret));
                }
                else
                {
                    $logger->logDebug("Space could not be validated: ". $space_api_file->error());
                    echo '{"error" : "URL/JSON doesn\'t provide a space api implementation."}';
                    exit;
                }
                
                break;
            
            default:
                return false;
        }
        
        return true;
    }
}
