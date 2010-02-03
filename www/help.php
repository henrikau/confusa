<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'classTextile.php';
require_once 'logger.php';

class CP_Help extends Content_Page
{
	function __construct()
	{
		parent::__construct("Help", false, "index");
	}

	public function process()
	{
		$nren = $this->person->getNREN();
		$help_text = $this->person->getNREN()->getHelpText($this->person);
		$this->tpl->assign('nren', $nren);
		$this->tpl->assign('nren_help_text', $help_text);
		$this->tpl->assign('help_file', file_get_contents('../include/help.html'));
		$this->tpl->assign('content', $this->tpl->fetch('help.tpl'));
	}
} /* end CP_Help */

$fw = new Framework(new CP_Help());
$fw->start();

?>
