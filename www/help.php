<?php
require_once 'framework.php';
require_once 'person.php';

$fw = new Framework('help');
$fw->render_page();

function help($person)
{
	echo "<H3>Help</H3>\n";
}

?>