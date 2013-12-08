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

define('RECAPTCHA_KEY_PUBLIC', "6Lcsss8SAAAAAD4080SvtHcts5CBHyMhLU9q3eGS");
define('RECAPTCHA_KEY_PRIVATE', "6Lcsss8SAAAAAJ7Izqs_sQIgj91UyuY1AMsRisWy");

/*****************************
 * Second-stage proxy
 *****************************/
define('APIKEY', "09a8fsdfy89a7sdf8usad9f76asd89f7as9d8fgs09fa7");
define('SECOND_STAGE_PROXY_URL', "http://jasonproxy.herokuapp.com/?api=". APIKEY ."&url=");

/*****************************
 * Site information
 *****************************/

// it's highly recommended to set an URL explicitly because php code run
// from cli doesn't know the SERVER_NAME index.
// Use an URL of the form openspace.slopjong.de and leave the protocol away.
define('SITE_URL', $_SERVER["SERVER_NAME"]);

/**
 * Cache report emails
 */
// The from email which is used as the sender for the cache report emails.
// Ideally people should be able to reply to this address
define('CACHE_REPORT_FROM', "from@your-space.com");

// A array of emails where blind copies are sent to, these should
// be OpenSpaceLint operators (admins), his list must be a valid json!
// When you wish to receive no emails, just leave the brackets empty '[]'.
define('CACHE_REPORT_BCC', '["bcc1@your-space.com", "bcc2@your-space.com"]');

// A flag which disables sending an email to a space.
define('CACHE_REPORT_SENDEXTERNAL', true);

// the flag that puts OpenSpaceLint into the debug mode
define('DEBUG_MODE', false);

/*
  the debugging message level, one of these values:
  
    EMERG  = 0;  // Emergency: system is unusable
    ALERT  = 1;  // Alert: action must be taken immediately
    CRIT   = 2;  // Critical: critical conditions
    ERR    = 3;  // Error: error conditions
    WARN   = 4;  // Warning: warning conditions
    NOTICE = 5;  // Notice: normal but significant condition
    INFO   = 6;  // Informational: informational messages
    DEBUG  = 7;  // Debug: debug messages
*/
define('DEBUG_LEVEL', 7);

// what schedules should this installation support?
define('CRON_AVAILABLE_SCHEDULES', '["m.02","m.05","m.10","m.15","m.30","h.01","h.02","h.04","h.08","h.12","d.01"]');
// what should the default cron schedule be if a space doesn't define one?
define('CRON_DEFAULT_SCHEDULE', "d.01");

// user agent used when fetching the space json from the server. let it
// believe we are a browser
define('CURL_USER_AGENT', "Mozilla/5.0 (X11; Linux x86_64; rv:18.0) Gecko/20100101 Firefox/18.0");

// After how many seconds should the curl timeout appear?
define('CURL_TIMEOUT', "15");

// which is the specs draft version?
define('SPECSDRAFTVERSION', '13');

/**************************************************************/
// just don't change this
define('ROOTDIR', realpath(dirname(__FILE__)."/..")."/");
define('CACHEDIR', ROOTDIR . "cache/");
define('CACHEREPORTSDIR', CACHEDIR . "reports/");
define('CLASSDIR', ROOTDIR . "c/php/classes/"); // if you change this, change the path in the controller too
define('CONFIGDIR', ROOTDIR . "config/");
define('CONFIGFILE', __FILE__); // if you change this, change the path in the Config class too
define('CRONDIR', ROOTDIR . "cron/");
define('DIRECTORYDIR', ROOTDIR . "c/directory/");
define('LOGDIR', ROOTDIR . "log/");
define('PLUGINDIR', ROOTDIR ."c/php/validation-plugins/");
define('STATUSCACHEDIR', CACHEDIR . "status/");
define('SPECSDIR', ROOTDIR . "c/specs/versions/");

// define where our apps live 
define('_APPSDIR', "apps/");
define('APPSDIR', ROOTDIR . _APPSDIR);