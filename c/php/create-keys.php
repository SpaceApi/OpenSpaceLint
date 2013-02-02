<?php

require_once('utils.php');

if(isset($_GET["debug"]))
{
				header('Content-type: application/json');
				echo json_encode(list_space_array_keys());
}