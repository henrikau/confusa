<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'db_query.php';
require_once 'logger.php';
require_once 'output.php';
require_once 'input.php';

/**
 * Accountant - Graphical class for managing the account information for
 * hooking up with the remote CA (e.g. Comodo).
 */
class CP_Accountant extends Content_Page
{
	function __construct()
	{
		parent::__construct("Admin", true);
	}

	public function pre_process($person)
	{
		$res = false;

		/*  we cannot call parent::pre_process here because CertManager
		 *  will bomb if the account_map is not properly set. */
		/* parent::pre_process($person); */
		$this->setPerson($person);

		/*
		 * are we going to update the account-map?
		 */
		/* If the caller is not a nren-admin or Confusa is not in online mode, we stop here */
		if (!$this->person->isNRENAdmin() || Config::get_config('ca_mode') != CA_ONLINE) {
			return false;
		}

		if (isset($_POST['account'])) {
			/* We must use POST as we may pass along a password and
			 * we do not want to set that statically in the subject-line. */
			if (isset($_POST['login_name']))
				$login_name = Input::sanitize($_POST['login_name']);
			if (isset($_POST['password']))
				$password = Input::sanitize($_POST['password']);
			if (isset($_POST['ap_name']))
				$ap_name = Input::sanitize($_POST['ap_name']);

			switch(htmlentities($_POST['account'])) {
			case 'edit':
				$res = $this->editNRENAccount($login_name, $password, $ap_name);
				break;
			case 'new':
				$res = $this->addNRENAccount($login_name, $password, $ap_name);
				break;
			default:
				Framework::error_output("Unknow accountant-operation (" .
							$htmlentities($_POST['account']) .
							"). Stopping.");
				$res = false;
			}
		}
		parent::pre_process($person);
		return $res;
	} /* end pre_process */

