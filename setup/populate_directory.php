<?php
    
    // in the Route class we check for this variable
    // in order to guarantee that some code is only
    // run during the installation time
    define("RUNNING_SETUP","");
    
    if(!file_exists("directory.json"))
        die("No spaces to populate");
    $directory = json_decode(file_get_contents("directory.json"), true);
    
    if($directory === null)
        die("The directory is no valid json.");
    
    define('SAPI', 'cli');
    
    $logger = null;
    
    spl_autoload_register("class_loader");
    Config::load();
    $logger = KLogger::instance(LOGDIR, DEBUG_LEVEL);
    
    foreach($directory as $space => $url)
    {
        echo "Adding $space\n";
        Route::execute("directory", "add", "$url");
    }
    
    print_crontab_notice();
    
    
    /**************************************************************************
     ***  HELPER FUNCTIONS
     **************************************************************************/
    
    function class_loader($classname)
    {
        $classfile = dirname(__FILE__) . "/../c/php/classes/$classname.class.php";
        
        if (file_exists($classfile))
        {
            require_once($classfile);
            return true;
        }
        
        echo("The class '$classname' cannot be loaded!");
        return false;
    }
    
    function print_crontab_notice()
    {
        $crondir = CRONDIR;
        $crontab = <<<EOF
*/2   *     *    *    *    run-parts ${crondir}scron.m.02
*/5   *     *    *    *    run-parts ${crondir}scron.m.05
*/10  *     *    *    *    run-parts ${crondir}scron.m.10
*/15  *     *    *    *    run-parts ${crondir}scron.m.15
*/30  *     *    *    *    run-parts ${crondir}scron.m.30
*     */1   *    *    *    run-parts ${crondir}scron.h.01
*     */2   *    *    *    run-parts ${crondir}scron.h.02
*     */4   *    *    *    run-parts ${crondir}scron.h.04
*     */8   *    *    *    run-parts ${crondir}scron.h.08
*     */12  *    *    *    run-parts ${crondir}scron.h.12
40    4     */1  *    *    run-parts ${crondir}scron.d.01
EOF;
    
        echo "\nAdd this to the crontab of the user under which the web server is running: \n";
        echo "# ------------------------------------------------\n";
        echo $crontab;
        echo "\n# ------------------------------------------------\n";
        echo "Note: these are the crons shipped with OpenSpaceLint. If you added schedules yourself add them too.";
        echo "\n\n";
    }
?>