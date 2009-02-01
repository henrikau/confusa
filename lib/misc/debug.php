<?php
require_once('config.php');
require_once('logger.php');
class Debug {
	static function dump($var) {
		if (Config::get_config('debug')) {
			echo "$var<BR>\n";
		}
	}
}
?>