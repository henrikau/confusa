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

	public function pre_process()
	{

		$sspdir = Config::get_config('simplesaml_path');
		require_once $sspdir . '/lib/_autoload.php';
		SimpleSAML_Configuration::setConfigDir($sspdir . '/config');

		if (is_null($this->person)) {
			$this->person = new Person();
		}

		$auth = AuthHandler::getAuthManager($this->person);

		$session = SimpleSAML_Session::getInstance();
		$authority = $session->getAuthority();

		if (empty($authority)) {
			$authority = ConfusaConstants::$DEFAULT_SESSION_AUTHORITY;
		}

		if ($session->isValid($authority)) {
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
