<?php

class Cache
{
    // we don't allow instances, the constructor must explicitly be
    // defined because the cache function will used as the constructor
    // otherwise
    private function __construct() {}
    
    
    /**
     * Returns the content of the cached space status file.
     * 
     * @param string $space_name A (url-decoded) space name
     */
    public static function get($space_name)
    {
        $cache_file = STATUSCACHEDIR . NiceFileName::json($space_name);
        
        // return the cached json if it's present in the directory (whitelisting)
        if( file_exists($cache_file) )
        {
            global $logger;
            return file_get_contents($cache_file);
        }
                
        global $logger;        
        $logger->logWarn("The cached status file of '$cache_file' does not exist");

        return "";
    }
    
    
    /**
     * Updates a single space api json. It fetches the space json from the server
     * and delegates an instance of the SpaceApiFile class to the cache method which
     * actually writes the json to the cache file.
     *
     * Every update will be reported to the dedicated space report file.
     *
     * It's the responsibility of the CacheReport to increment the fail counter and
     * send a report email to a space when it's necessary and appropriate.
     * 
     * @param $sanitized_space_name   if the space name originally is Pumping Station: One
     *                                $sanitized_space_name will be pumping_station__one
     */
    public static function update($sanitized_space_name)
    {
        if(SAPI == "cli")
        {
            global $logger;
            
            $directory = new PrivateDirectory;
            
            // continue only if the space name is in the directory
            if(!$directory->has_space($sanitized_space_name, true))
            {
                $logger->logWarn("'$sanitized_space_name' is not in the directory");
                return;
            }
            
            $url = $directory->get_url($sanitized_space_name, true);
            
            $space_name = $directory->get_original_space_name($sanitized_space_name);
            
            // when the json cannot be loaded from the URL we need to set
            // a proper space name in order to let the cron rotator work properly
            $space_api_file = new SpaceApiFile($url, $space_name);
            self::cache($space_api_file);
            
            FilterKeys::update();
        }
    }
    
    /**
     * Returns an instance of SpaceApiFile representing a cached space json.
     * 
     * @param string $space_name A space name. It can be sanitized or the original one.
     */
    public static function get_from_cache($space_name)
    {        
        $space_json_file = STATUSCACHEDIR .
                NiceFileName::json($space_name);
        
        $json_content = "";
        
        if(file_exists($space_json_file))
            $json_content = file_get_contents($space_json_file);
        
        return new SpaceApiFile($json_content);
    }
    
    
    /**
     * Returns true if a space is cached.
     * 
     * @param string $space_name A space name, it can be sanitized.
     */
    public static function is_cached($space_name)
    {
        $space_api_file = self::get_from_cache($space_name);
        
        // we consider every error code other than NONE as 'not cached'
        switch($space_api_file->error_code())
        {
            case SpaceApiFile::NONE:
                return true;
            default:
                return false;
        }
    }
    /**
     * Writes the json from a SpaceApiFile to the cache.
     * 
     * @param SpaceApiFile $space_api_file A space api file
     * @todo Report the success in the report file
     */
    public static function cache($space_api_file)
    {
        global $logger;
        
        $space_name = $space_api_file->name();
        
        $old_space_api_file = self::get_from_cache($space_name);
        $cache_report = new CacheReport($space_api_file, $old_space_api_file);
        
        if(!$space_api_file->has_error())
        {
            $cache_file = CACHEDIR. "status/" . NiceFileName::json($space_name);
            $json = json_encode($space_api_file->json());
            
            $write_success = file_put_contents($cache_file, $json);
            
            if($write_success === false)
                $logger->logError("Could not write the space api file to the cache");
            
            $public_directory = new PublicDirectory;
            
            // with the second argument we force the update
            $public_directory->add_space($space_api_file, true);
            
            Cron::set_schedule($space_name, $space_api_file->cron_schedule());

            // TODO: recreate the filterkey list in the cache and point .htaccess to it
            
            $logger->logNotice("Cached '$space_name'");
            $cache_report->report(true);
        }
        else
        {   
            $logger->logWarn("The space api file has an error. '$space_name' could not be cached.");
            $cache_report->report(false);
            
            $allowed_schedules = json_decode(CRON_AVAILABLE_SCHEDULES);
            
            // on a failure we schedule
            if($allowed_schedules !== null && $cache_report->fail_counter() == 0)
                Cron::set_schedule($space_api_file->name(), $allowed_schedules[0]);
            else
                // if a space is scheduled with a small interval we increase the performance of run-parts by
                // rescheduling a cron on a failure. If this is not done and if there are many space crons
                // with a small interval new triggers could overlap.
                Cron::rotate_schedule($space_api_file->name());
         
            // When a space api file has an error, we must check if there's a cached version
            // in order to replace the url in the public directory with the cache url.
            // If there's no cached version then remove the space from the public directory.
            
            $public_directory = new PublicDirectory;
            if(self::is_cached($space_name))
                $public_directory->update($space_name, "http://" . SITE_URL . "/cache/" . urlencode($space_name));
            else
                // if there is no cached version remove the space from the directory
                $public_directory->remove($space_name);
        }
    }
    
}