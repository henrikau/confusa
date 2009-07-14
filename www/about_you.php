<?php
require_once('confusa_include.php');
include_once('framework.php');

$fw = new Framework('about_you');
$fw->force_login();
$fw->render_page();

function about_you($person) {
	$textual = "no";

	if (isset($_GET['text'])) {
		$textual = htmlentities($_GET['text']);
	}

	if ($textual == "yes") {
		/* The id makes it very easy to automatically retrieve the content, e.g. with XPath */
		echo "<DIV ID=\"dn-section\">";
		echo $person->get_complete_dn();
		echo "</DIV>";
	} else {
		echo "<H3>This is what we know about you:</H3>\n";
		echo $person;
		echo "<HR>\n";
		echo "we store very little information. What we do keep, is information about certificates issued, combinded with the eduPersonPrincipalName\n";
		echo "This is the DN in the certificate, and whe <b>have</b> to store this.<BR>\n";
	}
	
}
?>
