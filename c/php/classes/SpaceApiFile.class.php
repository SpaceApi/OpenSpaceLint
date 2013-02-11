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
     */
    public function __construct($mixed)
    {
        global $logger;
        
        // check if $mixed is a URL
        $url = filter_var($mixed, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
        if($url === false)
        {
            // $mixed is not a URL
            $json = json_decode($mixed);
            
            if($json === null)
            {
                $this->set_error("Could not decode the passed (json) data");
                $this->error_code = COULD_NOT_DECODE;
                return;
            }
             
            $this->set_members($json);   
        }
        else
        {
            // $mixed is a URL
            
            $this->status_url = $url;
            
            $data = DataFetch::get_data($url);
            
            if($data === null)
            {
                $this->set_error("No data could be loaded from the server.");
                return;
            }
            
            $json = json_decode($data->content);
            
            // 
            if($json === null)
            {
                $this->set_error("The json could not be processed.");
                return;
            }
             
            $this->set_members($json);
            
        }
    }
    
    
    /**
     * Sets the members to the values definedin the space api json.
     * 
     * @param stdClass object $json A space api json
     */
    private function set_members($json)
    {
        $this->json = $json;
        $this->version = $json->api;
        $this->space_name = $json->space;
        
        if(property_exists($json, "contact") && property_exists($json->contact, "email"))
            $this->contact_email = $json->contact->email;
        
        
        // set the cron schedule if one is set and allowed to be used
        if(property_exists($json, "cache") && property_exists($json->cache, "schedule"))
        {
            $allowed_schedules = json_decode(CRON_AVAILABLE_SCHEDULES);
            if(in_array($json->cache->schedule, $allowed_schedules))
                $this->cron_schedule = $json->cache->schedule;
        }
        
        // an empty space name is not permitted
        if(empty($this->space_name))
            $this->set_error("The space name must not be empty!");
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
     * Returns the space api version.
     */
    public function version()
    {
        return $this->version;
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
    public function json()
    {
        return $this->json;
    }
}