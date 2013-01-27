<?php

    $cron_template = file_get_contents("../cron/cron_template");
    $directory = json_decode(file_get_contents("../c/php/spacehandlers/directory.json"));
    
    foreach($directory as $space => $url)
    {
        $template = str_replace("SSS", $space, $cron_template);
        
        $file_name = preg_replace("/[^a-zA-Z0-9]/i", "_", $space);
        $file_name = strtolower($file_name);
                
        $cron = "../cron/cron.d.01/" . $file_name;
        file_put_contents($cron, $template);
        chmod($cron, 0755);
    }

?>