<?php
require_once 'confusa_include.php';
include_once 'framework.php';
include_once 'mdb2_wrapper.php';
include_once 'db_query.php';
include_once 'logger.php';
include_once 'input.php';


/**
 * Admin - administer admins for the subscriber/nren
 *
 * Each NREN has a set of NREN-admins and subscriber-admins. Each subscriber may
 * manage its own subuscriber admins and subadmin.
 */
class CP_Admin extends FW_Content_Page
{

	function __construct()
	{
		parent::__construct("Admin", true);
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		/* IF user is not subscirber- or nren-admin, we stop here */
		if (!($this->person->isSubscriberAdmin() || $this->person->isNRENAdmin()))
			return false;

		if (isset($_POST['nren_operation'])) {
			if (!$this->person->isNRENAdmin()) {
				Framework::error_output("You have the wrong permissions for that operation!");
				return false;
			}

			/* operations called by the NREN-admin */
			switch(htmlentities($_POST['nren_operation'])) {
				case 'delete_nren_admin':
					$admin = Input::sanitize($_POST['nren_admin']);
					$this->deleteAdmin($admin, 2);
					break;
				case 'add_nren_admin':
					$admin = Input::sanitize($_POST['nren_admin']);
					$nren = $this->person->getNREN();
					$this->addAdmin($admin,2,NULL,$nren);
					break;
				case 'delete_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$this->deleteAdmin($admin,1);
					break;
				case 'add_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$subscriber = Input::sanitize($_POST['subscriber']);
					$this->addAdmin($admin,1,$subscriber,NULL);
					break;
				default:
					break;
			}
		/* operations called by the subscriber admin */
		} else if (isset($_POST['subs_operation'])) {
			if (!$this->person->isSubscriberAdmin()) {
				Framework::error_output("You have the wrong permissions for that operation!");
				return false;
			}

			switch(htmlentities($_POST['subs_operation'])) {
				case 'delete_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$this->deleteAdmin($admin,1);
					break;
				case 'add_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$subscriber = $this->person->getSubscriberOrgName();
					$this->addAdmin($admin,1,$subscriber,NULL);
					break;
				case 'delete_subs_sub_admin':
					$admin = Input::sanitize($_POST['subs_sub_admin']);
					$this->deleteAdmin($admin,0);
					break;
				case 'add_subs_sub_admin':
					$admin = Input::sanitize($_POST['subs_sub_admin']);
					$subscriber = $this->person->getSubscriberOrgName();
					$this->addAdmin($admin,0,$subscriber,NULL);
					break;
				default:
					break;
			}
		}
	}

