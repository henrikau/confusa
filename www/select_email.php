<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'confusa_constants.php';
require_once 'Input.php';

/**
 * Class displaying a selection to the user about which e-mails addresses to
 * include in the certificates' subject alt-name
 * @author tzangerl
 * @since v0.7-rc0
 *
 */
final class CP_Select_Email extends Content_Page
{
	function __construct()
	{
		parent::__construct("Select Email", true, "processcsr");
		Framework::sensitive_action();
	}

	/**
	 * Redirect user immediately to receive_csr step if number e-mail
	 * addresses is zero or both configured and available addresses equal
	 * 1. Otherwise, display mail selection form.
	 * @see Content_Page::pre_process()
	 */
	function pre_process($person)
	{
		parent::pre_process($person);
		$this->tpl->assign('extraScripts', array('js/jquery-1.4.1.min.js'));
		$this->tpl->assign('rawScript', file_get_contents('../include/rawToggleExpand.js'));

		$this->person->clearRegCertEmails();

		$emailsDesiredByNREN = $this->person->getNREN()->getEnableEmail();
		$registeredPersonMails = $this->person->getNumEmails();

		echo "Got the following desired mails: $emailsDesiredByNREN";
		switch($emailsDesiredByNREN) {
		case null:
		case '0':
			header("Location: receive_csr.php");
			exit(0);
			break;
		case '1':
		case 'm':
			if ($registeredPersonMails == 1) {
				$this->person->regCertEmail($this->person->getEmail());
				$this->person->storeRegCertEmails();

				header("Location: receive_csr.php");
				exit(0);
			}
			break;
		}
	}

	function process()
	{
		if (CS::getSessionKey('hasAcceptedAUP') !== true) {
			Framework::error_output($this->translateTag("l10n_err_aupagreement",
				"processcsr"));
			return;
		}

		$user_cert_enabled = $this->person->testEntitlementAttribute(Config::get_config('entitlement_user'));
		$this->tpl->assign('email_status', $this->person->getNREN()->getEnableEmail());
		$this->tpl->assign('user_cert_enabled', $user_cert_enabled);
		$this->tpl->assign('content', $this->tpl->fetch('select_email.tpl'));
	}

}

$fw = new Framework(new CP_Select_Email());
$fw->start();
?>