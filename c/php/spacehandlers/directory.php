<?php

// this script should synchronize with gmc's directory later

header('Content-type: application/json');

$directory = file_get_contents('directory.json');
echo $directory;

?>