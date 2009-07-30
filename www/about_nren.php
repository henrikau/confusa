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
		$this->tpl->assign('content', $this->tpl->fetch('about_nren.tpl'));
	}

}	

$fw = new Framework(new About_NREN());
$fw->start();

?>
