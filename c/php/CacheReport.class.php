<?php

$config = realpath( __DIR__ . "/../../config.php");
require_once($config);

// TODO: this is considered as a workaround, find out why the require_once doesn't work here
if(!isset($debug_mode))
	require($config);
	
error_reporting( ($debug_mode) ? E_ALL : 0 );

/**
 * Represents a report file in <docroot>/cron/reports
 */
class CacheReport
{
    private $reports_path = "";
	private $filename = "";
	private $report = NULL;
	private $loaded = false;
	private $space = "";
	
	
    /**
     * PHP5 style constructor
     *
     * @since 1.0
     *
     * @return void
     */
    function __construct($space)
    {		
		require_once(dirname(__FILE__) . "/NiceFileName.class.php");
		
		$this->space = $space;
        $this->reports_path = realpath(dirname(__FILE__) . "/../../cron/reports");
		
		// if the reports directory exists, try to load the space report
		if(!empty($this->reports_path))
		{
			$nice_file_name = NiceFileName::json($space);
			$this->filename = $this->reports_path . "/" . $nice_file_name;
			
			if(file_exists($this->filename))
			{
				$report = file_get_contents($this->filename);
				$report = json_decode($report);
				$this->report = $report;
				$this->loaded = true;	
			}
			else
			{				
				$report = new stdClass;
				$report->last_update = "";
				$report->last_update_ts = 0;
				$report->fail_counter = 0;
				//$report->last_email_sent = "";
				//$report->last_email_sent_ts = 0;
				$report->email = $this->get_email_from_cache($space);
				
				$this->report = $report;
			}
		}
    }
    
	
    /**
     * Reports of how successful the json could be updated.
     * If the report file for $space does not exist a new
     * report file in <docroot>/cron/reports will be created.
     *
     * Every 23 hours a report email is sent to the space and
     * to the BCC if the fail counter is greater zero.
     */
    public function report($success)
    {
		if($success)
			$this->report->fail_counter = 0;
		else
			$this->report->fail_counter++;
		
		// send a report email after one day if an issue hasn't been resolved in the meantime
		// 82800 seconds are 23 hours.
		if(
			( $this->report->fail_counter > 0) &&
			( time() - $this->report->last_update_ts) > 82800
		)
		{
			$this->send_mail();
		}
		
		// today's date and timestamp where the report got updated
		$this->report->last_update = date("Y l jS");
		$this->report->last_update_ts = time();
		
		//echo $this->report->last_update . "\n";
		//echo $this->report->last_update_ts . "\n";
		//echo $this->report->email . "\n";	
		
		$this->write_to_file();
    }
	
	/**
	 * Writes the report to the file.
	 */
	private function write_to_file()
	{
		$json = json_encode($this->report);
		file_put_contents($this->filename, $json);
	}
	
	
	/**
	 * Get the email from the cached JSON.
	 *
	 * @param string $space The space name from the directory.
	 */
	private function get_email_from_cache($space)
	{
		$nice_file_name = NiceFileName::json($space);
		
		echo dirname(__FILE__) . "/cache/" . $nice_file_name;
		$cached = file_get_contents(dirname(__FILE__) . "/cache/" . $nice_file_name);
		$cached = json_decode($cached);
		
		$email = "";
		
		if(property_exists($cached, "contact"))
			if(property_exists($cached->contact, "email" ))
				$email = $cached->contact->email;

		return $email;
	}
	
	
	/**
	 * Sends an email if an error got reported on the next schedule.
	 */
	// to make the mailer more platform-independent, use PHPmailer
	// https://code.google.com/a/apache-extras.org/p/phpmailer/
	public function send_mail()
	{
		global $cache_report_from;
		global $cache_report_bcc;
		global $debug_mode;
		
		// in the debug mode we send ourselves the report emails instead sending
		// them to the space
		if($debug_mode)
			$this->report->email = $cache_report_from;
		
		$fail_counter = $this->report->fail_counter;
		
		// The last argument '-femail@example.com' is used to set the Return-Path header.
		// Setting it as a regular header fails, it's removed from the mail server.
		mail($this->report->email, "Space API Issue", <<<EOF
Hi $this->space,

there's an issue with your Space API. OpenSpaceLint tried to fetch your JSON in the last 24 hours but your server didn't response.

Please have a look at it.

Cheers,
slopjong
EOF
		,
		"FROM: " . $cache_report_from . "\r\n".
		"Reply-To: " . $cache_report_from . "\r\n".
		"Message-ID: <" . time() . "." . $cache_report_from . ">\r\n".
		"X-Mailer: OpenSpaceLint\r\n".
		"BCC: " . $cache_report_bcc,
		"-f". $cache_report_from
		);
		
	}
}