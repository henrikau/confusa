<?php
require_once 'framework.php';
require_once 'person.php';

$fw = new Framework('download_cert');
$fw->force_login();
$fw->render_page();

function download_cert($person)
{
	echo "<H3>Certificate Download Area</H3>\n";
}

?>