	public function process()
	{
		if (!$this->person->isNRENAdmin()) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->getX509ValidCN() . " tried to access the accountant.");
			$this->tpl->assign('reason', 'You are not an NREN-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		} else if (Config::get_config('ca_mode') != CA_ONLINE) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->getX509ValidCN() . "tried to access the accountant, " .
					  "even though Confusa is not running in Online mode.");
			$this->tpl->assign('reason', 'Confusa is not in online mode');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}
		$res = $this->getNRENAccounts($this->person->getNREN());

		if (isset($res[0]['login_name'])) {
			$this->tpl->assign('login_name', $res[0]['login_name']);
		} else {
			$this->tpl->assign('login_name', 'undefined');
		}

		$this->tpl->assign('password_label', '<i>hidden</i>');

		if (isset($res[0]['ap_name'])) {
			$this->tpl->assign('ap_name', $res[0]['ap_name']);
		} else {
			$this->tpl->assign('ap_name', 'undefined');
		}

		$this->tpl->assign('content',			$this->tpl->fetch('accountant.tpl'));
	} /* end process */

	/**
	 * getNRENAccounts() Get all CA-accounts for the current NREN
	 *
	 * Get the currently existing account for NREN. Read login name and ap_name
	 * and IF there is a password, but do not decrypt it.
	 *
	 * @param $nren string the NREN for which to retrieve the account information
	 * @return array consisting of
	 * 		ap_name		String	AP-name
	 * 		login_name	String	the login name
	 * 		password	String	the ENCRYPTED password
	 * 		account_map_id	Int	the ID of the account in the DB
	 * 		nren_id		Int	the ID of the NREN in the DB
	 */
	private function getNRENAccounts($nren)
	{
		$query  = "SELECT ap_name, login_name, password, account_map_id, n.nren_id ";
		$query .= "FROM account_map a LEFT JOIN nrens n ";
		$query .= "ON a.account_map_id = n.login_account WHERE n.name = ?";

		try {
			$res = MDB2Wrapper::execute($query,
						    array('text'),
						    array($nren));

			if (count($res) > 0) {
				return $res;
			} else {
				return null;
			}
		} catch (DBQueryException $dqe) {
			Logger::log_event(LOG_INFO, "[nadm] (query) Could not determine the current " .
					  "ap_name and login_name for NREN $nren: " . $dqe->getMessage());
		} catch (DBStatementException $dse) {
			Logger::log_event(LOG_INFO, "[nadm] (statement) Could not determine the current " .
					  "ap_name and login_name for NREN $nren: " . $dse->getMessage());
		}
	} /* end getNRENAccounts() */

	/**
	 * editNRENAccount() Change elements in an existing account
	 *
	 * @param String login_name	The new login-name. This must be a
	 *				unique name (given by Comodo)
	 * @param String password	A strong password, and must be the same
	 *				as set in the remote CA.
	 *
	 * @param String ap_name	The "alliance partner" name used to
	 *				identify a reseller.
	 *
	 * @return Boolean indicating if the change was successful.				
	 */
	private function editNRENAccount($login_name, $password, $ap_name)
	{
		/*
		 * get NREN and currently configured account
		 */
		$accountInfo = $this->getNRENAccounts($this->person->getNREN());
		if (is_null($accountInfo)) {
			Framework::error_output("Cannot get NREN-account info. Aborting.");
			return false;
		}
		$nren_id	= $accountInfo[0]['nren_id'];
		$account_id	= $accountInfo[0]['login_account'];

		if (empty($nren_id) ||empty($account_id)) {
			Framework::error_output("Vital info unavailable. Cannot update Account");
			return false;
		}

		/*
		 * prepare the db-entries
		 */
		$enckey	= Config::get_config('capi_enc_pw');
		$pw	= base64_encode($password);
		$size	= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
		$iv	= mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
		$cryptpw= base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,
						       $enckey,$pw,
						       MCRYPT_MODE_CFB,
						       $iv));

		/*
		 * new or changing existing account
		 */
		try {
			$account_info = MDB2Wrapper::execute("SELECT * FROM account_map WHERE account_map_id = ?",
							     array('text'),
							     array($account_id));
			/* are we updating existing value, or adding new? */
			if (count($account_info) != 1) {
				$changed = false;
				/* are we changing anything? */
				if (($account_info[0]['login_name'] != $login_name) ||
				    ($account_info[0]['password'] != $cryptpw)) {
					echo "updating existing account<br />\n";
					return $this->updateNRENAccount($login_name, $password, $ap_name, $account_id, $nren_id);
				} else {
					echo "nothing changed. stopping<br />\n";
					return false;
				}
			}
			else {
				/* FIXME */
				return false;
			}
		} catch (DBStatementException $dbse) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . $dbse->getMessage());
			return false;
		} catch (DBQueryException $dbqe) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . $dbqe->getMessage());
			return false;
		}
	} /* end editNRENAccount() */

	/**
	 * deleteAccount() Delete a CA-account for the current NREN
	 *
	 * NOTE: This function is currently not in use, but it is planned to be
	 *	 used once we start listing all available accounts for the
	 *	 NREN. Thus, it does not make sense to remove it.
	 *
	 * This will delete an account for the current admin's NREN.
	 *
	 * @param String account_map_id the id of the account to delete.
	 * @return boolean Indicating if the account was successfully deleted.
	 *
	 */
	private function deleteAccount($account_map_id)
	{
		$query  = "DELETE FROM account_map WHERE account_map_id ? AND nren_id = ";
		$query .= "(SELECT nren_id FROM nrens WHERE name = ?)";

		try {
			MDB2Wrapper::update($query,
					    array('text', 'text'),
					    array($account_map_id, $this->person->getNREN()));
		} catch (DBQueryException $dbe) {
			Framework::error_message("Problem deleting your old account: " . $dbe->getMessage() .
						 ". Seems like a problem with the supplied data!");
			Logger::log_event(LOG_WARN, "[nadm] Could not delete old login account of " .
					  "NREN $nren " . $dbe->getMessage());
			return false;
		} catch (DBStatementException $dse) {
			Framework::error_message("Problem deleting your old account: " . $dbe->getMessage() .
						 ". Seems like a problem with the configuration. Please contact an administrator.");
			Logger::log_event(LOG_WARN, "[nadm] Could not delete old login account of " .
					  "NREN $nren " . $dbe->getMessage());
			return false;
		}
		return true;
	}

	/**
	 * Change the account of the NREN of the logged on person to login_name
	 *
	 * @param $login_name String the new login-name for the NREN of the logged
	 * 	in user
	 * @return void
	 */
	private function changeAccount($login_name)
	{
		$nren = $this->person->getNREN();

		/* Get the current account */
		try {
			$res = MDB2Wrapper::execute("SELECT account_login_name FROM nren_account_map_view WHERE nren = ?",
						    array('text'),
						    array($nren));
			if (count($res) > 1) {
				Framework::error_output("Too many hits in database! " . count($res) . " Database inconsistency.");
				Logger::log_event(LOG_WARNING, "Inconsistency detected in the database. $org has " . count($res) . " accounts");
				return;
			}

			if (count($res) == 1) {
				if ($res[0]['account_login_name'] === $login_name) {
					return;
				}
			}

			$subselect="(SELECT account_map_id FROM account_map WHERE login_name=?)";

			MDB2Wrapper::update("UPDATE nrens SET login_account=$subselect WHERE name=?",
					    array('text', 'text'),
					    array($login_name, $nren));
			Framework::message_output("Changed account for NREN $nren to $login_name");
			Logger::log_event(LOG_INFO, "Changed account for $nren to $login_name. " .
					  "Admin contacted us from " . $_SERVER['REMOTE_ADDR']);
		} catch (DBStatementException $dbqe) {
			Framework::error_output("Query syntax errors. Server said: " . $dbqe->getMessage());
			Logger::log_event(LOG_INFO, "Syntax error when trying to change the used account of NREN " .
					  $this->person->getNREN() . ": " . $dbqe->getMessage());
			return;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Database-server problems. Server said: " . $dbqe->getMessage());
			Logger::log_event(LOG_NOTICE, "Database problems when trying to change the used account of NREN " .
					  $this->person->getNREN() . ": " . $dbqe->getMessage());
			return;
		}
	} /* end changeAccount() */

	private function addNRENAccount($loginName, $password, $apName)
	{
		if (empty($loginName) || empty($password) || empty($apName)) {
			Framework::error_output("Cannot add new account when fields are missing!");
			return false;
		}

		/*
		 * prepare the db-entries
		 */
		$enckey	= Config::get_config('capi_enc_pw');
		$pw	= base64_encode($password);
		$size	= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
		$iv	= mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
		$cryptpw= base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,
						       $enckey,$pw,
						       MCRYPT_MODE_CFB,
						       $iv));

		/*
		 * Get NREN-id
		 */
		try {
			$res = MDB2Wrapper::execute("SELECT * FROM nrens WHERE name = ?",
						    array('text'),
						    array($this->person->getNREN()));
			$nrenID = $res[0]['nren_id'];
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Error adding new account, does the account exist?<br />".
						$dbqe->getMessage());
			return false;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Error adding new account $login_name. " .
						"Server said: " . $dbse->getMessage());
			return false;
		}

		/*
		 * Add the new account
		 */
		try {
			MDB2Wrapper::update("INSERT INTO account_map (login_name, password, ivector, ap_name, nren_id) " .
					    "VALUES(?, ?, ?, ?, ?)",
					    array('text','text','text', 'text', 'text'),
					    array($loginName, $cryptpw, base64_encode($iv), $apName, $nrenID));

			Framework::message_output("Added new account $loginName to NREN " . $this->person->getNREN());
			Logger::log_event(LOG_INFO, "Added new account $loginName to NREN " . $this->person->getNREN());
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Error adding new account, does the account exist?<br />".
						$dbqe->getMessage());
			return false;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Error adding new account $login_name. " .
						"Server said: " . $dbse->getMessage());
			return false;
		}

		/*
		 * Hook the account into nren
		 */
		$this->changeAccount($loginName);

		return true;
	} /* end addNRENAccount() */
}

$fw = new Framework(new CP_Accountant());
$fw->start();
?>
