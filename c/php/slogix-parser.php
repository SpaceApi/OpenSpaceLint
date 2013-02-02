<?php

$config = realpath(dirname(__FILE__) . "/../../config.php");
require_once($config);
error_reporting( ($debug_mode) ? E_ALL : 0 );

function decode_slogix($slogix, &$i=0, &$ast = array())
{								
				// remove all the whitespaces
				$slogix = preg_replace("/\s*/", "", $slogix);
				
				// if the amount of opening and closing braces isn't balanced
				// the slogix is invalid
				if(! is_balanced($slogix))
								return null;
				
				// there mustn't be whitespaces in the pattern because
				// they let the preg_match fail
				$pattern = '~(\w+(\.\w+)?|[(),])~';
				preg_match_all($pattern, $slogix, $matches);
				
				// the first element in $matches[1] must me a boolean operator
				if( ! in_array($matches[1][0], array("or", "and") ) )
							return null;
				
				$n = count($matches[1]);

				$operator = $matches[1][0];
				switch($operator)
				{
								case "and":
								case "or":
												
												// position of the opening brace
												$pos_brace_open = 1;
												$pos_brace_close = $n-1;
												
												if($matches[1][$pos_brace_open] !== "(" && $matches[1][$pos_brace_close] !== ")")
																return null;
												
												$slice = array_slice($matches[1], $pos_brace_open+1, $pos_brace_close-$pos_brace_open-1);
												$operands = get_operands($slice);
												$ast[$operator] = $operands;
												
												break;
																								
								default:
												return null;
				}
				
				return $ast;
}

function get_operands($input)
{
				$result = array();
				$keywords = array("or", "and", "(", ")", ",");

				for($index=0; $index<count($input); $index++)
				{
								$value = $input[$index];
								
								if(!in_array($value, $keywords))
								{
												array_push($result, $value);
												// skip the next symbol which is a brace or a comma
												$index++;
								}
								else
								{																
												// just look if $value is a logical operator
												if(in_array($value, array("and","or")))
												{																				
																// a sub expression might be empty and thus the shortest length
																// of a sub expression is 3 elements, 1 for the op and 2 for the braces
																$sub_exp_len = 3;
																
																while( ($sub_exp_len + $index - 1) < count($input) )
																{																								
																				$sub_slogix = array_slice($input, $index, $sub_exp_len);
																				$sub_slogix = join("", $sub_slogix);
																				$balanced = is_balanced($sub_slogix);
																				
																				if($balanced)
																				{
																								array_push($result, decode_slogix($sub_slogix));
																								break;
																				}
																				else
																								$sub_exp_len++;
																}
																
																$index = $index + $sub_exp_len;
												}
								}
				}
				return $result;
}

function is_balanced($slogix)
{								
				preg_match_all("~\(~", $slogix, $matches);
				$amount_opening_braces = count($matches[0]);
				
				preg_match_all("~\)~", $slogix, $matches);
				$amount_closing_braces = count($matches[0]);
				
				return ( 0 == ($amount_closing_braces - $amount_opening_braces));
}

function slogix_evaluate($slogix, $sets)
{
				list($operator, $operands) = each($slogix);
				
				$spaces = array();
				foreach($operands as $operand)
				{								
								if(count($spaces)==0)
								{
												if(gettype($operand) === "string")
																$set = $sets[$operand];
												else
																$set = slogix_evaluate($operand, $sets);
																
												$spaces = $set;
								}
								else
								{
												if(gettype($operand) === "string")
																$set = $sets[$operand];
												else
																$set = slogix_evaluate($operand, $sets);				
												
												switch($operator)
												{
																case "or":
																				
																				$spaces = array_merge($spaces, $set);
																				break;
																
																case "and":
																				
																				$spaces = array_intersect($spaces, $set);
																				break;
												}
								}
				}			
				
				//print_r($operator);
				//print_r($operands);
				
				//print_r($spaces);
				//exit;
				
				return $spaces;
}

/*
function decode_expression(&$i, $slogix)
{
				$result = "";
				
				$n = strlen($slogix);
				
				while( $i < $n && $slogix[$i] !== '(' && $slogix[$i] !== ',' )
				{
								$result = $result . $slogix[$i];
								$i++;
				}
				
				return $result;
}
*/

