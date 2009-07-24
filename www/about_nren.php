<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';

class About_NREN extends FW_Content_Page
{
	function __construct()
	{
		parent::__construct("About NREN", false);
	}


	public function process()
	{
		echo "<H3>NREN Area</H3>\n";

		if ($this->person->is_auth()) {
			$this->auth_page();
		} else {
			$this->open_page();
		}
	}

	private function auth_page()
	{
		echo "The classified stuff..<BR />\n";
	}

	private function open_page()
	{
		include 'unclassified_intro.php';
	}
}	

$fw = new Framework(new About_NREN());
$fw->start();

?>
