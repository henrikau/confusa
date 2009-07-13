<?php

$title = "Confusa";
$extra_header = "";

function set_title($new_title)
{
	$title = $new_title;
}

function add_header($header)
{
	global $extra_header;
	$extra_header = $header;
}
function show_headers()
{
	global $title;
	global $extra_header;
	echo "<HTML>\n";
	echo "<HEAD>\n";
	echo "$extra_header\n";
	echo "<TITLE>$title</TITLE>\n";
	echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"confusa.css\">\n";
	echo "<LINK REL=\"shortcut icon\" HREF=\"graphics/icon.gif\" TYPE=\"image/gif\"/>\n";
	echo "</HEAD>\n";
}
?>
