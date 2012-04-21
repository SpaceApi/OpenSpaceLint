<?php

error_reporting(0);

// TODO: this script should synchronize with gmc's directory later

header('Content-type: application/json');

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
				$filters = stripslashes(strip_tags($_GET["filter"]));				
				$filters = explode(",", $filters);
				
				$spaces = array();
				foreach($filters as $filter)
				{
								$array_keys_json = file_get_contents("../cache/array_keys.json");
								$array_keys_arr = json_decode($array_keys_json, true);
								
								if(count($spaces)==0)
												$spaces = $array_keys_arr[1][$filter];
								else
												$spaces = array_intersect($spaces, $array_keys_arr[1][$filter]);
				}
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