	/*
	 * Direct the user to the respective operations she may perform
	 * Currently the admin page can manage NREN subscriptions,
	 * organization subscriptions and (Comodo) subaccounts.
	 *
	 * The post parameters that are passed are supposed to be $this->sanitized in
	 * the respective functions that take them
	 */
	public function process()
	{
		/* IF user is not an admin, we stop here */
		if (!($this->person->isAdmin())) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->getX509ValidCN() . " was rejected at the admin-interface");
			$this->tpl->assign('reason', 'You do not have sufficient rights to view this page');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return false;
		}

		if ($this->person->isNRENAdmin()) { /* NREN admin display */
			$admins=$this->getNRENAdmins($this->person->getNREN());
			$subscribers=$this->getSubscribers($this->person->getNREN());
			$current_subscriber = "";

			if (isset($_POST['subscriber'])) {
				$current_subscriber = Input::sanitize($_POST['subscriber']);
			} else if (count($subscribers) > 0) {
				$current_subscriber = $subscribers[0];
			}

			if (!empty($current_subscriber)) {
				$subscriber_admins = $this->getSubscriberAdmins($current_subscriber, 1);
				$this->tpl->assign('subscriber', $current_subscriber);
				$this->tpl->assign('subscriber_admins', $subscriber_admins);
			}

			$this->tpl->assign('nren_admins', $admins);
			$this->tpl->assign('nren', $this->person->getNREN());
			$this->tpl->assign('subscribers', $subscribers);

		} else if ($this->person->isSubscriberAdmin()) { /* subscriber admin display */
			$subscriber = $this->person->getSubscriberOrgName();
			$subscriber_admins = $this->getSubscriberAdmins($subscriber, 1);
			$nren = $this->person->getNREN();
			$nren_admins = $this->getNRENAdmins($nren);
			$this->tpl->assign('nren_admins', $nren_admins);
			$this->tpl->assign('nren', $nren);
			$this->tpl->assign('subscriber', $subscriber);
			$this->tpl->assign('subscriber_admins', $subscriber_admins);

			$subscriber_sub_admins = $this->getSubscriberAdmins($this->person->getSubscriberOrgName(), 0);
			$this->tpl->assign('subscriber_sub_admins', $subscriber_sub_admins);

		} else if ($this->person->isSubscriberSubAdmin()) { /* subscriber-sub-admin display */
			$subscriber = $this->person->getSubscriberOrgName();
			$subscriber_admins = $this->getSubscriberAdmins($subscriber, 1);
			$subscriber_sub_admins = $this->getSubscriberAdmins($this->person->getSubscriberOrgName(), 0);
			/* remove the administrator herself from the list */
			$subscriber_sub_admins = array_diff($subscriber_sub_admins, array($this->person->getEPPN()));
			$this->tpl->assign('subscriber_sub_admins', $subscriber_sub_admins);
			$this->tpl->assign('subscriber_admins', $subscriber_admins);
			$this->tpl->assign('subscriber', $subscriber);
		}

		$this->tpl->assign('self', $this->person->getEPPN());
		$this->tpl->assign('content', $this->tpl->fetch('admin.tpl'));
	}


	/**
	 * Get all the NREN admins that belong to a certain NREN
	 *
	 * @param $nren The NREN for which the respective admins are queried
	 */
	private function getNRENAdmins($nren)
	{

		$query = "SELECT admin FROM admins WHERE admin_level='2' AND nren=";
		$query .= "(SELECT nren_id FROM nrens WHERE name = ?)";

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Cannot retrieve (nren)admins from database!<BR /> " .
				"Probably wrong syntax for query, ask an admin to investigate. Server said: " . $dbse->getMessage());
			return null;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Query failed. This probably means that the values " .
				"passed to the database are wrong. Server said: " . $dbqe->getMessage());
			return null;
		}

		$admins = array();

		if (count($res) > 0) {

			foreach($res as $row) {
				$admins[] = $row['admin'];
			}
		}

		return $admins;
	}

	/**
	 * Get all the admins that belong to a certain subscriber
	 *
	 * @param $subscriber The subscriber whose admins are sought
	 * @param $level 0 or 1, dependant on whether the query is for subscriber
	 * admins or subscriber sub-admins
	 */
	private function getSubscriberAdmins($subscriber, $level)
	{
		$query = "SELECT admin FROM admins WHERE admin_level=? AND subscriber=";
		$query .= "(SELECT subscriber_id FROM subscribers WHERE name=?)";

		try {
			$res = MDB2Wrapper::execute($query,
										array('text','text'),
										array($level, $subscriber));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Cannot retrieve (subscriber) admins from database!<BR /> " .
				"Probably wrong syntax for query, ask an admin to investigate. Server said: " . $dbse->getMessage());
			return null;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Query failed. This probably means that the values passed to the "
								. "database are wrong. Server said: " . $dbqe->getMessage());
			return null;
		}

		$subscribers = array();

		if (count($res) > 0) {

			foreach($res as $row) {
				$subscribers[] = $row['admin'];
			}
		}

		return $subscribers;
	}

	/*
	 * Get all the subscribers that belong to an NREN
	 *
	 * @param $nren The NREN for which the subscribers are to be returned
	 */
	private function getSubscribers($nren)
	{
		$query = "SELECT subscriber FROM nren_subscriber_view WHERE nren=?";

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch(DBStatementException $dbse) {
			Framework::error_output("Cannot retrieve subscriber from database!<BR /> " .
				"Probably wrong syntax for query, ask an admin to investigate. Server said: " . $dbse->getMessage());
			return null;
		} catch(DBQueryException $dbqe) {
			Framework::error_output("Query failed. This probably means that the values passed to the "
								. "database are wrong. Server said: " . $dbqe->getMessage());
			return null;
		}

		$subscribers = array();

		if (count($res) > 0) {

			foreach($res as $row) {
				$subscribers[] = $row['subscriber'];
			}
		}

		return $subscribers;
	}

	/*
	 * Add an admin to the DB, either a subscriber or a NREN admin.
	 * One of subscriber or nren must be specified, and it must match the
	 * specified administrator level
	 *
	 * @param $admin The principal identifier of the admin
	 * @param $level The level of the admin (2 - NREN-admin, 1 - subscriber-admin,
	 *	0 - subscriber-sub-admin)
	 * @param $subscriber The name of the subscriber of which this is the admin,
	 *		level must be 0 or 1
	 * @param $nren The name of the NREN of which this is a admin, level must 2
	 */
	private function addAdmin($admin, $level, $subscriber=NULL, $nren=NULL)
	{
		$nren_id = NULL;
		$subscriber_id = NULL;

		if ($nren === NULL && $subscriber === NULL) {
			Framework::error_output("Not both NREN and subscriber can be empty when adding an admin!");
			return;
		} else if ($admin == NULL) {
			Framework::error_output("The name of the admin must not be empty!");
			return;
		}

		try {
			if ($level == 2) { /* Insert a NREN-admin */
				$query = "SELECT nren_id FROM nrens WHERE name=?";
				$res = MDB2Wrapper::execute($query,
											array('text'),
											array($nren));

				if (count($res) === 1) {
					$nren_id = $res[0]['nren_id'];
				} else {
					Framework::error_output("Could not retrieve exactly one NREN" .
							" with name $nren WHEN inserting a new admin! Got " . count($res) . "results!");
				}
			} else { /* insert a subscriber-admin or subscriber-sub-admin */
				$query = "SELECT subscriber_id FROM subscribers WHERE name=?";
				$res = MDB2Wrapper::execute($query,
											array('text'),
											array($subscriber));

				if (count($res) === 1) {
					$subscriber_id = $res[0]['subscriber_id'];
				} else {
					Framework::error_output("Could not retrieve exactly one subscriber" .
							" with name $subscriber WHEN inserting a new admin! Got " . count($res) . "results!");
				}
			}
		} catch (DBStatementException $dbse) {
			Framework::error_output("Could not retrieve NREN/subscriber for which you are " .
									"trying to add an admin. Probably there's a server side problem, contact " .
									"an administrator! Server said " . $dbse->getMessage());
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Could not retrieve NREN/subscriber for which you are " .
									 "trying to add an admin! There seems to be a problem with the " .
									 "received data. Server said " . $dbqe->getMessage());
		}

		$query = "INSERT INTO admins(admin, admin_level, nren, subscriber) ";
		$query .= "VALUES(?,?,?,?)";

		try {
			MDB2Wrapper::update($query,
								 array('text','text','text','text'),
								 array($admin,$level,$nren_id,$subscriber_id));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Inserting the admin into the database failed, because the statement " .
									 "was bad. Please contact an administrator. Server said " . $dbse->getMessage());
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Inserting the admin into the database failed because of problems " .
									 "with the supplied data. Server said " . $dbqe->getMessage() .
									 " Maybe an admin with that name already exists?");
		}
	}

	/*
	 * Delete an administrator from the DB
	 *
	 * @param $level the privilege level of the admin that is to be deleted
	 *			(this is for added security)
	 * @param $admin The eduPersonPrincipalName (or similar identifier) for the
	 *			admin that is to be deleted
	 */
	private function deleteAdmin($admin, $level)
	{
		$query = "DELETE FROM admins WHERE admin=? and admin_level=?";

		try {
			MDB2Wrapper::update($query,
								array('text','text'),
								array($admin, $level));
		} catch(DBStatementException $dbse) {
			Framework::error_output("Could not delete the admin because the statement was bad " .
									 "Please contact an administrator. Server said " . $dbse->getMessage());
		} catch(DBQueryException $dbqe) {
			Framework::error_output("Could not delete the admin because of problems with the " .
									 "received data. Server said " . $dbqe->getMessage());
		}
	}
}

$fw = new Framework(new CP_Admin());
$fw->start();


?>

