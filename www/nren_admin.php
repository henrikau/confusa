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
		if (!$this->person->is_nren_admin())
			return false;


		/* handle nren-flags */
		if (isset($_GET['subscriber'])) {
			if (isset($_GET['name']))
				$name	= Input::sanitize($_GET['name']);
			if (isset($_GET['state']))
				$state	= Input::sanitize($_GET['state']);

			switch(htmlentities($_GET['subscriber'])) {
			case 'edit':
				$this->editSubscriber($name, $state);
				break;
			case 'add':
				$this->addSubscriber($name, $state);
				break;
			case 'delete':
				$this->delSubscriber($name, $state);
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
				$this->delAccount($login_name);
				break;
			case 'change':
				$this->changeAccount($login_name);
				break;
			}
		}

	} /* end pre_process */

	public function process()
	{
		if (!$this->person->is_nren_admin()) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->get_valid_cn() . " tried to access the NREN-area");
			$this->tpl->assign('reason', 'You are not an NREN-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}

		/* get all info from database and publish to template */
		$this->tpl->assign_by_ref('nren'	, $this);
		$this->tpl->assign('subscriber_list'	, $this->getSubscribers());
		$this->tpl->assign('account_list'	, $this->getAccountInfo());

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

		$query_id = "SELECT nren_id FROM nrens WHERE name=?";

		$res_id = MDB2Wrapper::execute($query_id,
					       array('text'),
					       array($this->person->get_nren()));

		if (count($res_id) < 1) {
		    throw new DBQueryException("Could not find your NREN! Something seems to be misconfigured.");
		}

		$query = "SELECT * FROM subscribers WHERE name = ? AND nren_id = ?";
		$res = MDB2Wrapper::execute($query,
					    array('text', 'text'),
					    array($name, $res_id[0]['nren_id']));
		if (count($res) > 1)
			throw new DBQueryException("Could not retrieve the correct subscriber. Got " . count($res) . " rows in return");
		if (count($res) != 1)
			return;

		/* only thing you can change is state */
		if ($res[0]['org_state'] === $state) {
			return;
		}
		$update = "UPDATE subscribers SET org_state=? WHERE name=? AND nren_id=?";
		MDB2Wrapper::update($update, array('text', 'text', 'text'), array($state, $name, $res_id[0]['nren_id']));
		Logger::log_event(LOG_NOTICE, "Changed state for $name from " . $res[0]['org_state'] . " to $state");
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
		$org_name	= Input::sanitize($name);
		$nren		= $this->person->get_nren();

		if (!isset($org_state) || $org_state === "")
			echo "orgstate not set!";
		if (!isset($org_name) || $org_name === "")
			echo "orgname not set!";
		if (!isset($nren) || $nren === "")
			echo "nren not set!";

		$subselect = "(SELECT nren_id FROM nrens WHERE name=?)";
		$res = MDB2Wrapper::execute($subselect,
					    array('text'),
					    array($nren));

		if (count($res) < 1) {
		    Framework::error_output("Your NREN is unknown to Confusa! " .
			  "Probably something is wrong with the configuration");
		}

		$update = "INSERT INTO subscribers(name, nren_id, org_state) VALUES(?,?,?)";
		try {
		MDB2Wrapper::update($update,
				    array('text',	'text',			'text'),
				    array($org_name,	$res[0]['nren_id'],	$org_state));
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Cannot add row, duplicate entry?");
			return;
		}
		/* FIXME: detect errors */

		Logger::log_event(LOG_INFO, "Added the organization $org_name with " .
				  "NREN $nren and state $org_state as a subscriber ");
	} /* end addSubscriber() */


	/**
	 * delSubscriber - remove the subscriber from the NREN
	 *
	 * @name : the name of the institution
	 */
	private function delSubscriber($name, $state) {
		if (!isset($name) || $name === "") {
			error_output("Cannot delete empty string!");
		}
		$nren	= $this->person->get_nren();
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
			Logger::log_event(LOG_NOTICE, "Could not delete $sub in organization $org from DB.\n");
			Framework::message_output("Could not delete $sub in organization $org from DB.");
		}
	}

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
		$query = "SELECT * FROM nren_subscriber_view WHERE nren=? ORDER BY subscriber ASC";
		$res = MDB2Wrapper::execute($query, array('text'), array($this->person->get_nren()));
		if (count($res) == 0)
			return;
		$result = array();
		foreach($res as $row)
			$result[] = array('subscriber' => $row['subscriber'], 'org_state' => $row['org_state']);

		return $result;
	} /* end getSubscribers */

	private function getAccountInfo()
	{

		/* Get the current account */
		$query	= "SELECT * FROM nren_account_map_view WHERE nren = ?";
		$res	= MDB2Wrapper::execute($query, array('text'), array($this->person->get_nren()));
		if (count($res) == 1)
			$curr_account = $res[0]['account_login_name'];
		else if (count($res) > 1) {
			$msg  = "Inconsistency in the database! You have more than one account tied in! How is this possible?";
			$msg .= "Got " . count($res) . " results back from the database";
			throw new DBQueryException($msg);
		}

		/* Get all available accounts */
		$accounts	= array();
		$query		= "SELECT login_name FROM account_map";
		$res		= MDB2Wrapper::execute($query, null, null);
		if (count($res) < 1)
			throw new DBQueryException("No account-maps set for Confusa!");
		foreach($res as $row) {
			$accounts[] = $row['login_name'];
		}

		return array('account' => $curr_account, 'all' => $accounts);
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
		$nren = $this->person->get_nren();

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
		Framework::message_output("Added new account $login_name to NREN " . $this->person->get_nren());
		return;
	}

	private function deleteAccount($login_name)
	{
		/* FIXME:
		 *
		 * Handle scenario when more than one NREN is still using the
		 * account.
		 */
	}
	private function changeAccount($login_name)
	{
		$nren = $this->person->get_nren();

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

		$res  = "<FORM ACTION=\"\" METHOD=\"GET\">\n";
		$res .= "<INPUT TYPE=\"hidden\" NAME=\"". $key . "\" VALUE=\"delete\">\n";
		$res .= "<INPUT TYPE=\"hidden\" NAME=\"name\" VALUE=\"" . $target . "\" />\n";
		$res .= "<INPUT TYPE=\"hidden\" NAME=\"state\" VALUE=\"\" />\n"; /* don't need state to delete */
		$res .= "<INPUT TYPE=\"IMAGE\" NAME=\"delete\" ";
		$res .= "       onclick=\"return confirm('Delete entry? (" . $target . ") ')\" ";
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
