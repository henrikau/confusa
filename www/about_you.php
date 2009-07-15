<?php
require_once 'confusa_include.php';
require_once 'framework.php';

class AboutYou extends ContentPage
{
	function __construct()
	{
		parent::__construct("About You", true);
		$this->signing_ok = false;
	}

	public function pre_process($person)
	{
		$this->setPerson($person);
		$this->setManager();
		return false;
	}
	public function process($person)
	{
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
	public function post_render($person)
	{
		/* cleanups etc? */
	}
}

$fw = new Framework(new AboutYou());
$fw->start();

?>
