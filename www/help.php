<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'Person.php';
require_once 'classTextile.php';
require_once 'Logger.php';

class CP_Help extends Content_Page
{
	function __construct()
	{
		parent::__construct("Help", false, "index");
	}

	public function process()
	{
		$nren = $this->person->getNREN();

		if (isset($nren)) {
			$nren = $this->person->getNREN();
			$helpText = $nren->getHelpText($this->person);
		} else {
			$this->tpl->assign('help_file', file_get_contents('../include/help.html'));
			$this->tpl->assign('content', $this->tpl->fetch('help.tpl'));
			return;
		}

		$this->tpl->assign('nren_help_text', $helpText);
		$this->tpl->assign('nren', $nren->getName());
		$this->tpl->assign('help_file', file_get_contents('../include/help.html'));
		$this->tpl->assign('content', $this->tpl->fetch('help.tpl'));
	}
} /* end CP_Help */

$fw = new Framework(new CP_Help());
$fw->start();

?>
