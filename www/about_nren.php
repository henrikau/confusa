<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';

$fw = new Framework('about_nren');
$fw->render_page();

function about_nren($person)
{
	echo "<H3>NREN Area</H3>\n";

	if ($person->is_auth())
		auth_page($person);
	else
		open_page();
}

function auth_page($page)
{
	echo "The classified stuff..<BR />\n";
}

function open_page()
{
	  include('unclassified_intro.php');
}

?>