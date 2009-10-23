<?php
require_once 'confusa_include.php';
include_once 'framework.php';
require_once 'confusa_config.php';

class Logout extends Content_Page
{
	public function __construct()
	{
		parent::__construct("Logout", false);
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		if (!is_null($person)) {
			AuthHandler::getAuthManager($this->person)->deAuthenticateUser(basename($_SERVER['SCRIPT_NAME']));
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
