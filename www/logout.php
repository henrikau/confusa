<?php
require_once 'confusa_include.php';
include_once 'framework.php';
class Logout extends FW_Content_Page
{
	public function __construct()
	{
		parent::__construct("Logout", false);
	}

	public function pre_process()
	{
		if (is_null($this->person)) {
			$this->person = new Person();
		}

		$auth = AuthHandler::getAuthManager($this->person);
		if ($auth->checkAuthentication()) {
			$auth->deAuthenticateUser('logout.php');
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
