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
				$name	= htmlentities($_GET['name']);
			if (isset($_GET['state']))
				$state	= htmlentities($_GET['state']);

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
				$login_name = $_POST['login_name'];
			if (isset($_POST['password']))
				$password = $_POST['password'];

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
		if (!$this->person->is_nren_admin())
			return;
		/* echo "<H3>Administration Area for <I>" . $this->person->get_orgname() . "</I></H3>\n"; */
		/* echo " [ " . create_link($_SERVER['SCRIPT_NAME'] . "?subscriber",	"Subscribers")	. " ] "; */
		/* echo " [ " . create_link($_SERVER['SCRIPT_NAME'] . "?account",		"Accounts")	. " ] "; */
		echo "<BR />\n";
		
		/* list all subscriptor */
		$this->showSubscribers();

		/* Add account-info for this nren, make it possible to update account */
		$this->listAccountInfo();
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
		$query = "SELECT * FROM subscribers WHERE name = ? AND nren_name = ?";
		$res = MDB2Wrapper::execute($query,
					    array('text', 'text'),
					    array($name, $this->person->get_orgname()));
		if (count($res) > 1)
			throw new DBQueryException("Could not retrieve the correct subscriber. Got " . count($res) . " rows in return");
		if (count($res) != 1)
			return;

		/* only thing you can change is state */
		if ($res[0]['org_state'] === $state) {
			return;
		}
		$update = "UPDATE subscribers SET org_state=? WHERE name=? AND nren_name=?";
		MDB2Wrapper::update($update, array('text', 'text', 'text'), array($state, $name, $this->person->get_orgname()));
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
		$nren		= $this->person->get_orgname();

		if (!isset($org_state) || $org_state === "")
			echo "orgstate not set!";
		if (!isset($org_name) || $org_name === "")
			echo "orgname not set!";
		if (!isset($nren) || $nren === "")
			echo "nren not set!";
		echo "all set<BR />\n";

		$update = "INSERT INTO subscribers(name, nren_name, org_state) VALUES(?,?,?)";
		echo "Update: $update <BR />\n";
		MDB2Wrapper::update($update,
				    array('text',	'text',		'text'),
				    array($org_name,	$nren,		$org_state));
		/* FIXME: detect errors */

		Logger::log_event(LOG_INFO, "Added the organization $org_name with " .
				  "NREN $nren and state $org_state as a subscriber ");
	} /* end addSubscriber() */


	/*
	 * Delete a registered (identity-vetting) instituion
	 */
	private function delSubscriber($name, $state) {
		if (!isset($name) || $name === "") {
			error_output("Cannot delete empty string!");
		}
		$nren	= $this->person->get_orgname();
		$sub	= Input::sanitize($name);
		MDB2Wrapper::execute("DELETE FROM subscribers WHERE name = ? AND nren_name = ?",
				     array('text', 'text'),
				     array($sub, $nren));
		/* FIXME: test for errors */


		Logger::log_event(LOG_INFO, "Deleted subscriber $sub in organization $org.\n");
	}

	/**
	 * showSubscribers
	 *
	 * Show a mask with all the organizations currently subscribed to the
	 * service. Show visual elements permitting the user to update, add and
	 * delete such entries.
	 */
	private function showSubscribers()
	{
		/*
		 * mimic tables with <div> elements, because in legal html, forms
		 * may not be interleaved with table elements, i.e. we can not
		 * place a form within a whole table row.
		 *
		 * But for <div>s, there is no such restriction
		 */
		$table	= "<DIV CLASS=\"admin_table\">\n";
		$table	= "</DIV>\n";

		$tr	= "<DIV CLASS=\"admin_table_row\">\n";
		$tr_e	= "</DIV>\n";

		$td	= "<DIV CLASS=\"admin_table_cell\">\n";
		$td_e	= "</DIV>\n";

		
		$query = "SELECT * FROM nren_subscriber_view WHERE nren=? ORDER BY subscriber ASC";
		$res = MDB2Wrapper::execute($query, array('text'), array($this->person->get_orgname()));
		if (count($res) == 0)
			return;

		echo "Show subscribers for <B><I>" . $this->person->get_orgname() . "</I></B><BR /><BR />\n";
		echo $table;
		echo $tr;
		echo $td . $td_e; /* button  */
		echo $td . "<B>Organization</B>"	. $td_e;
		echo $td . $td_e; /* FIXME: space */
		echo $td . "<B>State</B>"		. $td_e;
		echo $td ." ". $td_e; /* FIXME: space */
		echo $td . $td_e;     /* update-button (<FORM>) */
		echo $tr_e;
		echo $tr . $td . $td_e . $tr_e;
		foreach($res as $key => $val) {
			echo $tr;

			echo $td . $this->delete_button("subscriber", $val['subscriber']) . $td_e;
			echo $td . $this->format_subscr_on_state($val['subscriber'], $val['org_state']) . $td_e;

			/* FIXME: space, air, should be fixed with borders in the table */
			echo $td ." ". $td_e;

			echo "<FORM ACTION=\"\">\n";
			echo "<INPUT TYPE=\"hidden\" NAME=\"subscriber\" VALUE=\"edit\">\n";
			echo "<INPUT TYPE=\"hidden\" NAME=\"name\" VALUE=\"" . $val['subscriber'] . "\">\n";
			echo $td . create_select_box($val['org_state'], $this->org_states, 'state') . $td_e;
			echo $td ." ". $td_e;
			echo $td;
			echo "<INPUT TYPE=\"submit\" CLASS=\"button\" VALUE=\"Update\" />";
			echo "</FORM>\n";
			echo $td_e;

			echo $tr_e;
		} /* end foreach */


		echo $tr;
		echo $td . $td_e;

		echo $td;

		echo "<FORM ACTION=\"\" METHOD=\"GET\">";
		echo "<INPUT TYPE=\"hidden\" NAME=\"subscriber\" VALUE=\"add\" />\n";
		echo "<INPUT TYPE=\"TEXT\" NAME=\"name\" />";
		echo $td_e;
		echo $td . $td_e;
		echo $td . create_select_box('',$this->org_states,'state') . $td_e;
		echo $td . $td_e;
		echo $td;
		echo "<INPUT TYPE=\"submit\" VALUE=\"Add new\" />";
		echo "</FORM>";
		echo $td_e;

		echo $tr_e;


		echo $table_e;

	} /* end show_subscribers() */

	/**
	 * listAccountInfo - show info about the current account with comodo
	 *		     used for creating credentials.
	 */
	private function listAccountInfo()
	{
		$table	= " <DIV CLASS=\"admin_table\">\n";
		$tr	= " <DIV CLASS=\"admin_table_row\">\n";
		$td	= " <DIV CLASS=\"admin_table_cell\">\n";
		$td_e	= "</DIV>\n";
		$tr_e	= "</DIV>\n";
		$table_e= "</DIV>\n";

		/*
		 * Find the current account tied to this NREN
		 */
		$query	= "SELECT * FROM nrens_account_map_view WHERE nren_name = ?";
		$res	= MDB2Wrapper::execute($query, array('text'), array($this->person->get_orgname()));
		if (count($res) == 1)
			$curr_account = $res[0]['account_login_name'];
		else if (count($res > 1)) {
			$msg  = "Inconsistency in the database! You have more than one account tied in! How is this possible?";
			$msg .= "Got " . count($res) . " results back from the database";
			throw new DBQueryException($msg);
		}

		/*
		 * Get all accounts (so we can choose) 
		 */
		$accounts	= array();
		$query		= "SELECT login_name FROM account_map";
		$res		= MDB2Wrapper::execute($query, null, null);
		if (count($res) < 1)
			throw new DBQueryException("No account-maps set for Confusa!");
		foreach($res as $row) {
			$accounts[] = $row['login_name'];
		}

		echo "<H4>CA-Account information for " . $this->person->get_orgname() . "</H4>\n";
		echo "<FoRM ACTION=\"\" METHOD=\"POST\">\n";
		echo "<INPUT TYPE=\"hidden\" NAME=\"account\" VALUE=\"change\">\n";
		echo create_select_box($curr_account, $accounts, 'login_name');
		echo "<INPUT TYPE=\"submit\" VALUE=\"Change account\">\n";
		echo "</FORM>\n";


	}

	private function editAccount($login_name, $password)
	{
		/* FIXME */
		return;
	}

	private function addAccount($login_name, $password)
	{
		/* FIXME */
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
		$org = $this->person->get_orgname();
		$res = MDB2Wrapper::execute("SELECT nren_name FROM nrens_account_map_view", null, null);
		if (count($res) > 1)
			return;
		if (count($res) == 1)
			if ($res[0]['account_login_name'] === $login_name)
				return;

		echo "Changing account for " . $this->person->get_orgname() . " to $login_name <BR />\n";
		$update = "UPDATE nrens SET login_name=? WHERE name=?";
		MDB2Wrapper::update($update, array('text', 'text'), array($login_name, $org));
	} /* end changeAccount() */

	private function format_subscr_on_state($subscriber, $state)
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

	private function delete_button($key, $target)
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
}


$fw = new Framework(new CP_NREN_Admin());
$fw->start();

?>
