<?php

/*****************************
 * reCaptcha
 *****************************/

// if you change the keys, don't forget to change the public key
// in c/js/add-hackerspace.js in the code block:
//
//    Recaptcha.create(
//        "6Lcsss8SAAAAAD4080SvtHcts5CBHyMhLU9q3eGS",
//        "recaptcha_div",
//        {
//            lang: "en",
//            theme: "clean",
//            callback: Recaptcha.focus_response_field
//        });
    
$recaptcha_key = array(
				"public" => "6Lcsss8SAAAAAD4080SvtHcts5CBHyMhLU9q3eGS",
				"private" => "6Lcsss8SAAAAAJ7Izqs_sQIgj91UyuY1AMsRisWy"				
				);

/*****************************
 * Second-stage proxy
 *****************************/
$apikey = "09a8fsdfy89a7sdf8usad9f76asd89f7as9d8fgs09fa7";
$second_stage_proxy = "http://jasonproxy.herokuapp.com/?api=". $apikey ."&url=";

/*****************************
 * Site information
 *****************************/

// it's highly recommended to set an URL explicitly because php code run
// from cli doesn't know the SERVER_NAME index.
// Use an URL of the form openspace.slopjong.de and leave the protocol away.
$site_url = $_SERVER["SERVER_NAME"];

/**
 * Cache report emails
 */
// The from (to) and bcc emails where a copy of a cache report is sent from/to.
// These should be OpenSpaceLint admins.
$cache_report_from = "from@your-space.com";
$cache_report_bcc = "bcc@your-space.com";

// the flag that puts OpenSpaceLint into the debug mode
$debug_mode = false;

// what should the default cron schedule be if a space doesn't define one?
$default_cron_schedule = "d.01";

// user agent used when fetching the space json from the server. let it
// believe we are a browser
$curl_user_agent = "Mozilla/5.0 (X11; Linux x86_64; rv:18.0) Gecko/20100101 Firefox/18.0";

?>
