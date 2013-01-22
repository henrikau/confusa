<?php
require_once 'confusa_include.php';
require_once 'confusa_constants.php';
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

	public function pre_process($person)
	{
		parent::pre_process($person);
		$this->tpl->assign('extraScripts', array('js/jquery-1.6.1.min.js'));
		$this->tpl->assign('rawScript', file_get_contents('../include/rawToggleExpand.js'));
	}
	public function process()
	{
		$nren = $this->person->getNREN();
		if (isset($nren) && $this->person->isAuth()) {
			$helpText = $nren->getHelpText($this->person);

			if (isset($helpText)) {
				$this->tpl->assign('nren_help_text', $helpText);
			} else {
				$this->tpl->assign('nren_contact_email', $nren->getContactEmail(true));
			}
			if (Config::get_config('cert_product') == PRD_ESCIENCE) {
				$this->tpl->assign('portal_escience', true);
			}
			$this->tpl->assign('nren', $nren->getName());
			$this->tpl->assign('content', $this->tpl->fetch('help.tpl'));
		} else {
			$this->tpl->assign('content', $this->tpl->fetch('help.tpl'));
			return;
		}
	}
} /* end CP_Help */

$fw = new Framework(new CP_Help());
$fw->start();

?>
