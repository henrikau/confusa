<?php
function set_value($name, $target, $msg, $method = "POST")
{
	echo "<FORM ACTION=\"$target\" METHOD=\"$method\">\n";
	echo "<INPUT NAME=\"$name\" TYPE=\"text\" />\n";
	echo "<INPUT TYPE=\"submit\" VALUE=\"$msg\" />\n";
	echo "</FORM>\n";
}
?>