<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';

class Help extends FW_Content_Page
{
	function __construct()
	{
		parent::__construct("Help", false);
	}

	public function pre_process($person)
	{
		$this->setPerson($person);
		$this->setManager();
		return false;
	}

	public function process($person)
	{
		if (!$person->is_auth()) {
			echo "<H3>Help</H3>\n";
			include 'ipso_lorem.html';
			return;
		}
		echo "<H3>Classified help</H3>\n";
		echo "Nothing here yet...<BR />\n";
	}

	public function post_process($person)
	{
		/* cleanups etc? */
	}
}

$fw = new Framework(new Help());
$fw->start();

?>

