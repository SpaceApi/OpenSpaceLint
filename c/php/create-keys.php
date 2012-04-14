<?php

//header('Content-type: application/json');

/**
 * Iterate over the passed associative array and find
 * all array key combinations with the nested ones
 * concatenated with a dot.
 *
 * E.g. $arr[a][b] would result in the found keys a and a.b
 *
 * a=contact
 * b=phone
 * -------------
 * nested keys are: contact and contact.phone
 *
 * Numerical indices are skipped and don't appear in any concatenated key.
 * 
 */
function space_array_keys($array, &$keys, $ancestor_key = "")
{			
				foreach($array as $key => $value){
								
								// skip the numerical indices
								if(!is_numeric($key))
												$new_key = $ancestor_key
																				. ((!empty($ancestor_key)) ? "." : "")
																				. $key;
								else
												$new_key = $ancestor_key;
											
								// don't push the key if it is already present
								if(!in_array($new_key, $keys))									
												array_push($keys, $new_key);
												
								if(is_array($value))
												space_array_keys($value, $keys, $new_key);									
				}				
}

/**
 * Create an array with two lists listing what member a space uses.
 * The first array element contains a list sorted to the spaces
 * and the second is sorted to the members.
 */
function list_space_array_keys()
{
				$sorted_to_space = array();
				$sorted_to_member = array();
				
				// create a list of what members a certain space supports
				foreach (glob("cache/*.json") as $filename)
				{
								$json = json_decode(file_get_contents($filename), true);				
								$members = array();
								space_array_keys($json, $members);
								$sorted_to_space[$json["space"]] = $members;
				}
				
				// create a list of what space uses a certain member
				foreach ($sorted_to_space as $space => $members)
				{
								foreach($members as $member)
								{
												$val = $sorted_to_member[$member];
												$sorted_to_member[$member] = $val . ((!empty($val)) ? "," : "" ). $space;
								}
				}
				
				/*
				foreach($sorted_to_space as $space => $keys)
								$sorted_to_member = array_merge($sorted_to_member, $keys);
				
				$sorted_to_member = array_unique($sorted_to_member);
				sort($sorted_to_member);
				*/
				
				return array($sorted_to_space, $sorted_to_member);
}

//echo json_encode(list_space_array_keys());
?>