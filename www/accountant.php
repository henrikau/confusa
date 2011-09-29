<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'MDB2Wrapper.php';
require_once 'NRENAccount.php';
require_once 'db_query.php';
require_once 'Logger.php';
require_once 'Output.php';
require_once 'Input.php';
/**
 * Accountant - Graphical class for managing the account information for
 * hooking up with the remote CA (e.g. Comodo).
 */
class CP_Accountant extends Content_Page
{
	private $account;
	function __construct()
	{
		parent::__construct("Admin", true, "accountant");
	}

	public function pre_process($person)
	{
		$res = true;

		/*  we cannot call parent::pre_process here because CA
		 *  will bomb if the account_map is not properly set. */
		/* parent::pre_process($person); */
		$this->setPerson($person);
		$this->account = NRENAccount::get($this->person->getNREN());

		/*
		 * are we going to update the account-map?
		 */
		/* If the caller is not a nren-admin or Confusa is not in online mode, we stop here */
		if (!$this->person->isNRENAdmin() || Config::get_config('ca_mode') != CA_COMODO) {
			return false;
		}

		$login_name = false;
		$password   = false;
		$ap_name    = false;
		if (isset($_POST['account']) && $_POST['account'] === 'edit') {
			/* We must use POST as we may pass along a password and
			 * we do not want to set that statically in the subject-line. */
			if (isset($_POST['login_name'])) {
				$ln = $_POST['login_name'];
				$login_name = Input::sanitizeText(htmlspecialchars($ln));
				if ($ln === $login_name) {
					$this->account->setLoginName($login_name);
					$res = false;
				} else {
					Framework::error_output("The new login_name contains illegal characters, will not update!");
				}
			}

			if (isset($_POST['password']) && $_POST['password']!=="") {
				$pw = $_POST['password'];
				$password = Input::sanitizeText(htmlspecialchars($pw));
				if ($pw === $password) {
					$this->account->setPassword($password);
				} else {
					Framework::error_output("The new password contains illegal characters, ($pw - $password)");
					$res = false;
				}
			}

			if (isset($_POST['ap_name'])) {
				$ap = $_POST['ap_name'];
				$ap_name = Input::sanitizeText(htmlspecialchars($ap));
				if ($ap === $ap_name) {
					$this->account->setAPName($ap_name);
				} else {
					Framework::error_output("Cleaned ap-name and it contains illegal characters!");
					$res = false;
				}
			}
			/* should we validate? */
			try {
				if ($this->account->save()) {
					Framework::success_output("Changes successfully saved!");
				}
			} catch (ConfusaGenException $cge) {
				/* FIXME: l10n */
				Framework::error_output("Could not update account-data, username/password did not validate with backend CA");
			}
		}
		parent::pre_process($person);
		return $res;
	} /* end pre_process */

	public function process()
	{
		if (!$this->person->isNRENAdmin()) {

			$errorTag = PW::create();
			Logger::logEvent(LOG_NOTICE, "Accountant", "process()",
			                 "User " . stripslashes($this->person->getX509ValidCN()) . " tried to access the accountant.",
			                __LINE__, $errorTag);
			$this->tpl->assign('reason', "[$errorTag] You are not an NREN-admin");
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;

		} else if (Config::get_config('ca_mode') != CA_COMODO) {

			$errorTag = PW::create();
			Logger::logEvent(LOG_NOTICE, "Accountant", "process()",
			                "User " . stripslashes($this->person->getX509ValidCN()) . "tried to access the accountant, " .
			                "even though Confusa is not using the Comodo CA.",
			                __LINE__, $errorTag);
			$this->tpl->assign('reason', "[$errorTag] Confusa is not using Comodo CA");
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;

		}

		/* set fields in template */
		if (!$this->account->getLoginName()) {
			$this->tpl->assign('login_name',
			                   $this->translateTag('l10n_fieldval_undefined', 'accountant'));
		} else {
			$this->tpl->assign('login_name', $this->account->getLoginName());
		}

		if (!$this->account->getPassword()) {
			$this->tpl->assign('password',
			                   $this->translateTag('l10n_fieldval_undefined', 'accountant'));
		} else {
			$this->tpl->assign('password',
							   $this->translateTag('l10n_label_passwhidden', 'accountant'));

		}

		if (!$this->account->getAPName()) {
			$this->tpl->assign('ap_name',
			                   $this->translateTag('l10n_fieldval_undefined', 'accountant'));
		} else {
			$this->tpl->assign('ap_name', $this->account->getAPName());
		}

		$this->tpl->assign('content',			$this->tpl->fetch('accountant.tpl'));
	} /* end process */
}

$fw = new Framework(new CP_Accountant());
$fw->start();
?>
