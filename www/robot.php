<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';

class Robot_Interface extends FW_Content_Page
{
	function __construct()
	{
		parent::__construct("Robot", true);
	}

	public function pre_process($person)
	{
		$this->setPerson($person);
		$this->setManager();
		return false;
	}

	public function process($person)
	{
		echo "<H3>Robot Interface</H3>\n";
		echo "This is where you administer the robotic interface for your institution<BR />\n";
	}

	public function post_process($person)
	{
		return ;
	}
}

$fw = new Framework(new Robot_Interface());
$fw->start();

?>
