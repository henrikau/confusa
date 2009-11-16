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
class CP_Admin extends Content_Page
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
				Framework::error_output("You need NREN-administrator privileges in order to complete this request.");
				return false;
			}

			/* operations called by the NREN-admin */
			switch(htmlentities($_POST['nren_operation'])) {
				case 'delete_nren_admin':
					$admin = Input::sanitize($_POST['nren_admin']);
					$this->deleteAdmin($admin, NREN_ADMIN);
					break;
				case 'downgrade_self':
					$this->downgradeNRENAdmin($this->person->getEPPN(),
								  $this->person->getSubscriber()->getIdPName());
					break;
				case 'upgrade_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$this->upgradeSubscriberAdmin($admin);
					break;
				case 'add_nren_admin':
					$admin = Input::sanitize($_POST['nren_admin']);
					$this->addNRENAdmin($admin);
					break;
				case 'delete_subs_admin':
					$admin = Input::sanitizeText($_POST['subs_admin']);
					$this->deleteAdmin($admin,SUBSCRIBER_ADMIN);
					break;
				case 'add_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$subscriber = Input::sanitizeText($_POST['subscriber']);
					$this->addSubscriberAdmin($admin, SUBSCRIBER_ADMIN, $subscriber);
					break;
				default:
					break;
			}
		/* operations called by the subscriber admin */
		} else if (isset($_POST['subs_operation'])) {
			if (!$this->person->isSubscriberAdmin()) {
				Framework::error_output("You do not have sufficient permissions in order to complete this transaction.");
				return false;
			}

			switch(htmlentities($_POST['subs_operation'])) {
				case 'delete_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$this->deleteAdmin($admin, SUBSCRIBER_ADMIN);
					break;
				case 'add_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$this->addSubscriberAdmin($admin,SUBSCRIBER_ADMIN,$this->person->getSubscriber()->getIdPName());
					break;
				case 'downgrade_subs_admin':
					$admin = Input::sanitize($_POST['subs_admin']);
					$this->downgradeSubscriberAdmin($admin, $this->person->getSubscriber()->getIdPName());
					break;
				case 'upgrade_subs_sub_admin':
					$admin = Input::sanitize($_POST['subs_sub_admin']);
					$this->upgradeSubscriberSubAdmin($admin, $this->person->getSubscriber()->getIdPName());
					break;
				case 'delete_subs_sub_admin':
					$admin = Input::sanitize($_POST['subs_sub_admin']);
					$this->deleteAdmin($admin,SUBSCRIBER_SUB_ADMIN);
					break;
				case 'add_subs_sub_admin':
					$admin = Input::sanitize($_POST['subs_sub_admin']);
					$this->addSubscriberAdmin($admin,SUBSCRIBER_SUB_ADMIN,$this->person->getSubscriber()->getIdPName());
					break;
				default:
					break;
			}
		}
	} /* End pre_process() */

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

			/* Are we looking at a particular subscriber? */
			if (isset($_POST['subscriber'])) {
				$current_subscriber = Input::sanitizeText($_POST['subscriber']);
			} else if (count($subscribers) > 0) {
				$current_subscriber = $subscribers[0];
			}

			if (!empty($current_subscriber)) {
				$subscriber_admins = $this->getSubscriberAdmins($current_subscriber, SUBSCRIBER_ADMIN);
				$this->tpl->assign('subscriber', $current_subscriber);
				$this->tpl->assign('subscriber_admins', $subscriber_admins);
			}

			$this->tpl->assign('nren_admins', $admins);
			$this->tpl->assign('nren', $this->person->getNREN());
			$this->tpl->assign('subscribers', $subscribers);

		} else if ($this->person->isSubscriberAdmin()) { /* subscriber admin display */
			$subscriber_dn	= $this->person->getSubscriber()->getOrgName();
			$subscriber_db  = $this->person->getSubscriber()->getIdPName();
			$nren		= $this->person->getNREN();

			/* get all NREN-admins */
			$nren_admins = $this->getNRENAdmins($nren);
			$this->tpl->assign('nren_admins', $nren_admins);
			$this->tpl->assign('nren', $nren);

			/* Get all subscriber-admins */
			$subscriber_admins = $this->getSubscriberAdmins($subscriber_db, SUBSCRIBER_ADMIN);
			print_r($subscriber_admins);
			$this->tpl->assign('subscriber', $subscriber_dn);
			$this->tpl->assign('subscriber_admins', $subscriber_admins);

			/* get a list of all subadmins */
			$subscriber_sub_admins = $this->getSubscriberAdmins($subscriber_db, SUBSCRIBER_SUB_ADMIN);
			$this->tpl->assign('subscriber_sub_admins', $subscriber_sub_admins);

		} else if ($this->person->isSubscriberSubAdmin()) { /* subscriber-sub-admin display */
			$subscriber_dn		= $this->person->getSubscriber()->getOrgName();
			$subscriber_db		= $this->person->getSubscriber()->getIdPName();
			$subscriber_admins	= $this->getSubscriberAdmins($subscriber_db, SUBSCRIBER_ADMIN);
			$subscriber_sub_admins	= $this->getSubscriberAdmins($subscriber_db, SUBSCRIBER_SUB_ADMIN);

			/* remove the administrator herself from the list */
			$subscriber_sub_admins = array_diff($subscriber_sub_admins, array($this->person->getEPPN()));
			$this->tpl->assign('subscriber_sub_admins', $subscriber_sub_admins);
			$this->tpl->assign('subscriber_admins', $subscriber_admins);
			$this->tpl->assign('subscriber', $subscriber_db);
		}

		/* output sanitation */
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

		$query  = "SELECT admin, admin_name, admin_email ";
		$query .= "FROM admins WHERE admin_level='2' AND nren=";
		$query .= "(SELECT nren_id FROM nrens WHERE name = ?)";

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Cannot retrieve (nren)admins from database!<BR /> " .
				"Probably wrong syntax for query, ask an admin to investigate. Server said: " .
				htmlentities($dbse->getMessage()));
			return null;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Query failed. This probably means that the values " .
				"passed to the database are wrong. Server said: " .
				htmlentities($dbqe->getMessage()));
			return null;
		}

		$admins = array();

		if (count($res) > 0) {

			foreach($res as $row) {
				$admins[] =  array('eppn' => $row['admin'],
				                   'name' => $row['admin_name'],
				                   'email' => $row['admin_email']);
			}
		}

		return $admins;
	}

	/**
	 * Get all the admins that belong to a certain subscriber
	 *
	 * @param String The subscriber whose admins are sought
	 * @param String dependant on whether the query is for subscriber
	 *		 admins or subscriber sub-admins. This is either
	 *		 SUBSCRIBER_ADMIN	- 1
	 *		 SUBSCRIBER_SUB_ADMIN	- 0
	 */
	private function getSubscriberAdmins($subscriber, $level)
	{
		$query  = "SELECT admin, admin_name, admin_email ";
		$query .= "FROM admins WHERE admin_level=? AND subscriber=";
		$query .= "(SELECT subscriber_id FROM subscribers WHERE name=?)";

		try {
			$res = MDB2Wrapper::execute($query,
						    array('text','text'),
						    array($level, $subscriber));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Cannot retrieve (subscriber) admins from database!<BR /> " .
				"Probably wrong syntax for query, ask an admin to investigate. Server said: " .
				htmlentities($dbse->getMessage()));
			return null;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Query failed. This probably means that the values passed to the "
								. "database are wrong. Server said: " .
								htmlentities($dbqe->getMessage()));
			return null;
		}

		$subscribers = array();

		if (count($res) > 0) {

			foreach($res as $row) {
				$subscribers[] = array('eppn' => $row['admin'],
				                       'name' => $row['name'],
				                       'email' => $row['email']);
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
				"Probably wrong syntax for query, ask an admin to investigate." .
				"Server said: " . htmlentities($dbse->getMessage()));
			return null;
		} catch(DBQueryException $dbqe) {
			Framework::error_output("Query failed. This probably means that the values passed to the "
								. "database are wrong. Server said: " .
								htmlentities($dbqe->getMessage()));
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

	/**
	 * addNRENAdmin() add a new NREN administrator to the admin-table.
	 *
	 * @param String The unique name of the new admin (typically ePPN).
	 * @param String The name of the NREN for the new administrator.
	 */
	private function addNRENAdmin($admin) {
		if (!isset($admin)) {
			Framework::error_output("Need to have the name of the new admin in order to add a new NREN-admin!");
			return;
		}
		try {
			$res = MDB2Wrapper::execute("SELECT nren_id FROM nrens WHERE name=?",
						    array('text'),
						    array($this->person->getNREN()));
			if (count($res)!=1) {
				Framepwork::error_output("Could not find a unique NREN based on name ($nren). Cannot contine.");
				return;
			}
			$nrenID=$res[0]['nren_id'];
			/* See if ADMIN is unique within NREN_umbrella */
			$res = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=? AND nren=?",
						    array('text', 'text'),
						    array($admin, $nrenID));
			if (count($res) != 0) {
				$msg = "Admin $admin already present as admin in table.\n<ul>";
				foreach ($res as $key => $val) {
					$msg .= "<li>" . htmlentities($val['admin']) . " in NREN: " .
					        htmlentities($val['nren']) . " for subscriber " .
					        htmlentities($val['subscriber']) . "</li>\n";
				}
				$msg .= "</ul>\n";
				Framework::error_output($msg);
				return;
			}

			MDB2Wrapper::update("INSERT INTO admins (admin, admin_level, last_mode, nren) VALUES(?,?,?,?)",
					    array('text', 'text', 'text', 'Integer'),
					    array($admin, '2', '0', $nrenID));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem with statement, probably server-issues. Server said " .
			                        htmlentities($dbse->getMessage()));
			return;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem with query, probably issues with supplied data. Server said " .
			                        htmlentities($dbqe->getMessage()));
			return;
		}
	} /* end addNRENAdmin() */

	/**
	 * addSubscriberAdmin()	Add a new subscriber admin to the table
	 *
	 * This function will take the $admin and add it as a new
	 * subscriber-admin. Given that the user has the admin-entitlement set.
	 *
	 * @param String admin	The ePPN of the admin to add
	 * @param String level	Subscriber-admin level (either subscribera-admin
	 *			or sub-admin).
	 * @param String subscriber The name of the subscriber as exported by
	 *			the IdP. IOW, this is *not* the subscriber-dn,
	 *			but the db_name.
	 *
	 * @return void
	 */
	private function addSubscriberAdmin($admin, $level, $subscriber)
	{
		/* FIXME: Change signature to boolean, indicating the result of adding a
		 * new subscriber-admin
		 */

		if (!isset($admin)) {
			Framework::error_output("Need the name of the new admin.");
			return;
		}
		if (!isset($subscriber)) {
			Framework::error_output("Need the subscriber-name in order to add a new subscriber admin.");
		}
		if (!isset($level)) {
			Framework::error_output("Need the access-level for the new Admin.");
			return;
		}

		/* Assert level */
		if (!($level == SUBSCRIBER_ADMIN || $level == SUBSCRIBER_SUB_ADMIN)) {
			Framework::error_output("Cannot add administrator with mangled admin-level. Got " .
			                        htmlentities($level) . ", which is not a subscriber admin code.");
			return;
		}

		/* get nren and subscriber id */
		$query = "SELECT * FROM nrens n LEFT JOIN subscribers s ON s.nren_id = n.nren_id WHERE n.name=? AND s.name=?";
		try {
			$res = MDB2Wrapper::execute($query, array('text', 'text'), array($this->person->getNREN(), $subscriber));
		} catch (DBStatementException $dbse) {
			$msg =  "Serverside issues. Cannot find IDs for NREN and subscriber in database. ";
			$msg .= "Server said: " . htmlentities($dbse->getMessage());
			Framework::error_output($msg);
			return;
		} catch (DBQueryException $dbqe) {
			$msg = "Cannot find IDs for NREN and subscriber in database, probably problems with supplied data. ";
			$msg .= "Server said: " . htmlentities($dbqe->getMessage());
			Framework::error_output($msg);
			return;
		}

		if (count($res) != 1) {
			$msg  = "Could not find unique subscriber/nren combination for subscriber ";
			$msg .= htmlentities($subscriber);
			$msg .= " and NREN ". htmlentities($this->person->getNREN()) . ". Cannot continue.";
			Framework::error_output($msg);
			return;
		}
		$nrenID		= $res[0]['nren_id'];
		$subscriberID	= $res[0]['subscriber_id'];

		/* make sure that the admin is not already present in the database */
		try {
			$res = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=? AND nren=?",
						    array('text', 'text'),
						    array($admin, $nrenID));
		} catch (DBStatementException $dbse) {
			$msg  = "Serverside issues. Cannot find admin in database. ";
			$msg .= "Server said: " . htmlentities($dbse->getMessage());
			Framework::error_output($msg);
			return;
		} catch (DBQueryException $dbqe) {
			$msg  = "Cannot find admin in database, probably problems with supplied data. ";
			$msg .= "Server said: " . htmlentities($dbqe->getMessage());
			Framework::error_output($msg);
			return;
		}
		if (count($res) != 0) {
			Framework::error_output("Cannot add subscriber(sub) admin as an administrator with " .
			                        "that name is already present in the database. Note that an " .
			                        "administrator may only exist once, with exactly one admin-level!");
			return;
		}

		/* Insert admin */
		try {
			$query	= "INSERT INTO admins (admin, admin_level, last_mode, subscriber, nren) VALUES (?, ?, ?, ?, ?)";
			$params	= array('text', 'text', 'text', 'text', 'text');
			$data	= array($admin, $level, '0', $subscriberID, $nrenID);
			MDB2Wrapper::update($query, $params, $data);

		} catch (DBStatementException $dbse) {
			$msg  = "Cannot add Admin to database, probably serverside problems.<br />";
			$msg .= "Server said " . htmlentities($dbse->getMessage());
			Framework::error_output($msg);
			return;
		} catch (DBQueryException $dbqe) {
			$msg  = "Cannot add Admin to database, probably problems with supplied data. <br />";
			$msg .= "Server said: " . htmlentities($dbqe->getMessage());
			Framework::error_output($msg);
			return;
		}
	}

	/**
	 * downgradeNRENAdmin() Downgrade a NREN admin to the status of a subscriber admin
	 *
	 * @param $admin	The admin that should be downgraded to subscriber level
	 * @param $subscriber	The subscriber of which the is to become
	 *			admin. This string must be the db-name, i.e. the
	 *			unique identifier sent from the IdP.
	 *
	 * @return void
	 */
	private function downgradeNRENAdmin($admin, $subscriber)
	{
		$nren = $this->person->getNREN();
		if (is_null($subscriber) || $subscriber=="") {
			$msg  = "Tried to downgrade NREN admin " . htmlentities($admin) . " from NREN " .
			        htmlentities($nren) . " to subscriber admin, ";
			$msg .= "but admin's subscriber affiliaton is not set. Cannot continue.";
			Logger::log_event(LOG_NOTICE,$msg);
			Framework::error_output($msg);
		}

		$sid_query = "SELECT subscriber_id AS sid FROM subscribers s LEFT JOIN nrens n on n.nren_id=s.nren_id WHERE n.name=? AND s.name=?";
		try {
			$res = MDB2Wrapper::execute($sid_query,
						    array('text','text'),
						    array($nren, $subscriber));
		} catch (DBQueryException $dbqe) {
			$msg  = "Problem getting the ID of your subscriber, probably serverside issues. ";
			$msg .= " Server said: " . htmlentities($dbqe->getMessage());
			Framework::error_output($msg);
			Logger::log_event(LOG_NOTICE, "ADMIN: Did not get subscriber_id for admin $admin, nren $nren, " .
							 "subscriber $subscriber. Error is " . $dbqe->getMessage());
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem getting the ID of your subscriber, server said: " .
									htmlentities($dbse->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Did not get subscriber_id for admin $admin, nren $nren, " .
					  "subscriber $subscriber. Error is " . $dbse->getMessage());
			return;
		}

		if (count($res) == 1) {
			$sid=$res[0]['sid'];
		} else {
			Framework::error_ouput("Did not find your subscriber ID!");
			/* Log the (hopefully) rare inconsistency case */
			if (count($res) > 1) {
				Logger::log_event(LOG_WARNING, "ADMIN: Database inconsistency when looking for " .
								"the subscriber-ID linked to subscriber $subscriber and NREN $nren");
			}

			return;
		}

		$query="UPDATE admins SET admin_level='1', subscriber=? WHERE admin=?";

		try {
			$res2 = MDB2Wrapper::update($query,
										array('text','text'),
										array($sid,$admin));
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem updating your admin status. Server said: " . htmlentities($dbqe->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not update admin status of admin $admin to subscriber admin " .
							" of subscriber $subscriber");
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem updating your admin status. Server said: " . $dbse->getMessage());
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not update admin status of admin $admin to subscriber admin " .
							" of subscriber $subscriber");
			return;
		}

		Logger::log_event(LOG_NOTICE, "Admin: NREN admin $admin downgraded his/her status to subscriber admin of " .
						"subscriber $subscriber");
		Framework::message_output("Downgraded you to subscriber admin of subscriber $subscriber");
	}

	/*
	 * "Downgrade" a subscriber admin to the level of a subscriber-sub-admin
	 *
	 * @param $admin The eduPersonPN of the subscriber that is downgrader
	 * @param $subscriber The subscriber within which that happens
	 */
	private function downgradeSubscriberAdmin($admin, $subscriber)
	{
		$update = "UPDATE admins SET admin_level='0' WHERE admin=? ";
		$update .= "AND subscriber=(SELECT subscriber_id FROM subscribers WHERE name=?)";

		try {
			MDB2Wrapper::update($update,
								array('text', 'text'),
								array($admin, $subscriber));
		} catch (DBStatementException $dbse) {
			Framework::error_output("ADMIN: Could not downgrade admin " . htmlentities($admin) .
			                        "! Seems like a problem " .
									"with the configuration of Confusa! Server said: " .
									htmlentities($dbse->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not downgrade subscriber-admin $admin of subscriber " .
							"$subscriber to a subscriber-sub-admin. Something seems to " .
							"be wrong with the statement: " . $dbse->getMessage());
			return;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("ADMIN: Could not downgrade admin " . htmlentities($admin) .
			                        "! Seems like a problem " .
									"with the supplied data! Server said: " .
									htmlentities($dbqe->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not downgrade subscriber-admin $admin of subscriber " .
							"$subscriber to a subscriber-sub-admin. Error with the " .
							"supplied data: " . $dbqe->getMessage());
			return;
		}

		Logger::log_event(LOG_NOTICE, "ADMIN: Downgraded admin $admin from subscriber-admin to subscriber-" .
						"sub-admin in subscriber $subscriber.");
		Framework::success_output("Downgraded " . htmlentities($admin) .
		                          " from subscriber admin to subscriber-sub-admin");
	}

	/**
	 * ugradeSubscriberAdmin() Upgrade an admin from subscriber to NREN
	 *
	 * @param String admin the ePPN of the admin to upgrade
	 *
	 * @return void
	 */
	private function upgradeSubscriberAdmin($admin)
	{
		$snren_id = "SELECT nren_id FROM nrens WHERE name=?";
		$nren = $this->person->getNREN();
		try {
			$res=MDB2Wrapper::execute($snren_id,
						  array('text'),
						  array($nren));
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem determining the ID of your NREN! Server said " .
									htmlentities($dbqe->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Problem getting NREN-ID for NREN $nren " .
								$dbqe->getMessage());
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem determining the ID of your NREN! Server said " .
									htmlentities($dbse->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Problem getting NREN-ID for NREN $nren " .
								$dbse->getMessage());
			return;
		}

		if (count($res) == 1) {
			$nren_id=$res[0]['nren_id'];
		} else {
			Framework::error_output("Could not retrieve your NREN in the DB!");

			if (count($res) > 1) {
				Logger::log_event(LOG_WARNING, "ADMIN: Database inconsistency when looking for " .
								"the nren-ID linked to nren $nren");
			}

			return;
		}

		$update="UPDATE admins SET admin_level='2',nren=? WHERE admin=?";

		try {
			$res2 = MDB2Wrapper::update($update,
										array('text','text'),
										array($nren_id, $admin));
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_NOTICE, "ADMIN: Problem when trying to upgrade subscriber admin " .
							"$admin to NREN-admin in NREN $nren: " . $dbse->getMessage());
			Framework::error_output("Problem when upgrading the admin. Server said: " .
			                        htmlentities($dbse->getMessage()));
			return;
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_NOTICE, "ADMIN: Problem when trying to upgrade subscriber admin " .
							"$admin to NREN-admin in NREN $nren: " . $dbqe->getMessage());
			Framework::error_output("Problem when upgrading the admin. Server said: " .
			                        htmlentities($dbqe->getMessage()));
			return;
		}

		Logger::log_event(LOG_NOTICE, "ADMIN: Subscriber admin $admin upgraded to NREN level (NREN $nren)");
		Framework::success_output("Upgraded subscriber-admin " . htmlentities($admin) . " to NREN level " .
		                          htmlentities($nren));
	}

	/*
	 * "Upgrade" a subscriber-sub-admin to a subscriber admin
	 *
	 * @param $admin The ePPN of the admin
	 * @param $subscriber The name of the subscriber within which everything happens
	 */
	private function upgradeSubscriberSubAdmin($admin, $subscriber)
	{
		$update="UPDATE admins SET admin_level='1' WHERE admin=? and subscriber=";
		$update .= "(SELECT subscriber_id FROM subscribers WHERE name=?)";

		try {
			MDB2Wrapper::update($update,
								array('text','text'),
								array($admin,$subscriber));
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_NOTICE, "ADMIN: Problem when trying to upgrade subscriber-sub-admin " .
							"$admin in subscriber $subscriber. Error with the statement: " .
							$dbse->getMessage());
			Framework::error_output("Problem when upgrading sub-admin " . htmlentities($admin) .
			                        " Probably an error with the configuration! Server said: " .
			                        htmlentities($dbse->getMessage()));
			return;
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_NOTICE, "ADMIN: Problem when trying to upgrade subscriber-sub-admin " .
							"$admin in subscriber $subscriber. Error with supplied data: " .
							$dbqe->getMessage());
			Framework::error_output("Problem when upgrading sub_admin " . htmlentities($admin) .
			                        " Probably a problem with the supplied data! Server said: " .
			                        htmlentities($dbqe->getMessage()));
			return;
		}

		Logger::log_event(LOG_NOTICE, "ADMIN: Upgraded subscriber-sub-admin $admin to a subscriber-admin " .
						"within subscriber $subscriber");
		Framework::success_output("Upgraded subscriber-sub-admin " . htmlentities($admin) .
		                          " to a subscriber-admin");
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
		/* does the current user have the rights? */
		try {
			$query = "SELECT a.* FROM admins a LEFT JOIN nrens n on n.nren_id = a.nren";
			$query .= " WHERE (a.admin=? OR a.admin=?) AND n.name=?";
			$res = MDB2Wrapper::execute($query,
						    array('text', 'text', 'text'),
						    array($admin, $this->person->getEPPN(), $this->person->getNREN()));
			switch (count($res)) {
			case 0:
				Framework::error_output("Did not find neither the admin to delete or the current admin in the database. Cannot continue.");
				return;
			case 1:
				if ($res[0]['admin'] != $admin) {
					Framework::error_output("Cannot find the admin to delete in the admins-table. Cannot continue.");
					return;
				}
				break;
			case 2:
				$id = 0;
				if ($res[1]['admin'] == $admin) {
					$id = 1;
				}
				$nrenID		= $res[$id]['nren'];
				$subscriberID	= $res[$id]['subscriber'];
				break;
			default:
				Framework::error_output("Too many hits in the database. Cannot decide where to go from here.");
				return;
			}

		} catch (DBStatementException $dbse) {
			$msg = "Cannot find id-values in the database due to server problems. Server said: " .
			        htmlentities($dbse->getMessage());
			Framework::error_output($msg);
			return;
		} catch (DBQueryException $dbqe) {
			$msg = "Cannot find id-values due to data inconsistency. Server said: " .
			       htmlentities($dbqe->getMessage());
			Framework::error_output($msg);
			return;
		}

		/* Find the admin-level of both admins and make sure that the
		 * enforcer (the admin performing the deletion) has the rights
		 * to do so. */
		if ($res[0]['admin'] == $admin) {
			$targetLevel	= (int)$res[0]['admin_level'];
			$enforcerLevel	= (int)$res[1]['admin_level'];
		} else {
			$targetLevel	= (int)$res[1]['admin_level'];
			$enforcerLevel	= (int)$res[0]['admin_level'];
		}

		if ($enforcerLevel < $targetLevel) {
			Framework::error_output("Cannot delete admin with higher admin-level.");
			return;
		}

		if ($targetLevel == NREN_ADMIN) {
			$query	= "DELETE FROM admins WHERE admin=? AND nren=?";
			$params	= array('text', 'text');
			$data	= array($admin, $nrenID);
		} else {
			$query	= "DELETE FROM admins WHERE admin=? AND nren=? AND subscriber=?";
			$params	= array('text', 'text', 'text');
			$data	= array($admin, $nrenID, $subscriberID);
		}


		try {
			MDB2Wrapper::update($query, $params, $data);
			Logger::log_event(LOG_INFO, "Successfully deleted admin $admin with level $targetLevel");
		} catch(DBStatementException $dbse) {
			Framework::error_output("Could not delete the admin because the statement was bad " .
						"Please contact an administrator. Server said " .
						htmlentities($dbse->getMessage()));
			Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ . ": Problem occured when trying to delete " .
					  "admin $admin with level $level: " . $dbse->getMessage());
		} catch(DBQueryException $dbqe) {
			Framework::error_output("Could not delete the admin because of problems with the " .
						"received data. Server said " .
						htmlentities($dbqe->getMessage()));
			Logger::log_event(LOG_INFO, __FILE__ . ":" . __LINE__ . ": Problem occured when tyring to delete " .
					  "admin $admin with level $level: " . $dbqe->getMessage());
		}

		Framework::success_output("Deleted admin " . htmlentities($admin));
	}
}

$fw = new Framework(new CP_Admin());
$fw->start();


?>

