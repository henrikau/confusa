<?php
require_once 'framework.php';
require_once 'person.php';

$fw = new Framework('revoke_cert');
$fw->force_login();
$fw->render_page();

function revoke_cert($person)
{
	echo "<H3>Certificate Revokation Area</H3>\n";
}

?>