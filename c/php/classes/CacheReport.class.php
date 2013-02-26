<?php

// TODO: rework the comments in this class

/**
 * Represents a report file in <docroot>/cron/reports
 */
class CacheReport
{
    private $reports_path = "";
	private $filename = "";
    private $error = "";
    private $space_name = "";
    
    // these are the bits written to the report file
    private $last_update = "";
    private $last_update_ts = 0;
    private $last_email_sent = "";
    private $last_email_sent_ts = 0;
    private $fail_counter = 0;
    private $email = "";
	
	
    /**
     * Creates an Instance of CacheReport representing a file in the
     * reports directory.
     * 
     * @param SpaceApiFile $new_space_api_file The new space api file that should be cached
     * @param SpaceApiFile $old_space_api_file The space api file from the cache
     */
    function __construct($new_space_api_file, $old_space_api_file)
    {
        global $logger;
        
        if($new_space_api_file === null)
        {
            $logger->logWarn("The passed space api file is null");
            return;
        }
        
        $space_name = $new_space_api_file->name();
        
		$this->space = $space_name;
        $this->reports_path = CACHEREPORTSDIR;
		
		// if the reports directory exists, try to load the space report
		if(is_dir($this->reports_path))
		{
            $logger->logDebug("Initializing the report");
            
			$nice_file_name = NiceFileName::json($space_name);
			$this->filename = $this->reports_path . $nice_file_name;
			
			if(file_exists($this->filename))
			{
                $logger->logDebug("Loading the last report file: ". $this->filename );
                
				$last_report = file_get_contents($this->filename);
                
                if($last_report === false)
                    $logger->logDebug("The last report file could not be loaded");
                
				$last_report = json_decode($last_report);
                
                if($last_report === null)
                    $logger->logDebug("The report file could not be decoded");
                else
                {
                    // load the data from the report file
                    $this->last_update = $last_report->last_update;
                    $this->last_update_ts = $last_report->last_update_ts;
                    $this->last_email_sent = $last_report->last_email_sent;
                    $this->last_email_sent_ts = $last_report->last_email_sent_ts;
                    $this->fail_counter = $last_report->fail_counter;
                    $this->email = $last_report->email;   
                }
			}
            
            // set the new email if one is available
            if(
               ! $new_space_api_file->has_error() &&
               ! $new_space_api_file->email() == "" &&
                 $new_space_api_file->email() != $this->email
               )
            {
                $this->email = $new_space_api_file->email();
            }
            
            // If the email is still not set, let's see if we find one in the cache.
            // This check can't be left out since a report file could have been deleted
            // in the meantime while a space has removed the email field from their json.
            if(
                 $old_space_api_file !== null &&
               ! $old_space_api_file->has_error() &&
                 $old_space_api_file->email() != "" &&
                 $old_space_api_file->email() != $this->email
               )
            {
                $this->email = $old_space_api_file->email();
            }              
		}
    }
    
    public function has_error()
    {
        return empty($this->error);
    }
    
    public function error()
    {
        return $this->error;
    }
	
    /**
     * Reports of how successful the json could be updated.
     * If the report file for $space does not exist a new
     * report file in <docroot>/cron/reports will be created.
     *
     * Every 23 hours a report email is sent to the space and
     * to the BCC if the fail counter is greater zero.
     *
     */
    public function report($success)
    {
        global $logger;
        
		if($success)
        {
            $logger->logDebug("The cache fail counter is reset to 0");
			$this->fail_counter = 0;
        }
		else
        {
            $logger->logDebug("The cache fail counter is incremented by 1");
			$this->fail_counter++;
        }
        
        $update_diff = time() - $this->last_update_ts;
        
    	// today's date and timestamp where the report got updated
		$this->last_update = date("Y l jS");
		$this->last_update_ts = time();
        
        $write_success = $this->write_to_file();
        
        if($write_success === false)
            $logger->logWarn("Could not write to the report file");
        
		// send a report email after one day if an issue hasn't been resolved in the meantime
		// 82800 seconds are 23 hours.
		if(
            $write_success &&
			$this->fail_counter > 0 &&
			$update_diff > 82800
		)
		{
			$this->send_mail();
		}
    }
	
	/**
	 * Writes the report to the file.
	 */
	private function write_to_file()
	{
        global $logger;
        
        $report = new stdClass;
        $report->last_update = $this->last_update;
        $report->last_update_ts = $this->last_update_ts;
        $report->fail_counter = $this->fail_counter;
        $report->last_email_sent = $this->last_email_sent;
        $report->last_email_sent_ts = $this->last_email_sent_ts;
        $report->email = $this->email;

		$json = json_encode($report);
        $json = Utils::json_pretty_print($json);
        
        $logger->logDebug("Writing to ". $this->filename .":\n$json");
        
		return file_put_contents($this->filename, $json);
	}
	
	
	/**
	 * Sends an email if an error got reported on the next schedule.
	 */
	// to make the mailer more platform-independent, use PHPmailer
	// https://code.google.com/a/apache-extras.org/p/phpmailer/
	public function send_mail()
	{
        global $logger;
        
        $receiver = "";
        
        if(CACHE_REPORT_SENDEXTERNAL && $this->email != "")
            $receiver = $this->email;
		

		Email::send("Space API Issue — ". $this->space, $receiver, <<<EOF
Hi $this->space,

there's an issue with your Space API. OpenSpaceLint tried to fetch your JSON in the last 24 hours but your server didn't response.

Please have a look at it.

Cheers,
slopjong
EOF
		);
        
        // today's date and timestamp where the report email got sent
		$this->last_email_sent = date("Y l jS");
		$this->last_email_sent_ts = time();
        
        $this->write_to_file();
	}
    
    
    /**
     * Returns the number of cache fails.
     */
    public function fail_counter()
    {
        return $this->fail_counter;
    }
}