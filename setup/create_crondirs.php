<?php

    require_once( dirname(__FILE__) ."/../config/config.php");
    
    $schedules = json_decode(CRON_AVAILABLE_SCHEDULES);
    
    foreach($schedules as $index => $schedule)
        mkdir(dirname(__FILE__) . "/../cron/scron." . $schedule);
?>