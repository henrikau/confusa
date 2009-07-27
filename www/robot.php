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


	public function process()
	{
		$this->tpl->assign('content', $this->tpl->fetch('robot.tpl'));
	}
}

$fw = new Framework(new Robot_Interface());
$fw->start();

?>
