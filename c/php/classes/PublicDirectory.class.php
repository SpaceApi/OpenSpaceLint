<?php

class PublicDirectory extends SpaceDirectory
{    
    /**
     * Creates an instance of the public directory.
     */
    public function __construct()
    {
        parent::__construct("public");
    }
    
    /**
     * Adds a space to the public directory. The passed
     * SpaceApiFile is exptected to have the status URL set.
     * 
     * This method checks if a cron schedule is set in the space api file. In
     * that caase the cache URL will be used instead of the direct URL.
     * 
     * @param SpaceApiFile $space_api_file A space api file
     * @param bool $force_update Forces an update
     */
    public function add_space($space_api_file, $force_update = false)
    {
        global $logger;
        
        $space_name = $space_api_file->name();
        $json = $space_api_file->json();
        
        if( (! parent::has_space($space_name)) || $force_update)
        {
            if($json !== null && property_exists($json, "cache"))
                $url = "http://". SITE_URL . "/cache/" . urlencode($space_name);
            else
                $url = $space_api_file->status_url();
            
            if($force_update)
                $logger->logNotice("The following space URL will be updated: '$url'");
                
            parent::add_space($space_name, $url);
        }
        else
            $logger->logNotice("The space '$space_name' is already in the public directory");

    }
    
    
    /**
     * Updates the url for a space.
     *
     * @param string $space_name A space name. It must not be sanitized!
     * @param string $url The url which should be set for a space
     * @todo This method was introduced after add_space() supported the update flag.
     *       Use this method explicitly if a url should be updated.
     */
    public function update($space_name, $url)
    {
        global $logger;
        $logger->logInfo("Updating the url for '$space_name' ($url)");
        
        $url = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
        
        if($this->has_space($space_name))
            $this->dir_array[$space_name] = $url;
        
        $this->save($this->dir_array);
    }
    
    /**
     * Removes a space from the public directory.
     * 
     * @param string $space_name A space name. It must not be sanitized!
     */
    public function remove($space_name)
    {
        global $logger;
        $logger->logInfo("Removing '$space_name' from the directory");
        
        if(isset($this->dir_array[$space_name]))
            unset($this->dir_array[$space_name]);
        
        $this->save($this->dir_array);
    }
    
}