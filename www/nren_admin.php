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
			if (isset($_POST['id']))
				$id	= Input::sanitize($_POST['id']);

			if (isset($_POST['state']))
				$state	= Input::sanitize($_POST['state']);

			if (isset($_POST['name']))
				$name	= Input::sanitize($_POST['name']);

			switch(htmlentities($_POST['subscriber'])) {
			case 'edit':
				$this->editSubscriber($id, $state);
				break;
			case 'add':
				$this->addSubscriber($name, $state);
				break;
			case 'delete':
				$this->delSubscriber($id);
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
		$this->tpl->assign('nrenName'		, $this->person->getNREN());
		$this->tpl->assign_by_ref('nren'	, $this);
		$this->tpl->assign('subscriber_list'	, $this->getSubscribers());
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
	private function editSubscriber($id, $state)
	{
		$query_id		= "SELECT nren_id FROM nrens WHERE name=?";
		$update			= "UPDATE subscribers SET org_state=? WHERE subscriber_id=? AND nren_id=?";

		try {
			$res_id = MDB2Wrapper::execute($query_id,
						       array('text'),
						       array($this->person->getNREN()));
			if (count($res_id) < 1) {
				Framework::error_output("Could not find your NREN! Something seems to be misconfigured.");
			}

			/* only thing you can change is state.
			 * If new state is identical to the current,
			 * there's no point in going further. */
			if ($res_subscribers[0]['org_state'] === $state) {
				return;
			}
			MDB2Wrapper::update($update,
					    array('text', 'text', 'text'),
					    array($state, $id, $res_id[0]['nren_id']));

			Logger::log_event(LOG_NOTICE, "Changed state for subscriber with ID $id from " . $res[0]['org_state'] . " to $state");

		} catch (DBStatementException $dbse) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . " Error in query-syntax.<BR />Server said " . $dbse->getMessage());
			Logger::log_event(LOG_NOTICE, "Problem occured when editing the state of subscriber $id: " . $dbse->getMessage());
		} catch (DBQueryException $dbqe) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . " Problems with query.<BR />Server said " . $dbqe->getMessage());
			Logger::log_event(LOG_NOTICE, "Problem occured when editing subscriber $id: " . $dbse->getMessage());
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

		if (empty($org_name)) {
		    Framework::error_output("Please specify a name for the subscriber!");
		    return;
		}

		$select_nrenid		= "SELECT nren_id FROM nrens WHERE name=?";
		$constraint_query	= "SELECT subscriber_id FROM subscribers WHERE name=? and nren_id = ?";
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
			Logger::log_event(LOG_NOTICE, $msg);
			$msg .=	"<BR />Server said: " . $dbse->getMessage();
			Framework::error_output($msg);
			return;
		} catch (DBQueryException $dbqe) {
			$msg  = __FILE__ . ":" . __LINE__ . " Query-error. Constraint violoation in query?";
			Logger::log_event(LOG_NOTICE, $msg);
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
		    $check = MDB2Wrapper::execute($constraint_query,
					array('text', 'text'),
					array($name, $res[0]['nren_id']));

		    if (count($check) > 0) {
			Framework::error_output("Subscriber names must be unique per NREN! " .
				"Found an existing subscriber with the name '$name' and " .
				"id " . $check[0]['subscriber_id'] . "!");
			return;
		    }
		} catch (DBStatementException $dbse) {
		    $msg = __FILE__ . ":" . __LINE__ . " syntax error in constraint check, server said: " . $dbse->getMessage();
		    Logger::log_event(LOG_NOTICE, $msg);
		    Framework::error_output($msg);
		} catch (DBQueryException $dbqe) {
		    $msg = __FILE__ . ":" . __LINE__ . " cannot add row, duplicate entry?";
		    Logger::log_event(LOG_NOTICE, $msg);
		    Framework::error_output($msg);
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
	private function delSubscriber($id) {
		if (!isset($id) || $id === "") {
			Framework::error_output("Cannot delete subscriber with unknown id!");
		}
		$nren	= $this->person->getNREN();

		$subselect = "(SELECT nren_id FROM nrens WHERE name=?)";

		try {
			/* FIXME: add switch to force the query to fail if the
			 * subscriber does not exist. */
			MDB2Wrapper::execute("DELETE FROM subscribers WHERE subscriber_id = ? AND nren_id = $subselect",
					     array('text', 'text'),
					     array($id, $nren));

			Logger::log_event(LOG_INFO, "Deleted subscriber with ID $id.\n");
			Framework::message_output("Successfully deleted subscriber with ID $id.");
		} catch (DBQueryException $dbqe) {
			$msg = "Could not delete subscriber with ID $id from DB.";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::message_output($msg . "<BR />Server said: " . $dbqe->getMessage());
		} catch (DBStatementException $dbse) {
			$msg = "Could not delete subsriber with ID $id from DB, due to problems with the " .
				"statement. Probably this is a configuration error. Server said: " .
				$dbse->getMessage();
			Logger::log_event(LOG_NOTICE, "ADMIN: " . $msg);
			Framework::message_output($msg);
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
				$result[] = array('subscriber' => $row['subscriber'], 'org_state' => $row['org_state'], 'subscriber_id' => $row['subscriber_id']);
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

	public function format_subscr_on_state($state)
	{
		switch($state) {
		case unsubscribed:
			$styling = "color: gray; font-weight: bold;";
			break;
		case suspended:
			$styling = "color: red; font-weight: bold;";
			break;
		case subscribed:
			$styling .= "font-style: italic;";
			break;
		default:
			break;
		}

		return $styling;
	}

	public function delete_button($key, $target, $id)
	{
		if (!isset($key) || !isset($target))
			return;

		if ($key === "" || $target === "")
			return"";

		$res  = "<form action=\"\" method=\"post\">\n";
		$res .= "<div>\n";
		$res .= "<input type=\"hidden\" name=\"". $key . "\" value=\"delete\" />\n";
		$res .= "<input type=\"hidden\" name=\"name\" value=\"" . $target . "\" />\n";
		$res .= "<input type=\"hidden\" name=\"state\" value=\"\" />\n"; /* don't need state to delete */
		$res .= "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\" />\n";
		$res .= "<input type=\"image\" name=\"delete\" ";
		$res .= "title=\"Delete\" ";
		/* warning upon attempted self-deletion */
		if ($target === $this->person->getSubscriberOrgName()) {
			$res .= "onclick=\"return confirm('You are about to delete your OWN INSTITUTION (" . $target . ")!\\n";
			$res .= "          Are you sure about that?')\"";
		} else {
			$res .= "onclick=\"return confirm('Delete entry with ID $id? (" . $target . ") ')\" ";
		}

		$res .= "                 value=\"delete\" src=\"graphics/delete.png\"";
		$res .= "                 alt=\"delete\" />\n";
		$res .= "</div>\n";
		$res .= "</form>\n";
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
