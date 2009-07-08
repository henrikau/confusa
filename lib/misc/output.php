<?php
function error_output($msg) {
	echo "<FONT COLOR=\"RED\"><B>\n";
	echo $msg . "<BR>\n";
	echo "</B></FONT>\n";
  }

function db_array_debug($array)
{
	if (Config::get_config('debug') && count($array) > 1) {
		echo "<PRE>\n";
		print_r($array);
		echo "</PRE>\n";
	}
}
?>