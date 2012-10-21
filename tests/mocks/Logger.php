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
		foreach(Logger::$entries as $e) {
			if ($line === $e[1]) return true;
		}
		return false;
	}

	static function dump_loglines()
	{
		echo "<pre>\n";
		foreach (Logger::$entries as $e) {
			echo $e[0] . " : " . $e[1] . "\n";
		}
		echo "</pre>\n";
	}

	static function empty_loglines()
	{
		Logger::$entries = array();
	}
}
?>