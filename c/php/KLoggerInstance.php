<?php

require_once(dirname(__FILE__) . '/KLogger.class.php');
require_once(dirname(__FILE__) . '/../../config.php');

$logger = KLogger::instance(dirname(__FILE__) . "/../../log", $debug_level);