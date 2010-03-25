<?php
include_once 'Framework.php';
include_once 'Logger.php';
include_once 'confusa_config.php';

function confusaErrorHandler($errno, $errstr, $errfile, $errline)
{
	$msg = "";
	$display_errors = (ini_get('display_errors') == true ||
	                   ini_get('display_errors') == "stdout");

	switch($errno) {
	case E_ERROR:
	case E_USER_ERROR:
		$msg = "PHP Fatal Error: $errstr in $errfile on line $errline";

		if ($display_errors) {
			Framework::error_output($msg);
		}
		break;

	case E_WARNING:
	case E_USER_WARNING:
		$msg = "PHP Warning: $errstr in $errfile on line $errline";

		if ($display_errors) {
			Framework::warning_output($msg);
		}
		break;

	case E_NOTICE:
	case E_USER_NOTICE:
		$msg = "PHP Notice: $errstr in $errfile on line $errline";

		if ($display_errors) {
			Framework::message_output($msg);
		}
		break;

	case E_STRICT:
		$msg = "PHP Strict: $errstr in $errfile on line $errline";
		break;

	default:
		$msg = "PHP Unknown: $errstr in $errfile on line $errline";

		if ($display_errors) {
			Framework::message_output($msg);
		}
		break;
	}

	/* if logging is turned on, log the errors to the respective PHP log */
	if (ini_get('log_errors') && (error_reporting() & $errno) ) {
		error_log($msg);
	}

	return true;
}

set_error_handler('confusaErrorHandler');

?>
