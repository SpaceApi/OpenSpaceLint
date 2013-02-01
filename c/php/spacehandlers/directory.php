<?php

error_reporting(0);

// TODO: this script should synchronize with gmc's directory later

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

require_once("../slogix-parser.php");

function output_json($data)
{
				if(isset($_GET['fmt']) && $_GET['fmt']=='a')
				{
								$data = json_decode($data);
								
								Class SpaceDirEntry
								{
												public $name;
												public $url;
												public function __construct($name,$url)
												{
																$this->name=$name;
																$this->url=$url;
												}
								}
								
								$objdir=array();
								foreach($data as $name=>$url) {
												$objdir[]=new SpaceDirEntry($name,$url);
								}
								
								$data = json_encode((object) array('spaces'=>$objdir));
				}
				
				echo $data;
				exit(0);			
}

$directory_json = file_get_contents('directory.json.public');
$directory_array = json_decode($directory_json, true);

// TODO: document, make it public to the world
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
				
				output_json(json_encode($arr));
}

if(isset($_GET["filter"]))
{
				$array_keys_json = file_get_contents("../cache/array_keys.json");
				$array_keys_arr = json_decode($array_keys_json, true);
								
				$filters = stripslashes(strip_tags($_GET["filter"]));				
				
				// a slogix expression or a json can be used to define the filters
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
				
				output_json(json_encode($arr));
}

// output the full directory
output_json($directory_json);
//echo $directory_json;

?>
