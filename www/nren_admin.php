<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'db_query.php';
require_once 'logger.php';
require_once 'output.php';
require_once 'input.php';

class CP_NREN_Admin extends FW_Content_Page
{
	private $state;
	function __construct()
	{
		parent::__construct("Admin", true);
		$this->org_states	= array('','subscribed', 'suspended', 'unsubscribed');
		$this->org_name_cache	= array();
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		/* If user is not subscirber- or nren-admin, we stop here */
		if (!$this->person->isNRENAdmin())
			return false;


		/* handle nren-flags */
		if (isset($_POST['subscriber'])) {
			if (isset($_POST['name']))
				$name	= Input::sanitize($_POST['name']);
			if (isset($_POST['state']))
				$state	= Input::sanitize($_POST['state']);

			switch(htmlentities($_POST['subscriber'])) {
			case 'edit':
				$this->editSubscriber($name, $state);
				break;
			case 'add':
				$this->addSubscriber($name, $state);
				break;
			case 'delete':
				$this->delSubscriber($name);
				break;
			}
		} else if (isset($_POST['account'])) {
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
				$this->addAccount($login_name, $password);
				break;
			case 'delete':
				$this->deleteAccount($login_name);
				break;
			case 'change':
				$this->changeAccount($login_name);
				break;
			}
		}

	} /* end pre_process */

	public function process()
	{
		if (!$this->person->isNRENAdmin()) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->getX509ValidCN() . " tried to access the NREN-area");
			$this->tpl->assign('reason', 'You are not an NREN-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}

		/* get all info from database and publish to template */
		$this->tpl->assign('caMode'		, Config::get_config('ca_mode'));
		$this->tpl->assign('nrenName'		, $this->person->getNREN());
		$this->tpl->assign_by_ref('nren'	, $this);
		$this->tpl->assign('subscriber_list'	, $this->getSubscribers());
		$this->tpl->assign('account_list'	, $this->getAccountInfo());
		$this->tpl->assign('self_subscriber'	, $this->person->getSubscriberOrgName());

		/* render page */
		$this->tpl->assign('content', $this->tpl->fetch('nren_admin.tpl'));

	} /* end process */



	/**
	 * editSubscriber - change an existing subscriber
	 *
	 * At the moment, the only thing that actually makes sense to change, is
	 * the state of the subscriber.
	 *
	 * @name	: The name of the subscriber.
	 * @state	: New state.
	 */
	private function editSubscriber($name, $state)
	{
		$query_id		= "SELECT nren_id FROM nrens WHERE name=?";
		$query_subscribers	= "SELECT * FROM subscribers WHERE name = ? AND nren_id = ?";
		$update			= "UPDATE subscribers SET org_state=? WHERE name=? AND nren_id=?";

		try {
			$res_id = MDB2Wrapper::execute($query_id,
						       array('text'),
						       array($this->person->getNREN()));
			if (count($res_id) < 1) {
				throw new DBQueryException("Could not find your NREN! Something seems to be misconfigured.");
			}
			$res_subscribers = MDB2Wrapper::execute($query_subscribers,
								array('text', 'text'),
								array($name, $res_id[0]['nren_id']));

			if (count($res_subscribers) > 1) {
				$msg  = "Database Inconsistency! Got duplicate (identical) subscribers (" . $name . ")";
				$msg .= " for NREN " . $this->person->getNREN() . ". Got " . count($res_subscribers);
				$msg .= ", should have found 0 or 1";
				Logger::log_event(LOG_ALERT, $msg);
				throw new DBQueryException($msg);
			}

			/* only thing you can change is state.
			 * If the subscriber is unknown or the new state
			 * is identical to the current, there's no point in
			 * going further. */
			if (count($res_subscribers) != 1 || $res_subscribers[0]['org_state'] === $state) {
				return;
			}
			MDB2Wrapper::update($update,
					    array('text', 'text', 'text'),
					    array($state, $name, $res_id[0]['nren_id']));

			Logger::log_event(LOG_NOTICE, "Changed state for $name from " . $res[0]['org_state'] . " to $state");

		} catch (DBStatementException $dbse) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . " Error in query-syntax.<BR />Server said " . $dbse->getMessage());
		} catch (DBQueryException $dbqe) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . " Problems with query.<BR />Server said " . $dbqe->getMessage());
		}
	}

	/**
	 * addSubscriber - add a new subscriber
	 *
	 * @name	: Name of subscriber
	 * @state	: The initial state to put the subscriber in.
	 */
	private function addSubscriber($name, $state)
	{
		$org_state	= Input::sanitize($state);
		$org_name	= strtolower(Input::sanitize($name));
		$nren		= $this->person->getNREN();

		$select_nrenid		= "(SELECT nren_id FROM nrens WHERE name=?)";
		$update_subscr_insert	= "INSERT INTO subscribers(name, nren_id, org_state) VALUES(?,?,?)";

		if (!isset($org_state) || $org_state === "")
			echo "orgstate not set!";
		if (!isset($org_name) || $org_name === "")
			echo "orgname not set!";
		if (!isset($nren) || $nren === "")
			echo "nren not set!";

		try {
			$res = MDB2Wrapper::execute($select_nrenid,
						    array('text'),
						    array($nren));
		} catch (DBStatementException $dbse) {
			$msg  = __FILE__ . ":" . __LINE__ . " Error in query syntax.";
			Logger::log_event(LOG_INFO, $msg);
			$msg .=	"<BR />Server said: " . $dbse->getMessage();
			Framework::error_output($msg);
			return;
		} catch (DBQueryException $dbqe) {
			$msg  = __FILE__ . ":" . __LINE__ . " Query-error. Constraint violoation in query?";
			Logger::log_event(LOG_INFO, $msg);
			$msg .= "<BR />Server said: " . $dbqe->getMessage();
			Framework::error_output($msg);
			return;
		}

		if (count($res) < 1) {
			Framework::error_output("Your NREN is unknown to Confusa! " .
						"Probably something is wrong with the configuration");
			return;
		}

		try {
			MDB2Wrapper::update($update_subscr_insert,
					    array('text',	'text',			'text'),
					    array($org_name,	$res[0]['nren_id'],	$org_state));
		} catch (DBStatementException $dbse) {
			$msg = __FILE__ . ":" . __LINE__ . " synatx error in update, server said: " . $dbse->getMessage();
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::error_output($msg);
		} catch (DBQueryException $dbqe) {
			$msg = __FILE__ . ":" . __LINE__ . " Cannot add row, duplicate entry?";
			Framework::error_output($msg);
			Logger::log_event(LOG_NOTICE, $msg);
			return;
		}

		Logger::log_event(LOG_INFO, "Added the organization $org_name with " .
				  "NREN $nren and state $org_state as a subscriber ");
	} /* end addSubscriber() */


	/**
	 * delSubscriber - remove the subscriber from the NREN and Confusa.
	 *
	 * This will remove the subscriber *permanently* along with all it's
	 * affiliated subscriber admins (this is handled by the database-schema
	 * with the 'ON DELETE CASCADE'.
	 *
	 * @name  : the name of the institution/subscriber
	 *
	 */
	private function delSubscriber($name) {
		if (!isset($name) || $name === "") {
			error_output("Cannot delete empty string!");
		}
		$nren	= $this->person->getNREN();
		$sub	= Input::sanitize($name);

		$subselect = "(SELECT nren_id FROM nrens WHERE name=?)";

		try {
			/* FIXME: add switch to force the query to fail if the
			 * subscriber does not exist. */
			MDB2Wrapper::execute("DELETE FROM subscribers WHERE name = ? AND nren_id = $subselect",
					     array('text', 'text'),
					     array($sub, $nren));

			Logger::log_event(LOG_INFO, "Deleted subscriber $sub in organization $org.\n");
			Framework::message_output("Successfully deleted subscriber $sub in organization $org.");
		} catch (DBQueryException $dbqe) {
			$msg = "Could not delete $sub in organization $org from DB.";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::message_output($msg . "<BR />Server said: " . $dbqe->getMessage());
		}
	} /* end delSubscriber */

	/**
	 * getSubscribers - get an array with subscriber and state
	 *
	 * Find all subscribers for the current NREN and return an array containing
	 * - subscriber name
	 * - subscriber state (subscribed | unsubscribed | suspended)
	 *
	 */
	private function getSubscribers()
	{
		try {
			$query = "SELECT * FROM nren_subscriber_view WHERE nren=? ORDER BY subscriber ASC";
			$res = MDB2Wrapper::execute($query, array('text'), array($this->person->getNREN()));
			if (count($res) == 0)
				return;
			$result = array();
			foreach($res as $row)
				$result[] = array('subscriber' => $row['subscriber'], 'org_state' => $row['org_state']);
		} catch (DBStatementException $dbse) {
			$msg = __FILE__ . ":" . __LINE__ . " Error in query-syntax. Verify that the query matches the database!";
			Logger::log_event(LOG_NOTICE, $msg);
			$msg .= "<BR />Server said: " . $dbse->getMessage();
			Framework::error_output($msg);
			return;
		} catch (DBQueryException $dbqe) {
			$msg =  __FILE__ . ":" . __LINE__ . " Possible constraint-violation in query. Compare query to db-schema";
			Logger::log_event(LOG_NOTICE, $msg);
			$msg .= "<BR />Server said: " . $dbse->getMessage();
			Framework::error_output($msg);
		}
		return $result;
	} /* end getSubscribers */

	/**
	 * getAccountInfo - return an array with info for *this* nren-account
	 *
	 * This function will find 
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

		if (count($res) == 1)
			$curr_account = $res[0]['account_login_name'];
		else if (count($res) > 1) {
			$msg  = "Inconsistency in the database,  more than one account tied to a single NREN. ";
			$msg .= "Got " . count($res) . " results back from the database";
			Logger::log_event(LOG_ALERT, $msg);
			throw new DBQueryException($msg);
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
				$return_res = array('account' => $curr_account, 'all' => $accounts);
			} else {
				Framework::error_output("No account-maps set for Confusa!");
				return null;
			}

		} catch (DBStatemenetException $dbse) {
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
			Framework::error_output("Login-name not set. This <B>must</B> be available when one wants to edit it.");
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
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Error in statement at " . __FILE__ . ":" . __LINE__ . ". This should be handled by some developer.");
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
					    array('text','text','text'),
					    array($cryptpw, base64_encode($iv), $login_name));
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Could not update table. Some error in the constraints? " . $dbqe->getMessage());
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Could not update table. Some error in the syntax? " . $dbse->getMessage());
			return;
		}
		Framework::message_output("Password for account '$login_name' updated successfully");
		return;
	}

	/* addAccount - add a new account for the NRENs to use.
	 *
	 * @login_name : the new login-name. This must be a unidque name (given
	 *		 by Comodo)
	 * @password   : a strong password, and must be the same as set in the
	 *		 remote CA.
	 */
	private function addAccount($login_name, $password)
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
		MDB2Wrapper::update("INSERT INTO account_map (login_name, password, ivector) VALUES(?, ?, ?)",
				    array('text','text','text'),
				    array($login_name, $cryptpw, base64_encode($iv)));

		} catch (DBQueryException $dbqe) {
			Framework::error_output("Error adding new account.<BR />\n" . $dbqe->getMessage());
			return;
		}
		Framework::message_output("Added new account $login_name to NREN " . $this->person->getNREN());
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
				Logger::log_event(LOG_NOTICE, "Inconsistency detected in the database. $org has " . count($res) . " accounts");
				return;
			}

			if (count($res) == 1) {
				if ($res[0]['account_login_name'] === $login_name) {
					/* FIXME: remove this error-output? Or
					 * is the feedback valuable? */
					Framework::error_output("Will not update NREN with the same account");
					return;
				}
			}

			$subselect="(SELECT account_map_id FROM account_map WHERE login_name=?)";

			MDB2Wrapper::update("UPDATE nrens SET login_account=$subselect WHERE name=?",
					    array('text', 'text'),
					    array($login_name, $nren));
			Framework::message_output("Changed account for $org to $login_name");
		} catch (DBStatementException $dbqe) {
			Framework::error_output("Query syntax errors. Server said: " . $dbqe->getMessage());
			return;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Database-server problems. Server said: " . $dbqe->getMessage());
			return;
		}
	} /* end changeAccount() */

	public function format_subscr_on_state($subscriber, $state)
	{
		$res = $subscriber;

		switch($state) {
		case unsubscribed:
			$res = "<FONT COLOR=\"GRAY\"><B>$res</B></FONT>";
			break;
		case suspended:
			$res = "<FONT COLOR=\"RED\"><B>$res</B></FONT>";
		case subscribed:
			$res = "<I>$res</I>";
			break;
		default:
			break;
		}
		return $res;
	}

	public function delete_button($key, $target)
	{
		if (!isset($key) || !isset($target))
			return;

		if ($key === "" || $target === "")
			return"";

		$res  = "<FORM ACTION=\"\" METHOD=\"POST\">\n";
		$res .= "<INPUT TYPE=\"hidden\" NAME=\"". $key . "\" VALUE=\"delete\">\n";
		$res .= "<INPUT TYPE=\"hidden\" NAME=\"name\" VALUE=\"" . $target . "\" />\n";
		$res .= "<INPUT TYPE=\"hidden\" NAME=\"state\" VALUE=\"\" />\n"; /* don't need state to delete */

		$res .= "<INPUT TYPE=\"IMAGE\" NAME=\"delete\" ";

		/* warning upon attempted self-deletion */
		if ($target === $this->person->getSubscriberOrgName()) {
			$res .= "onclick=\"return confirm('You are about to delete your OWN INSTITUTION (" . $target . ")!\\n";
			$res .= "          Are you sure about that?')\"";
		} else {
			$res .= "onclick=\"return confirm('Delete entry? (" . $target . ") ')\" ";
		}

		$res .= "                 value=\"delete\" src=\"graphics/delete.png\"";
		$res .= "                 alt=\"delete\" />\n";
		$res .= "</FORM>\n";
		echo $res;
	}

	public function createSelectBox($active, $list = null, $name)
	{
		$arg_list = $list;
		if (!isset($list))
			$arg_list = $this->org_states;

		return Output::create_select_box($active, $arg_list, $name);
	} /* end createSelectBox */
}


$fw = new Framework(new CP_NREN_Admin());
$fw->start();

?>
