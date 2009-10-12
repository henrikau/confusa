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
		parent::pre_process($person);
		/* If the caller is not a nren-admin or Confusa is not in online mode, we stop here */
		if (!$this->person->isNRENAdmin() || Config::get_config('ca_mode') != CA_ONLINE)
			return false;

		if (isset($_POST['account'])) {
			/* We must use POST as we may pass along a password and
			 * we do not want to set that statically in the subject-line. */
			if (isset($_POST['login_name']))
				$login_name = Input::sanitize($_POST['login_name']);
			if (isset($_POST['password']))
				$password = Input::sanitize($_POST['password']);

			switch(htmlentities($_POST['account'])) {
			case 'edit':
				$this->editAccount($login_name, $password);
				break;
			case 'add':
				$ap_name = Input::sanitize($_POST['ap_name']);
				$this->addAccount($login_name, $password, $ap_name);
				break;
			case 'delete':
				$this->deleteAccount($login_name);
				break;
			case 'change':
				$this->changeAccount($login_name);
				break;
			case 'change_ap_name':
				$ap_name = Input::sanitize($_POST['ap_name']);
				$this->changeAPName($ap_name);
				break;
			}
		}
	}

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

		$this->tpl->assign('account_list',		$this->getAccountInfo());
		$this->tpl->assign_by_ref('accountant',	$this);
		$this->tpl->assign('content',			$this->tpl->fetch('accountant.tpl'));
	} /* end process */


	/* addAccount - add a new account for the NRENs to use.
	 *
	 * @login_name : the new login-name. This must be a unidque name (given
	 *		 by Comodo)
	 * @password   : a strong password, and must be the same as set in the
	 *		 remote CA.
	 * @ap_name    : The "alliance partner" name used to identify a reseller
	 */
	private function addAccount($login_name, $password, $ap_name)
	{
		try {
			$enckey	= Config::get_config('capi_enc_pw');
			$pw	= base64_encode($password);
			$size	= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
			$iv	= mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
			$cryptpw= base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,
								$enckey,$pw,
								MCRYPT_MODE_CFB,
								$iv));
			MDB2Wrapper::update("INSERT INTO account_map (login_name, password, ivector, ap_name) " .
						"VALUES(?, ?, ?, ?)",
						array('text','text','text', 'text'),
						array($login_name, $cryptpw, base64_encode($iv), $ap_name));

		} catch (DBQueryException $dbqe) {
			Framework::error_output("Error adding new account." . $dbqe->getMessage());
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Error adding new account $login_name. " .
						"Server said: " . $dbse->getMessage());
			return;
		}

		$accounts = $this->getAccountInfo();
		if (is_null($accounts['account'])) {
			$this->changeAccount($login_name);
		}

		Framework::message_output("Added new account $login_name to NREN " . $this->person->getNREN());
		Logger::log_event(LOG_INFO, "Added new account $login_name to NREN " . $this->person->getNREN());
		return;
	}

	/**
	 * deleteAccount - remove an account from account_map
	 *
	 * Note:
	 *	at the moment, this function only supports the deletion of
	 *	unused accounts. If *any* NREN uses this account, it cannot be
	 *	deleted. Furthermore, it does not distinguish between accounts
	 *	belonging to other NRENS, it will happily delete any unused
	 *	account regardless of who created/owns it.
	 *
	 * @login_name: the name of the account (the actual login-name used at
	 *		the Comodo interface).
	 */
	private function deleteAccount($login_name)
	{
		/* FIXME:
		 *
		 * Handle scenario when more than one NREN is still using the
		 * account.
		 */
		/* Temporary solution: only allow the deletion of an unused account */
		$query = "SELECT * FROM nren_account_map_view WHERE account_login_name=?";
		try {
			$res = MDB2Wrapper::execute($query, array('text'), array($login_name));
			if (count($res) > 0) {
				Framework::error_output("Cannot delete an account that's still being used.");
				return;
			}
			$update = "DELETE FROM account_map WHERE login_name=?";
			MDB2Wrapper::update($update, array('text'), array($login_name));
			Framework::message_output("Deleted account $login_name from account_map.");
			Logger::log_event(LOG_INFO, "Deleted account $login_name from account_map. " .
						"Admin contacted us from " . $_SERVER['REMOTE_ADDR']);
		} catch (DBStatementException $dbse) {
			$msg = __FILE__ . ":" . __LINE__ . " Error in db-statement. Check syntax.";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::error_message($msg . "<BR />Server said: " . $dbse->getMessage());
		} catch (DBQueryException $dbqe) {
			$msg = __FILE__ . ":" . __LINE__ . " Error in query. Check values, possible constrain-violation.";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::error_message($msg . "<BR />Server said: " . $dbse->getMessage());
		}
	} /* end deleteAccount */

	/**
	 * getAccountInfo - return an array with info for *this* nren-account
	 *
	 * This function will find all the accounts, the account for the current
	 * NREN and the AP-name for the current NREN and return an array in the
	 * following format:
	 *
	 * 		- account: the current account of the NREN
	 * 		- AP-name: the AP-name of the current account
	 * 		- all: all Comodo accounts that could be found in the DB
	 */
	private function getAccountInfo()
	{

		/*
		 * Get the current account
		 */
		$query	= "SELECT * FROM nren_account_map_view WHERE nren = ?";
		try {
			$res	= MDB2Wrapper::execute($query, array('text'), array($this->person->getNREN()));
		} catch (DBStatementException $dbse) {
			$msg = __FILE__ . ":" . __LINE__ . " Error in query-syntax.";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::error_output($msg . "<BR />Server said: " . $dbse->getMessage());
			return null;
		} catch (DBQueryException $dbqe) {
			$msg = __FILE__ . ":" . __LINE__ . " Error in query values, possible constraint violation";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::error_output($msg . "<BR />Server said: " . $dbse->getMessage());
			return null;
		}

		if (count($res) == 1) {
			$curr_account = $res[0]['account_login_name'];
			$ap_name = $res[0]['ap_name'];
		} else if (count($res) > 1) {
			$msg  = "Inconsistency in the database,  more than one account tied to a single NREN. ";
			$msg .= "Got " . count($res) . " results back from the database";
			Logger::log_event(LOG_ALERT, $msg);
			Framework::error_message($msg);
		}

		/*
		 * Get all available accounts
		 */
		$accounts	= array();
		$return_res	= null;
		$query		= "SELECT login_name FROM account_map";
		try {
			$res = MDB2Wrapper::execute($query, null, null);

			if (count($res) > 0) {
				foreach($res as $row) {
					$accounts[] = $row['login_name'];
				}
				$return_res = array('account' => $curr_account, 'ap_name' =>  $ap_name, 'all' => $accounts);
			} else {
				Framework::error_output("No account-maps set for Confusa!");
				return null;
			}

		} catch (DBStatementException $dbse) {
			$msg = __FILE__ . ":" . __LINE__ . " Error in query-syntax.";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::error_output($msg . "<BR />Server said: " . $dbse->getMessage());
			return null;
		} catch (DBQueryException $dbqe) {
			$msg = __FILE__ . ":" . __LINE__ . " Error with values, possible constraints viloation.";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::error_output($msg . "<BR />Server said: " . $dbse->getMessage());
			return null;
		}

		return $return_res;
	} /* end getAccountInfo() */

	/**
	 * editAccount - change the state of an account.
	 *
	 * The account is what ties the NREN to the CA-account. It consists of a
	 * username and a password (which is encrypted in the databaes). These
	 * credentials are then used when signing or revoking certificates.
	 *
	 * At the moment, an NREN-admin can only change the currently selected
	 * account (to avoid accidental change of another NREN's account).
	 *
	 * @login_name	: the account-name
	 * @password	: ..
	 */
	private function editAccount($login_name, $password)
	{
		/* FIXME */
		$nren = $this->person->getNREN();

		if (!isset($login_name) || $login_name === "") {
			Framework::error_output("Login-name not set. This <b>must</b> be available when one wants to edit it.");
			Logger::log_event(LOG_INFO, "Tried to edit account with login name not set!");
			return;
		}

		$subselect = "(SELECT account_map_id FROM account_map WHERE login_name=?)";
		/* Is the account the native account for the NREN? */
		try {
			$res = MDB2Wrapper::execute("SELECT * FROM nrens WHERE name=? and login_account = $subselect",
						    array('text', 'text'),
						    array($nren, $login_name));
			if (count($res) == 0) {
				Framework::error_output("Can only change the active account for NREN " . $nren . " and login_name " . $login_name);
				return;
			}
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Error in query at " . __FILE__ . ":" . __LINE__ . ". This should be handled by some developer.");
			Logger::log_event(LOG_ERROR, "Account $login_name could not be edited. Server said: " . $dbqe->getMessage());
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Error in statement at " . __FILE__ . ":" . __LINE__ . ". This should be handled by some developer.");
			Logger::log_event(LOG_ERROR, "Account $login_name could not be edited. Server said: " . $dbse->getMessage());
			return;
		}

		/* The account is 'valid', we can change the password */
		$enckey	= Config::get_config('capi_enc_pw');
		$pw	= base64_encode($password);
		$size	= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
		$iv	= mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
		$cryptpw= base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,
							$enckey,$pw,
							MCRYPT_MODE_CFB,
							$iv));
		try {
			MDB2Wrapper::update("UPDATE account_map SET password=?, ivector=? WHERE login_name=?",
					    array('text','text','text', 'text'),
					    array($cryptpw, base64_encode($iv), $login_name));
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Could not update table. Some error in the constraints? " . $dbqe->getMessage());
			Logger::log_event(LOG_NOTICE, "Could not update account $login_name because of the following error: " . $dbqe->getMessage());
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Could not update table. Some error in the syntax? " . $dbse->getMessage());
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not update account $login_name because of the following error: " . $dbse->getMessage());
			return;
		}
		Framework::message_output("Password for account '$login_name' updated successfully");
		Logger::log_event(LOG_INFO, "Password for account $login_name was changed.");
		return;
	}

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
			Framework::message_output("Changed account for $nren to $login_name");
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

	/*
	 * Change the "Alliance Partner Name" to the supplied value
	 *
	 * @param $ap_name The new alliance partner name
	 */
	private function changeAPName($ap_name)
	{
	    $nren = $this->person->getNREN();

	    /* TODO: maybe that subselect can be optimized */
	    $update = "UPDATE account_map SET ap_name=? WHERE account_map_id=";
	    $update .= "(SELECT login_account from nrens WHERE name=?)";

	    try {
		    MDB2Wrapper::update($update,
					array('text','text'),
					array($ap_name, $nren));
	    } catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not change ap_name to $ap_name for " .
					"NREN $nren. Problem with data: " . $dbqe->getMessage());
			Framework::error_output("Could not change ap_name to $ap_name! Maybe a problem " .
						"with the supplied data? Server said: " . $dbqe->getMessage());
			return;
	    } catch (DBStatementException $dbse) {
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not change ap_name to $ap_name for " .
					"NREN $nren. Problem with data: " . $dbse->getMessage());
			Framework::error_output("Could not change ap_name to $ap_name! Maybe a configuration " .
						"problem! Server said: " . $dbse->getMessage());
			return;
	    }

	    Framework::success_output("Successfully changed AP-name to $ap_name");
	}


	public function createSelectBox($active, $list = null, $name)
	{
		$arg_list = $list;
		if (!isset($list))
			$arg_list = $this->org_states;

		return Output::create_select_box($active, $arg_list, $name);
	} /* end createSelectBox */
}

$fw = new Framework(new CP_Accountant());
$fw->start();
?>
