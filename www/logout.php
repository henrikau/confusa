<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'Confusa_Auth.php';

class Logout extends Content_Page
{
	public function __construct()
	{
		parent::__construct("Logout", false, "index");
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		if (!is_null($person)) {
			AuthHandler::getAuthManager($this->person)->deAuthenticate(basename($_SERVER['SCRIPT_NAME']));
		}
	}

	public function process()
	{
		$this->tpl->assign('person', $this->person);
		$this->tpl->assign('content', $this->tpl->fetch('logout.tpl'));
	}
}

$fw = new Framework(new Logout());
$fw->start();

?>
