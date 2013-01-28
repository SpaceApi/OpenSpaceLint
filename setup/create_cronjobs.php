<?php

    require_once("../c/php/utils.php");

    $cron_template = file_get_contents("../cron/cron_template");
    $directory = json_decode(file_get_contents("../c/php/spacehandlers/directory.json"));
    
    foreach($directory as $space => $url)
        create_new_cron($space);

?>