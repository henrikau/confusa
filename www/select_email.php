<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'confusa_constants.php';
require_once 'Input.php';

final class CP_Select_Email extends Content_Page
{
	function __construct()
	{
		parent::__construct("Select Email", true, "processcsr");
		Framework::sensitive_action();
	}

	function pre_process($person)
	{
		parent::pre_process($person);
		$this->tpl->assign('extraScripts', array('js/jquery-1.4.1.min.js'));
		$this->tpl->assign('rawScript', file_get_contents('../include/rawToggleExpand.js'));

		/* FIXME: more security checks */
		if (CS::getSessionKey('hasAcceptedAUP') !== true) {
			return;
		}
	}

	function process()
	{
		$user_cert_enabled = $this->person->testEntitlementAttribute(Config::get_config('entitlement_user'));
		$this->tpl->assign('email_status', $this->person->getNREN()->getEnableEmail());
		$this->tpl->assign('user_cert_enabled', $user_cert_enabled);
		$this->tpl->assign('content', $this->tpl->fetch('csr/email.tpl'));
	}

}

$fw = new Framework(new CP_Select_Email());
$fw->start();
?>