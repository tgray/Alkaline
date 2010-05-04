<?php

// Trim (that is, remove whitespace) from values of an array
function trimValue(&$value){ $value = trim($value); }

// Convert a possible string or integer into an array
function convertToArray(&$input){
	if(is_string($input)){
		$find = strpos($input, ',');
		if($find === false){
			$input = array($input);
		}
		else{
			$input = explode(',', $input);
			array_walk($input, 'trimValue');
		}
	}
}

// Convert a possible string or integer into an array of integers
function convertToIntegerArray(&$input){
	if(is_int($input)){
		$input = array($input);
	}
	elseif(is_string($input)){
		$find = strpos($input, ',');
		if($find === false){
			$input = array(intval($input));
		}
		else{
			$input = explode(',', $input);
			array_walk($input, 'trimValue');
		}
	}
}

?>