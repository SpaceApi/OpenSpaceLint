<?php
    
    $logger = null;
    
    spl_autoload_register("class_loader");
    Config::load();
    $logger = KLogger::instance(LOGDIR, DEBUG_LEVEL);
    
    FilterKeys::update();
    
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
    
?>