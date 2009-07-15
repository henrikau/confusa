<?php
require_once 'confusa_include.php';
require_once 'framework.php';

class AboutYou extends FW_Content_Page
{
	function __construct()
	{
		parent::__construct("About You", true);
	}

	public function process()
	{
		$textual = "no";
		if (isset($_GET['text'])) {
			$textual = htmlentities($_GET['text']);
		}
		if ($textual == "yes") {
			/* The id makes it very easy to automatically retrieve the content, e.g. with XPath */
			echo "<DIV ID=\"dn-section\">";
			echo $this->person->get_complete_dn();
			echo "</DIV>";
		} else {
			echo "<H3>This is what we know about you:</H3>\n";
			echo $this->person;
			echo "<HR>\n";
			echo "we store very little information. What we do keep, is information about certificates issued, combinded with the eduPersonPrincipalName\n";
			echo "This is the DN in the certificate, and whe <b>have</b> to store this.<BR>\n";
		}
	}
}

$fw = new Framework(new AboutYou());
$fw->start();

?>
