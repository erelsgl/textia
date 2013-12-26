<?php

/**
 * @file pack.php - utilities for packing objects for storing in database.
 * @see pack_test.php - a unit-test for this file.
 *
 * @author Erel Segal
 * @date 2007-11-11
 */

/**
 * A parameter for gzcompress.
 * @note 4-9 takes about 2 times longer than 1-3, and produces about 2 times shorter strings. See demo/pack_benchmark.php
 */
$GLOBALS['GZIP_COMPRESSION_LEVEL'] = 1;

/**
 * Don't bother to gzip strings with less than this number of bytes.
 * @note Change this number in your program, after including pack.php, NOT here! 
 */
$GLOBALS['MINIMUM_LENGTH_TO_ZIP'] = 10000;

$GLOBALS['PACK_DELIMITER'] = '<p> ';

$GLOBALS['GZIP_IDENTIFIER'] = 'GZ';
$GLOBALS['BASE64_IDENTIFIER'] = 'BS';

$GLOBALS['extension_old_or_new_loaded'] = extension_loaded("meezoog") || extension_loaded("meezoog2"); 
$GLOBALS['extension_new_loaded'] = extension_loaded("meezoog");

/*
 * Pack integers in 6-bit characters
 */
if (!$GLOBALS['extension_old_or_new_loaded']) {
	$GLOBALS['SAFECHARS']= '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz+/'. /* base64 chars */
	'~!@#$%^&*()<>[]-_=|?.,;:'.chr(128).chr(129).chr(130).chr(131).chr(132).chr(133).chr(134).chr(135).chr(136).chr(137).chr(138).chr(139).chr(140); /* more 36 chars added for belief+uncertainty. NOTE: {} are not safe because they are used by serialize; space is not safe because it is used as a delimiter */

	function pack_int1($int) {  // 6 bit
		return $GLOBALS['SAFECHARS'][$int];
	}
	
	function pack_int2($int) {  // 12 bit
		return 
			pack_int1($int&0x03f).
			pack_int1(($int>>6)&0x03f)
			;
	}
	
	function pack_int3($int) {  // 18 bit
		return 
			pack_int1($int&0x03f).
			pack_int1(($int>>6)&0x03f).
			pack_int1(($int>>12)&0x03f)
			;
	}
	
	function pack_int4($int) {  // 24 bit
		return 
			pack_int1($int&0x03f).
			pack_int1(($int>>6)&0x03f).
			pack_int1(($int>>12)&0x03f).
			pack_int1(($int>>18)&0x03f)
			;
	}
	
	function pack_int5($int) {  // 30 bit
		return 
			pack_int1($int&0x03f).
			pack_int1(($int>>6)&0x03f).
			pack_int1(($int>>12)&0x03f).
			pack_int1(($int>>18)&0x03f).
			pack_int1(($int>>24)&0x03f)
			;
	}
	
	function unpack_int1($string,$position=0) { // 6 bit
		if ($position>=strlen($string)) {
			user_error('illegal index on unpack_int1', E_USER_WARNING);
			return 0;
		}
		return strpos($GLOBALS['SAFECHARS'],$string[$position]);
	}
	
	function unpack_int2($string,$position=0) {  // 12 bit
		if ($position+1>=strlen($string)) {
			user_error('illegal index on unpack_int2', E_USER_WARNING);
			return 0;
		}
		return unpack_int1($string,$position) | (unpack_int1($string,$position+1)<<6);
	}
	
	function unpack_int3($string,$position=0) {  // 18 bit
		if ($position+2>=strlen($string)) {
			user_error('illegal index on unpack_int3', E_USER_WARNING);
			return 0;
		}
		return (unpack_int1($string,$position)) |
			(unpack_int1($string,$position+1)<<6) |
			(unpack_int1($string,$position+2)<<12);
	}
	
	function unpack_int4($string,$position=0) {  // 24 bit
		if ($position+3>=strlen($string)) {
			user_error('illegal index on unpack_int4', E_USER_WARNING);
			return 0;
		}
		return (unpack_int1($string,$position)) |
			(unpack_int1($string,$position+1)<<6) |
			(unpack_int1($string,$position+2)<<12) | (unpack_int1($string,$position+3)<<18);
	}
	
	function unpack_int5($string,$position=0) {  // 30 bit
		if ($position+4>=strlen($string)) {
			user_error('illegal index on unpack_int5', E_USER_WARNING);
			return 0;
		}
		return (unpack_int1($string,$position)) |
			(unpack_int1($string,$position+1)<<6) |
			(unpack_int1($string,$position+2)<<12) | (unpack_int1($string,$position+3)<<18) | (unpack_int1($string,$position+4)<<24);
	}






	/**
	 * @param $array - an array of integers
	 * @return STRING with the packed integers.	 
	 */
	function pack_array_of_int1($array) {
		$s = '';
		foreach ($array as $int)
			$s .= pack_int1($int);
		return $s;
	}
	
	/**
	 * @param $string - an string returned from pack_array_of_int1
	 * @return ARRAY with the original integets.	 
	 */
	function unpack_array_of_int1($string) {
		if (!is_string($string)) {
			user_error("unpack_array_of_int1: expected string but got ".print_r($string,TRUE), E_WARNING);
			var_dump($string);
			return array();
		}
		$c = strlen($string);
		$a = array_fill(0,$c,0);
		for ($i=0; $i<$c; ++$i)
			$a[$i] = unpack_int1($string[$i]);
		return $a;
	}

 } // old_or_new_extension
