<?php
function error_output($msg) {
	echo "<FONT COLOR=\"RED\"><B>\n";
	echo $msg . "<BR>\n";
	echo "</B></FONT>\n";
  }

function db_array_debug($array, $msg=null)
{
	if (Config::get_config('debug') && count($array) > 1) {
		if (isset($msg))
			echo $msg . "<BR>\n";
		echo "<PRE>\n";
		print_r($array);
		echo "</PRE>\n";
	}
}
?>