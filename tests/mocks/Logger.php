<?php

class Logger 
{
	static private $entries = array();

	static function log_event($level, $msg)
	{
		Logger::$entries[] = array($level, $msg);
	}


	static function assert_logline($line)
	{
		/* loop through Logger::$entries  */
		return false;
	}

	static function dump_loglines()
	{
		echo "<pre>\n";
		print_r(Logger::$entries);
		echo "</pre>\n";
	}
}
?>