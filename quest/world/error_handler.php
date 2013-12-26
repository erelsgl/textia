<?php

/**
 * @file error_handler.php - define a custom error handler for Textia - email the error to the developer
 * @author Erel Segal http://tora.us.fm
 * @date 2010-07-30
 * @copyright GPL 
 */


function printable_backtrace($back_trace) {
	$result = '';
	array_shift($back_trace); // remove "report_error_to_developers";
	array_shift($back_trace); // remove "error_handler";
	foreach ($back_trace as $level=>$data) {
		@$result .= "#$level  $data[function](".implode(",",$data['args']).") called at [$data[file]:$data[line]]\n";
	}
	return $result;
	//return print_r($back_trace,TRUE); // too long - includes all global variables
}

$GLOBALS['NUMBER_OF_BUG_REPORTS_PER_SESSION'] = 1;
$GLOBALS['EMAIL_ADDRESS_FOR_BUG_REPORTS'] = 'erelsgl@gmail.com';
/**
 * Report error on both the log file and an email to the developers.
 *  
 * @param string $message
 * @param int $errno e.g. E_USER_WARNING or E_STRICT 
 */ 
function report_error_to_developers($message, $errno) {
	if ($GLOBALS['NUMBER_OF_BUG_REPORTS_PER_SESSION']>0 && !$GLOBALS['DEBUG_QUERY_TIMES']) { // if $DEBUG_QUERY_TIMES, there may be errors of text before session

		$back_trace = printable_backtrace(debug_backtrace());

		if (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI'])) {
			$user_url = "http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
		} else {
			$user_url = "(unknown URL)";
		}
		$server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : __FILE__;
		$user_agent  = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'none';
		mail(
			$GLOBALS['EMAIL_ADDRESS_FOR_BUG_REPORTS'],
			"Bug in textia",
			"User URL: $user_url\n\n$message\nUser Agent: $user_agent\n\nBacktrace:\n$back_trace");
		--$GLOBALS['NUMBER_OF_BUG_REPORTS_PER_SESSION'];
	}
}

function error_handler($errno, $errstr, $errfile, $errline) {
	if(error_reporting() == 0) { // dont treat the silenced errors! (Gdata)
	    return;
	}
	if ($errno == 2048 && empty($GLOBALS['REPORT_STRICT'])) {
		return;
	}
	$fatal = $errno&(E_ERROR|E_PARSE|E_USER_ERROR);
	if (!empty($GLOBALS['IGNORE_NON_FATAL']) && !$fatal) {
		return;
	}
	$message = ($fatal? "Fatal " : "") . "Error type $errno: $errstr on $errfile:$errline";
	
	if ($GLOBALS['DEBUG_QUERY_TIMES']) {
		print "\n\n<br /><b>Error type $errno</b>: $errstr  in <b>$errfile</b> on line <b>$errline</b><br />\n\n";
		echo '<pre>';
		debug_print_backtrace();
		echo '</pre>';
	}

	report_error_to_developers($message, $errno);

	if ($GLOBALS['error_handler_before_log_system']) {
		$GLOBALS['error_handler_before_log_system']($errno, $errstr, $errfile, $errline);
	}
	if ($fatal) {
		die;
	}
}

//user_error("Test",E_USER_WARNING);

?>