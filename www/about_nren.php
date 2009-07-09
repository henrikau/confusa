<?php
require_once 'framework.php';
require_once 'person.php';

$fw = new Framework('about_nren');
$fw->render_page();

function about_nren($person)
{
	echo "<H3>NREN Area</H3>\n";
}

?>