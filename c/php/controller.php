<?php

init_class_loader();

Config::load();

error_reporting( (DEBUG_MODE) ? E_ALL : 0 );
$logger = KLogger::instance(LOGDIR, DEBUG_LEVEL);

prepare_route();
process_route();

/**
 * Initializes the auto class loader. This function needs
 * the environment configured before the first use.
 */
function init_class_loader()
{   
    // define the auto class loader
    function class_loader($classname)
    {
        $classfile = dirname(__FILE__) . "/classes/$classname.class.php";
        
        if (file_exists($classfile))
        {
            require_once($classfile);
            return true;
        }
        
        // this is not so ideal, when the config cannot be loaded this fails
        // so just be sure the Config class is always included!
        $logger = KLogger::instance(LOGDIR, DEBUG_LEVEL);
        $logger->logEmerg("The class '$classname' cannot be loaded!");
        
        return false;
    }
    
    spl_autoload_register("class_loader");
}

/**
 * Process the route by checking the delegator and action with whitelists
 * and defining the constants ROUTE_DELEGATOR, ROUTE_ACTION and ROUTE_RESOURCE.
 * Additionally the constant SAPI is defined.
 */
function prepare_route()
{    
    $logger = KLogger::instance(LOGDIR, DEBUG_LEVEL);
    
    // don't remove the empty delegator and action
    // not every delegator supports all the actions
    $allowed_delegators = array(
        'jsconfig',
        'cache',
        'cron',
        'directory',
        'environment',
        'filterkeys',
        'proxy',
        'status',
        'specs',
        'apienv',
        'validator',
        ''
    );
    $allowed_actions = array('get', 'update', 'add', '');
 
    $delegator = "";
    $action = "";
    $resource = "";
    $sapi = "";
            
    switch(PHP_SAPI)
    {
        case "apache":
        case "apache2filter":
        case "apache2handler":
            
            if( isset($_GET["delegator"]) )
               $delegator = $_GET["delegator"];
           
            if(isset($_GET["action"]) )
                $action = $_GET["action"];
            
            if(isset($_GET["resource"]))
               $resource = $_GET["resource"];
            
            $sapi = "apache";
            
            break;
        
        case "cli":
        case "cgi":
        case "cgi-fcgi":
            
            global $argc;
            global $argv;
            
            // process all the cli arguments
            for($i=1; $i<$argc; $i++)
            {
                $arg = $argv[$i];
                
                // this is just a basic test for the correctness of the argument
                if( strpos($arg, "=") !== false )
                {
                    
                    list($arg_name, $arg_value) = explode("=", $arg);
                   
                    switch($arg_name)
                    {
                        case "delegator":
                            
                            if( in_array($arg_value, $allowed_delegators) )
                                $delegator = $arg_value;
                            else
                            {
                                $logger->logDebug("Unsupported delegator: $arg_value");
                                exit();
                            }
                                
                            break;
                        
                        case "action":
                            
                            if( in_array($arg_value, $allowed_actions) )
                                $action = $arg_value;
                            else
                            {
                                $logger->logDebug("Unsupported action: $arg_value");
                                exit();
                            }   
                            break;
                        
                        case "resource":
                            
                            $resource = $arg_value; 
                            break;
                    }
                }
                else
                {
                    $logger->logEmerg("The route was defined incorrectly when called from the cli!");
                    exit();
                }
            }
            
            $sapi = "cli";
            
            break;
        
        default:
            
            $logger->logEmerg("The route could not be determined by analyzing '". PHP_SAPI ."' !");
            exit();        
    }

    define('ROUTE_DELEGATOR', $delegator);
    define('ROUTE_ACTION', $action);
    define('ROUTE_RESOURCE', $resource);
    
    // contains either the value cli or apache
    define('SAPI', $sapi);
    
    $unsupported = false;
    
    unset($_GET["delegator"]);
    unset($_GET["action"]);
    unset($_GET["resource"]);

    // whitelist the delegator
    if( ! in_array(ROUTE_DELEGATOR, $allowed_delegators) )
    {
        $logger->logDebug("Unsupported delegator: ". ROUTE_DELEGATOR);
        $unsupported = true;
    }

    // whitelist the action
    if( ! in_array(ROUTE_ACTION, $allowed_actions) )
    {
        $logger->logDebug("Unsupported action: ". ROUTE_ACTION);
        $unsupported = true;
    }
    
    // leave the script if something is unsupported, empty values are considered supported
    // basically we whitelist the values and take no action on bad values made by the user
    if($unsupported)
        exit();
}


/**
 * Calls the static execute method of the Route class.
 */
function process_route()
{
    Route::execute(ROUTE_DELEGATOR, ROUTE_ACTION, ROUTE_RESOURCE);
}