<?php

class SpaceApiFile
{
    const NONE = 0;
    const COULD_NOT_DECODE = 1;
    const OTHER = 2;
    
    private $version = "";    
    private $space_name = "";
    private $contact_email = "";
    private $json = null;
    private $cron_schedule = CRON_DEFAULT_SCHEDULE;
    private $error_message = "";
    private $error_code = 0;
    private $has_error = false;
    private $status_url = "";
    
    /**
     * Represents a minimal space api json with the data required for the CacheReport class.
     *
     * Either a URL or a json stdClass object can be passed to the constructor. Though only
     * in rare cases it makes sense to pass a URL e.g. when a user adds a space. The delegator
     * handler 'directory' in the controller makes use of it and passes the instance to
     * Cache, PrivateDirectory and PublicDirectory.
     *
     * @param string $mixed Can be a URL or a json string.
     * @param string $space_name A space name that can be additionally passed. It must not be sanitized.
     *                           If $mixed is a URL the space name cannot be determined
     *                           from the json.
     */
    public function __construct($mixed, $space_name = "")
    {
        global $logger;
        
        $this->space_name = $space_name;
        
        // check if $mixed is a URL
        $url = filter_var($mixed, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
        if($url === false)
        {
            // $mixed is not a URL
            $json = json_decode($mixed);
            
            if($json === null)
            {
                $this->set_error("Could not decode the passed (json) data");
                $this->error_code = self::COULD_NOT_DECODE;
                return;
            }
             
            $this->set_members($json);   
        }
        else
        {
            // $mixed is a URL
            
            $this->status_url = $url;
            
            $data_fetch_result = DataFetch::get_data($url);
            
            if($data_fetch_result->error_code() == DataFetchResult::BAD_STATUS)
            {
                $msg = "The server returned " . $data_fetch_result->http_code();
                $logger->logNotice($msg);
                $this->set_error($msg);
                return;
            }

            if($data_fetch_result->error_code() == DataFetchResult::CONTANT_GREATER_10_MEGS)
            {
                $msg = "The json data are greater than 10 megs";
                $logger->logNotice($msg);
                $this->set_error($msg);
                return;
            }   
                
            $data = $data_fetch_result->content();
            
            if($data === null)
            {
                $msg = "No data could be loaded from the server.";
                $logger->logNotice($msg);
                $this->set_error("No data could be loaded from the server.");
                return;
            }
            
            $obj = json_decode($data);
            
            if($obj === null)
            {
                $msg = "The json could not be processed.";
                $logger->logNotice($msg);
                $this->set_error($msg);
                return;
            }
             
            $this->set_members($obj);
        }
    }
    
    
    /**
     * Sets the members to the values definedin the space api json.
     * 
     * @param stdClass object $json A space api json
     */
    private function set_members($obj)
    {
        $this->json = $obj;
        $this->version = $obj->api;
        $this->space_name = $obj->space;
        
        if(property_exists($obj, "contact") && property_exists($obj->contact, "email"))
            $this->contact_email = $obj->contact->email;
        
        
        // set the cron schedule if one is set and allowed to be used
        if(property_exists($obj, "cache") && property_exists($obj->cache, "schedule"))
        {
            $allowed_schedules = json_decode(CRON_AVAILABLE_SCHEDULES);
            if(in_array($obj->cache->schedule, $allowed_schedules))
                $this->cron_schedule = $obj->cache->schedule;
        }
        
        // an empty space name is not permitted
        if(empty($this->space_name))
        {
            $this->set_error("The space name must not be empty!");
            $this->error_code = SpaceApiFile::OTHER;
        }
    }
    
    
    /**
     * This method is called when an error occured. A general error message
     * and the error flag will be set. See has_error();
     */
    private function set_error($msg)
    {
        $this->error_message = $msg;
        $this->has_error = true;
    }
    
    
    /**
     * Returns the error code.
     */
    public function error_code()
    {
        return $this->error_code;
    }
    
    
    /**
     * Returns the contact email address of the space.
     */
    public function email()
    {
        return $this->contact_email;
    }
    
    
    /**
     * Returns the (non-sanitized) space name.
     */
    public function name()
    {
        return $this->space_name;
    }
    
    
    /**
     * Returns the implemented space api version.
     */
    public function version()
    {
        return $this->version;
    }
    
    
    /**
     * Returns the implemented space api version without the leading 0.
     */
    public function real_version()
    {
        return str_replace("0.", "", $this->version);
    }
    
    
    /**
     * Returns the status URL if SpaceApiFile was created from a URL.
     * An empty string is returned otherwise.
     */
    public function status_url()
    {
        return $this->status_url;
    }
    
    
    /**
     * Returns true if an error occured.
     */
    public function has_error()
    {
        return $this->has_error;
    }
    
    
    /**
     * Returns the error message.
     */
    public function error()
    {
        return $this->error_message;
    }
    
    
    /**
     * Returns the cron schedule.
     */
    public function cron_schedule()
    {
        return $this->cron_schedule;
    }
    
    
    /**
     * Returns the json object.
     */
    // TODO: $this->json is not a json but an object
    //       don't mix up the terminology of json and the deserialized json
    public function json()
    {
        return $this->json;
    }
}