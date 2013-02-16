<?php

class Cron
{
    /**
     * Creates a new cron for a space. This function should only be called if
     * OpenSpaceLint is deployed on a new server or after a space got added to
     * the directory.
     *
     * It sets the schedule according what's defined in the space json. If
     * none is defined the default cron schedule from the config file will be
     * used.
     *
     * @param string $space A non-urlencoded space name
     */
    // TODO: fetch the schedule from the cached space api json
    // TODO: the $cron_schedule argument is obsolete. Check if it's
    //       used in a function call somewhere and remove it then
    public static function create($space, $cron_schedule = "d.01")
    {
        global $logger;
        
        $logger->logInfo("Creating a cron for '$space'");

        $cron_path = CRONDIR;
        $cron_template_file = $cron_path . "/cron_template";
        
        // do nothing if the directory can't be read
        if(! $cron_path_handle = opendir($cron_path))
        {
            global $logger;
            $logger->logEmerg("Could not open the cron directory handler");
            return;
        }
        
        if(file_exists($cron_template_file))
        {
            $cron_template = file_get_contents($cron_template_file);
            $cron_file_content = str_replace("SSS", $space, $cron_template);

            $nice_file_name = NiceFileName::get($space);

            while (false !== ($schedule_dir = readdir($cron_path_handle)))
            {
                if ( strpos($schedule_dir, "scron") === 0 )
                {
                    $schedule_dir = $cron_path . "/" . $schedule_dir;
                    $cron_file = $schedule_dir . "/" . $nice_file_name;
                    $write_success = file_put_contents($cron_file, $cron_file_content);
                    
                    if($write_success === false)
                        $logger->logError("Could not create the cron '$cron_file'");
                }
            }   
        }
    }

    /**
     * Sets a schedule for cache updates. It removes the execution bit
     * in all the scron directories and adds it according the passed
     * cron schedule.
     *
     * @param string $space A space name. It can be sanitized or the original one.
     * @param string $cron_schedule A cron schedule
     */
    public static function set_schedule($space, $cron_schedule)
    {
        global $logger;
        $logger->logInfo("Schedule '$space' with the interval '$cron_schedule'");
        
        // CRONDIR has a trailing slash
        $cron_path = CRONDIR;
        
        // do nothing if the directory can't be read
        if(! $cron_path_handle = opendir($cron_path))
            return;
        
        // the crons have no file extension
        $nice_file_name = NiceFileName::get($space);
        
        while (false !== ($schedule_dir = readdir($cron_path_handle)))
        {
            if ( strpos($schedule_dir, "scron") === 0 )
            {
                $schedule_dir = $cron_path . $schedule_dir;
                $cron = $schedule_dir . "/" . $nice_file_name;
                chmod($cron, 0644);
            }
        }
        
        chmod($cron_path . "/scron." . $cron_schedule . "/" . $nice_file_name, 0755);
    }

    
    /**
     * Returns the current schedule for a space name. An empty string is returned
     * if it could not be determined.
     *
     * @param string $space_name A space name which can be sanitized or not
     */
    private static function get_current_schedule($space_name)
    {
        global $logger;
        
        $file_name = NiceFileName::get($space_name);
        $available_schedules = json_decode(CRON_AVAILABLE_SCHEDULES, true);
        foreach($available_schedules as $index => $schedule)
        {
            $file = CRONDIR . "scron.$schedule/$file_name";
            if(file_exists($file))
            {
                $fileperms = fileperms($file);
                $fileperms = substr(sprintf('%o', fileperms($file)), -4);
                if($fileperms == "0755")
                    return $schedule;
            }
            else
                $logger->logDebug("$file doesn't exist");
        }
        
        return "";
    }
    
    /**
     * Reschedules a space cron with a bigger cron interval. If the schedule was
     * 'every 2 minutes' then it will be rescheduled to 'every 5 minutes'.
     * 
     * @param string $space_name A space name.
     */
    public static function rotate_schedule($space_name)
    {
        global $logger;
        
        //$space_name = $space_api_file->name();
        $available_schedules = json_decode(CRON_AVAILABLE_SCHEDULES, true);
        $current_schedule = self::get_current_schedule($space_name);
        
        $logger->logDebug("The current schedule is '$current_schedule'");
        
        $index = array_search($current_schedule, $available_schedules);
        
        if($index === false)
        {
            $logger->logError("The current schedule is not allowed");
            // we set the index to max
            $next_index = count($available_schedules) - 1;
        }
        else
        {
            $next_index = $index + 1;
            if($next_index >= count($available_schedules))
                $next_index = count($available_schedules) - 1;
        }
        
        self::set_schedule($space_name, $available_schedules[$next_index]);
    }

}