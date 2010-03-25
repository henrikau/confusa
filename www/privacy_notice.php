<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'Person.php';
require_once 'Logger.php';

class CP_Priv_Notice extends Content_Page
{
	function __construct()
	{
		parent::__construct("Help", false, "index");
	}

	public function process()
	{
		$nren = $this->person->getNREN();

		if (isset($nren)) {
			$this->tpl->assign('nren', $this->person->getNREN());
			$privacyNotice = $this->person->getNREN()->getPrivacyNotice($this->person);
			$this->tpl->assign('nren_pt_text', $privacyNotice);
		}

		$this->tpl->assign('content', $this->tpl->fetch('privacy_notice.tpl'));
	}
}

$fw = new Framework(new CP_Priv_Notice());
$fw->start();

?>
