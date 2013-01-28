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

# it's highly recommended to set an URL explicitly because php code run
# from cli doesn't know the SERVER_NAME index.
# Use an URL of the form openspace.slopjong.de and leave the protocol away.
$site_url = $_SERVER["SERVER_NAME"];

?>
