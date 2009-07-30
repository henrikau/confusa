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

	public function process()
	{
		
		$this->tpl->assign('help_file', file_get_contents('../include/ipso_lorem.html'));
		$this->tpl->assign('content', $this->tpl->fetch('help.tpl'));

	}
}

$fw = new Framework(new Help());
$fw->start();

?>

