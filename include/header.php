<?php

$title = "Confusa";
$extra_header = "";

function set_title($new_title)
{
	$title = $new_title;
}

function add_header($extra_header)
{

}
function show_headers()
{
	echo "<HTML>\n";
	echo "<HEAD>\n";
	echo "$extra_header\n";
	echo "<TITLE>$title</TITLE>\n";
	echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"confusa.css\">\n";
	echo "<LINK REL=\"shortcut icon\" HREF=\"graphics/icon.gif\" TYPE=\"image/gif\"/>\n";
	echo "</HEAD>\n";
}
?>
