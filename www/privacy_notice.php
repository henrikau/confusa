<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'classTextile.php';
require_once 'logger.php';

class CP_Priv_Notice extends Content_Page
{
	function __construct()
	{
		parent::__construct("Help", false, "index");
	}

	public function process()
	{
		$this->tpl->assign('nren', $this->person->getNREN());
		$this->tpl->assign('nren_pt_text',
				   $this->person->getNREN()->getPrivacyNotice($this->person));
		$this->tpl->assign('content', $this->tpl->fetch('privacy_notice.tpl'));
	}
}

$fw = new Framework(new CP_Priv_Notice());
$fw->start();

?>
