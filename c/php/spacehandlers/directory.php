<?php

error_reporting(0);

// TODO: this script should synchronize with gmc's directory later

header('Content-type: application/json');

require_once("../slogix-parser.php");

$directory_json = file_get_contents('directory.json');
$directory_array = json_decode($directory_json, true);

if(isset($_GET["space"]))
{
				$spaces = stripslashes(strip_tags($_GET["space"]));
				$spaces = explode(",", $spaces);
				sort($spaces);
				
				$arr = array();
				foreach($spaces as $space)
				{
								$arr[$space] = $directory_array[$space];
				}
				echo json_encode($arr);
				exit(0);
}

if(isset($_GET["filter"]))
{
				$array_keys_json = file_get_contents("../cache/array_keys.json");
				$array_keys_arr = json_decode($array_keys_json, true);
								
				$filters = stripslashes(strip_tags($_GET["filter"]));				
				
				if($slogix = decode_slogix($filters))
								$filters = $slogix;
				else			
								if($json = json_decode($filters, true))
												$filters = $json;
				
				if(gettype($filters) === "string")
								$filters = array("or" => array($filters));
								
				
				// input is a boolean expression as an abstract syntax tree
				// and the sets whose keys which are used in the expression
				$spaces = slogix_evaluate($filters, $array_keys_arr[1]);				
				sort($spaces);
				
				$arr = array();
				foreach($spaces as $space)
				{
								$arr[$space] = $directory_array[$space];
				}
				
				echo json_encode($arr);
				exit(0);
}

// echo the full directory
echo $directory_json;

?>