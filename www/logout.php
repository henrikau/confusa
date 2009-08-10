<?php
require_once 'confusa_include.php';
include_once 'framework.php';
class Logout extends FW_Content_Page
{
	public function __construct()
	{
		parent::__construct("Logout", false);
	}

	public function process()
	{
		if (isset($_GET['edu_name'])) {
			require_once 'confusa_auth.php';
			deauthenticate_user($this->person);
		}

		$this->tpl->assign('person', $this->person);
		$this->tpl->assign('content', $this->tpl->fetch('logout.tpl'));
	}
}

$fw = new Framework(new Logout());
$fw->start();

?>
