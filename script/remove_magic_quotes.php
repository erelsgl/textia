<?php

/**
 * @link http://il.php.net/manual/en/security.magicquotes.php#61188
 */
if (get_magic_quotes_gpc()) {

	function stripslashes_array($data) {
		if (is_array($data)){
			foreach ($data as $key => $value){
				$data[$key] = stripslashes_array($value);
			}
			return $data;
		}else{
			return stripslashes($data);
		}
	}

	$_SERVER = stripslashes_array($_SERVER);
	$_GET = stripslashes_array($_GET);
	$_POST = stripslashes_array($_POST);
	$_COOKIE = stripslashes_array($_COOKIE);
	$_FILES = stripslashes_array($_FILES);
	$_ENV = stripslashes_array($_ENV);
	$_REQUEST = stripslashes_array($_REQUEST);
}

?>