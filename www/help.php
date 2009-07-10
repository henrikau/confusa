<?php
require_once 'framework.php';
require_once 'person.php';

$fw = new Framework('help');
$fw->render_page();

function help($person)
{
	echo "<H3>Help</H3>\n";
}

function open_help()
{
	include 'ipso_lorem.html';
}

function auth_page($person)
{
	if (!$person->is_auth()) {
		open_help();
		return;
	}
	echo "<H3>Classified help</H3>\n";
}
?>