/*
 *                     String conversion
 */
if( !$GLOBALS['extension_new_loaded'] ) { // extension

	function pack_string($string) {
		if (strlen($string)>=$GLOBALS['MINIMUM_LENGTH_TO_ZIP']) {
			//user_error ("len='".strlen($string)."' min='".$GLOBALS['MINIMUM_LENGTH_TO_ZIP']."'");
			return $GLOBALS['BASE64_IDENTIFIER']. base64_encode(gzcompress($string,$GLOBALS['GZIP_COMPRESSION_LEVEL']));
		} else {
			return $string;
		}
	}


	function is_string_packed($string) {
		global $GZIP_IDENTIFIER, $BASE64_IDENTIFIER;
		return preg_match("/^$BASE64_IDENTIFIER/",$string) || preg_match("/^$GZIP_IDENTIFIER/",$string);
	}

	function unpack_string($string) {
		global $GZIP_IDENTIFIER, $BASE64_IDENTIFIER;
		if (preg_match("/^$BASE64_IDENTIFIER/",$string)) {
			$string_decoded = base64_decode(substr($string,2));
			$string_uncompressed = gzuncompress($string_decoded);
			if (!$string_uncompressed) {
				user_error("error in unpack_string($string)",E_USER_WARNING);
				return "";
			} else {
				return $string_uncompressed;
			}
		} elseif (preg_match("/^$GZIP_IDENTIFIER/",$string)) {
			$string = substr($string,2);
			$unpacked = gzuncompress($string);
			if (!$unpacked) var_dump($string);
			return $unpacked;
		} else {
			return $string;
		}
	}

	function serialize_pretty($object) {
		$serialized = serialize($object);
		// Don't do this - object might still be used!
		//$object=NULL; unset($object);  // save some space

		$serialized = str_replace('O:5:"group":1:{s:1:"a";a:','G{', $serialized);
		$serialized = str_replace('O:4:"path":1:{s:1:"a";a:' ,'P{', $serialized);
		$serialized = str_replace('O:4:"edge":1:{s:1:"s";s:' ,'E{', $serialized);

		return $serialized;
	}

	function unserialize_pretty($serialized) {
		$serialized = str_replace('G{','O:5:"group":1:{s:1:"a";a:',
		str_replace('P{', 'O:4:"path":1:{s:1:"a";a:',
		str_replace('E{', 'O:4:"edge":1:{s:1:"s";s:',
		$serialized)));
		$unserialized=@unserialize($serialized);
		if ($unserialized===FALSE) {
			// Overcome a strange bug when restoring from backup
			$serialized = str_replace("\n","\r\n", $serialized);
			$unserialized=unserialize($serialized);
		}
		if ($unserialized===FALSE) {
			var_dump($serialized);
		}
		return $unserialized;
	}

	/**
	* returns the original object that created the string.
	*/
	
	function unpack_object(&$string) {
		global $EDGE_DELIMITER;
		if (!$string)
			return NULL;
		else {
			$string_unpacked = unpack_string($string);
			if (!$string_unpacked) {
				user_error("Error in unpack_object($string)", E_USER_WARNING);
				return NULL;
			} elseif (preg_match("/^[a-z][:{]/i",$string_unpacked) || $string_unpacked==="N;") { // a serialized string
				if($string_unpacked==="N;")
					return NULL;

				$unserialized = unserialize_pretty($string_unpacked);
				if( $unserialized )
					return $unserialized;	// must have been serialized

				//$unserialized = unserialize($string_unpacked);
				//if( $unserialized )
				//	return $unserialized;	// must have been serialized

				return $string_unpacked;	// wasn't a serialized string
			}
			else
				return $string_unpacked;	// wasn't a serialized string
			//return object_from_binary($string);
		}
	}
	

	/**
	* @return a string ready for insertion into a database, without quotes.
	*/
	function pack_lazy_array(&$array) {
		if (!$array)
			return NULL;

		if (is_string($array))
			return $array;  // already packed

		global $PACK_DELIMITER;
		$packed = '';
		$number_of_unpacked_strings = 0;
		foreach ($array as $key=>$value) {
			if (!$value) continue; // don't pack null values (???)
			if (is_string($value)) { // value is probably already packed
				$packed_value = $value;
				if (!is_string_packed($packed_value)) {
					// NOTE: escaping here is more efficient but might insert backslashes to the database - see pairs_in_table_by_node.php
					//$packed_value = mysql_real_escape_string($packed_value);
					$number_of_unpacked_strings++;
				}
			} elseif (!$value) {  // null
				$packed_value = "N";
			} else {
				$serialized_value = serialize_pretty($value);
				$packed_value = pack_string($serialized_value);
				if (!is_string_packed($packed_value)) {
					// NOTE: escaping here is more efficient but might insert backslashes to the database - see pairs_in_table_by_node.php
					//$packed_value = mysql_real_escape_string($packed_value);
					$number_of_unpacked_strings++;
				}
			}
			$packed .= $PACK_DELIMITER;
			$packed .= $key;
			$packed .= $PACK_DELIMITER;
			$packed .= $packed_value;
		}
		/*if ($GLOBALS['DEBUG_QUERY_TIMES']) print("
			<p>number_of_unpacked_strings=$number_of_unpacked_strings</p>
			<p>count=".count($array)."</p>
			<p>before=".strlen($packed)."</p>
			");*/
		if ($number_of_unpacked_strings>=2 || count($array)>=100)
			$packed = pack_string($packed);
		/*if ($GLOBALS['DEBUG_QUERY_TIMES']) print("
			<p>after=".strlen($packed)."</p>
			");*/
		return $packed;
	}


	function unpack_lazy_array(&$string) {
		if (!$string)
			return array();

		global $PACK_DELIMITER;
		$string_unpacked = unpack_string($string);
		if (!$string_unpacked) {
			user_error("error in unpack_string($string)",E_USER_WARNING);
			return array();
		}

		$string = preg_replace("/^$PACK_DELIMITER/","",$string_unpacked);
		$elements = explode($PACK_DELIMITER,$string);
		if (count($elements)==1)
			return $string_unpacked;  // only a string - no array

		$unpacked = array();
		for ($i=0; $i<count($elements); $i+=2) {
			$key = $elements[$i];

			if (!isset($elements[$i+1]))
				var_dump($string);

			$value = $elements[$i+1];
			if ($value==='N')
				$unpacked[$key] = NULL;
			else
				$unpacked[$key] = $value;
		}

		return $unpacked;
	}
}/*////////// END EXTENSION ------------------------/////////// 
//////////// END EXTENSION ------------------------///////////
*/


function assert_numeric($name, $value) {
	if (!is_numeric($value))	
		user_error("$name must be numeric, not '$value'!", E_USER_ERROR);
}

function print_pre($label, $object, $background=NULL) {
	print "\n<pre dir='ltr'".($background? " style='background:$background'": "").">$label:\n";
	print_r($object);
	print "\n</pre>\n";
}

function pack_and_quote_string($string) {
	if (strlen($string)>=$GLOBALS['MINIMUM_LENGTH_TO_ZIP']) {
		//user_error ("len='".strlen($string)."' min='".$GLOBALS['MINIMUM_LENGTH_TO_ZIP']."'");
		return "'".$GLOBALS['BASE64_IDENTIFIER']. base64_encode(gzcompress($string,$GLOBALS['GZIP_COMPRESSION_LEVEL']))."'";
	} else {
		return quote_smart($string);
	}
}

if (!function_exists('pack_object')) {
	/**
	* returns a string ready for insertion into a database, but without quotes.
	*/
	function pack_object(&$object) {
		if (is_string($object))
			return $object;  // already packed
		else
			return pack_string(serialize_pretty($object));
		//return object_to_binary($object);
	}

}

/**
 * returns a string ready for insertion into a database, with quotes.
 */
function pack_and_quote_object(&$object) {
	if (is_string($object))
		return quote_smart($object);  // already packed
	else
		return pack_and_quote_string(serialize_pretty($object));
	//return object_to_binary($object);
}


?>