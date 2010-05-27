<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
include_once 'Framework.php';
include_once 'MDB2Wrapper.php';
include_once 'db_query.php';
include_once 'Logger.php';
include_once 'Input.php';


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
		parent::__construct("Admin", true, "admin");
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
					$admin = Input::sanitizeEPPN($_POST['nren_admin']);
					$this->deleteAdmin($admin, NREN_ADMIN);
					break;
				case 'downgrade_self':
					if ($this->person->testEntitlementAttribute(Config::get_config('entitlement_admin'))) {
						$this->downgradeNRENAdmin($this->person->getEPPN(),
									  $this->person->getSubscriber()->getDBID());
					}
					break;
				case 'upgrade_subs_admin':
					$admin = Input::sanitizeEPPN($_POST['subs_admin']);
					$this->upgradeSubscriberAdmin($admin);
					break;
				case 'add_nren_admin':
					$admin = Input::sanitizeEPPN($_POST['nren_admin']);
					$idp = Input::sanitizeIdPName($_POST['idp']);

					if ($idp === '-') {
						$this->addNRENAdmin($admin, NULL);
					} else {
						$this->addNRENAdmin($admin, $idp);
					}

					break;
				case 'delete_subs_admin':
					$admin = Input::sanitizeEPPN($_POST['subs_admin']);
					$this->deleteAdmin($admin,SUBSCRIBER_ADMIN);
					break;
				case 'add_subs_admin':
					$admin = Input::sanitizeEPPN($_POST['subs_admin']);
					$subscriberID = Input::sanitizeID($_POST['subscriberID']);
					$this->addSubscriberAdmin($admin, SUBSCRIBER_ADMIN, $subscriberID);
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
					$admin = Input::sanitizeEPPN($_POST['subs_admin']);
					$this->deleteAdmin($admin, SUBSCRIBER_ADMIN);
					break;
				case 'add_subs_admin':
					$admin = Input::sanitizeEPPN($_POST['subs_admin']);
					$this->addSubscriberAdmin($admin,SUBSCRIBER_ADMIN,$this->person->getSubscriber()->getDBID());
					break;
				case 'downgrade_subs_admin':
					$admin = Input::sanitizeEPPN($_POST['subs_admin']);
					$this->downgradeSubscriberAdmin($admin, $this->person->getSubscriber()->getDBID());
					break;
				case 'upgrade_subs_sub_admin':
					$admin = Input::sanitizeEPPN($_POST['subs_sub_admin']);
					$this->upgradeSubscriberSubAdmin($admin, $this->person->getSubscriber()->getDBID());
					break;
				case 'delete_subs_sub_admin':
					$admin = Input::sanitizeEPPN($_POST['subs_sub_admin']);
					$this->deleteAdmin($admin,SUBSCRIBER_SUB_ADMIN);
					break;
				case 'add_subs_sub_admin':
					$admin = Input::sanitizeEPPN($_POST['subs_sub_admin']);
					$this->addSubscriberAdmin($admin,SUBSCRIBER_SUB_ADMIN,$this->person->getSubscriber()->getDBID());
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
			$this->processNRENAdmin();

		} else if ($this->person->isSubscriberAdmin()) { /* subscriber admin display */
			$this->processSubscriberAdmin();

		} else if ($this->person->isSubscriberSubAdmin()) { /* subscriber-sub-admin display */
			$this->processSubscriberSubAdmin();
		}

		/* output sanitation */
		$this->tpl->assign('self', $this->person->getEPPN());
		$this->tpl->assign('content', $this->tpl->fetch('admin.tpl'));
	}


	/**
	 * Get all the NREN admins that belong to a certain NREN
	 *
	 * @param $nren NREN The NREN for which the respective admins are queried
	 */
	private function getNRENAdmins($nren)
	{

		$query  = "SELECT admin, admin_name, admin_email, idp_url ";
		$query .= "FROM admins WHERE admin_level='2' AND nren=?";

		$nrenID = $nren->getID();

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nrenID));
		} catch (DBStatementException $dbse) {
			Framework::error_output($this->translateTag('l10n_msg_getadms1', 'admin') .
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
				                   'email' => $row['admin_email'],
				                   'idp_url' => $row['idp_url']);
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
	 * @param exclude_self boolean whether to exclude the logged in person in the
	 *                             result set
	 */
	private function getSubscriberAdmins($subscriberID, $level, $exclude_self = false)
	{
		$query  = "SELECT admin, admin_name, admin_email ";
		$query .= "FROM admins WHERE admin_level=? AND subscriber=?";

		try {
			$res = MDB2Wrapper::execute($query,
						    array('text','text'),
						    array($level, $subscriberID));
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
				if ($exclude_self &&
				    $row['admin'] == $this->person->getEPPN()) {
						continue;
				}

				$subscribers[] = array('eppn' => $row['admin'],
				                       'name' => $row['admin_name'],
				                       'email' => $row['admin_email']);
			}
		}

		return $subscribers;
	}

	/**
	 * addNRENAdmin() add a new NREN administrator to the admin-table.
	 *
	 * @param $admin string The unique name of the new admin (typically ePPN).
	 * @param $idp string The IdP that the new admin should be associatied with
	 */
	private function addNRENAdmin($admin, $idp) {
		if (!isset($admin)) {
			Framework::error_output("Need to have the name of the new admin in order to add a new NREN-admin!");
			return;
		}
		try {
			$nrenID = $this->person->getNREN()->getID();
			/* See if ADMIN is unique within NREN_umbrella */
			$res = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=? AND nren=?",
						    array('text', 'text'),
						    array($admin, $nrenID));
			if (count($res) != 0) {
				$msg = "Admin $admin already present as admin in table.\n<ul>";
				foreach ($res as $key => $val) {
					$msg .= "<li>" . htmlentities($val['admin']) . " in NREN: " .
					        htmlentities($this->person->getNREN()->getName()) . " for subscriber " .
					        htmlentities($this->person->getSubscriber()->getIdPName()) . "</li>\n";
				}
				$msg .= "</ul>\n";
				Framework::error_output($msg);
				return;
			}

			MDB2Wrapper::update("INSERT INTO admins (admin, admin_level, last_mode, nren, idp_url) VALUES(?,?,?,?,?)",
					    array('text', 'text', 'text', 'Integer', 'text'),
					    array($admin, '2', '0', $nrenID, $idp));
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
	 * Render the page for a NREN-admin
	 */
	private function processNRENAdmin()
	{
			$admins=$this->getNRENAdmins($this->person->getNREN());

			try {
				/* Get a list of subscribers (as
				 * Subscriber-objects) */
				$subscribers = $this->person->getNREN()->getSubscriberList();
			} catch (DBQueryException $dbqe) {
				Framework::error_output("Cannot retrieve subscriber from database!<br /> " .
				                        "Probably wrong syntax for query, ask an admin to investigate." .
				                        "Server said: " . htmlentities($dbse->getMessage()));
			} catch (DBStatementException $dbse) {
				Framework::error_output("Query failed. This probably means that the values passed to the "
				                        . "database are wrong. Server said: " .
				                        htmlentities($dbqe->getMessage()));
			}

			$current_subscriber = null;

			/* Are we looking at a particular subscriber? */
			if (isset($_POST['subscriberID'])) {
				$current_subscriber_id = Input::sanitizeID($_POST['subscriberID']);

				foreach($subscribers as $nren_subscriber) {
					if ($nren_subscriber->getDBID() == $current_subscriber_id) {
						$current_subscriber = $nren_subscriber;
						break;
					}
				}
			} else if (! is_null($subscribers)) {
				$current_subscriber = $subscribers[0];
			}

			if (isset($current_subscriber)) {
				$subscriber_admins = $this->getSubscriberAdmins($current_subscriber->getDBID(), SUBSCRIBER_ADMIN);
				$this->tpl->assign('subscriber', $current_subscriber);
				$this->tpl->assign('subscriber_admins', $subscriber_admins);
			}

			/* does the NREN-admin have the admin-entitlement (for downgrading)? */
			if ($this->person->testEntitlementAttribute(Config::get_config('entitlement_admin'))) {
				$this->tpl->assign('has_adm_entl',true);
			} else {
				$this->tpl->assign('has_adm_entl',false);
			}

			$nren = $this->person->getNREN();
			$idpList = $nren->getIdPList();
			/* append an empty entry to the beginning */
			$idpList = array_merge((array)'-', $idpList);
			$this->tpl->assign('idps', $idpList);
			$this->tpl->assign('nren_admins', $admins);
			$this->tpl->assign('nren', $nren);
			$this->tpl->assign('subscribers', $subscribers);
	}

	/**
	 * Render the page for a subscriber admin
	 */
	private function processSubscriberAdmin()
	{
		$subscriber_db	= $this->person->getSubscriber()->getIdPName();
		$subscriber_id  = $this->person->getSubscriber()->getDBID();
		$nren		= $this->person->getNREN();

		/* get all NREN-admins */
		$nren_admins = $this->getNRENAdmins($nren);
		$this->tpl->assign('nren_admins', $nren_admins);
		$this->tpl->assign('nren', $nren);

		/* Get all subscriber-admins */
		$subscriber_admins = $this->getSubscriberAdmins($subscriber_id, SUBSCRIBER_ADMIN);
		$this->tpl->assign('subscriber', $subscriber_db);
		$this->tpl->assign('subscriber_admins', $subscriber_admins);

		/* get a list of all subadmins */
		$subscriber_sub_admins = $this->getSubscriberAdmins($subscriber_id, SUBSCRIBER_SUB_ADMIN);
		$this->tpl->assign('subscriber_sub_admins', $subscriber_sub_admins);
	}

	/**
	 * render the page for a subscriber sub-admin
	 */
	private function processSubscriberSubAdmin()
	{
		$subscriber_id		= $this->person->getSubscriber()->getDBID();
		$subscriber_db		= $this->person->getSubscriber()->getIdPName();
		$subscriber_admins	= $this->getSubscriberAdmins($subscriber_id, SUBSCRIBER_ADMIN);
		$subscriber_sub_admins	= $this->getSubscriberAdmins($subscriber_id, SUBSCRIBER_SUB_ADMIN, true);

		$this->tpl->assign('subscriber_sub_admins', $subscriber_sub_admins);
		$this->tpl->assign('subscriber_admins', $subscriber_admins);
		$this->tpl->assign('subscriber', $subscriber_db);
	}

	/**
	 * addSubscriberAdmin()	Add a new subscriber admin to the table
	 *
	 * This function will take the $admin and add it as a new
	 * subscriber-admin. Given that the user has the admin-entitlement set.
	 *
	 * @param String admin	The unique identifier (e.g. ePPN) of the admin to add
	 * @param String level	Subscriber-admin level (either subscribera-admin
	 *			or sub-admin).
	 * @param subscriberID integer The ID of the subscriber as exported by
	 *			the IdP. IOW, this is *not* the subscriber-dn,
	 *			but the db_name.
	 *
	 * @return void
	 */
	private function addSubscriberAdmin($admin, $level, $subscriberID)
	{
		/* FIXME: Change signature to boolean, indicating the result of adding a
		 * new subscriber-admin
		 */

		if (!isset($admin)) {
			Framework::error_output("Need the name of the new admin.");
			return;
		}
		if (!isset($subscriberID)) {
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

		$nrenID = $this->person->getNREN()->getID();

		/* check if the subscriber really belongs to the current NREN */
		$query = " SELECT * FROM subscribers s WHERE s.nren_id=? and s.subscriber_id=?;";
		try {
			$res = MDB2Wrapper::execute($query,
			                            array('text', 'text'),
										array($nrenID, $subscriberID));
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
			$msg  = "Could not find unique subscriber/nren combination for subscriber with ID ";
			$msg .= htmlentities($subscriberID);
			$msg .= " and NREN ". htmlentities($this->person->getNREN()) . ". Cannot continue.";
			Framework::error_output($msg);
			return;
		}

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
			Framework::error_output($this->translateTag('l10n_msg_admunique', 'admin'));
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
	 * @param  $admin_uid		String  The UID of the admin that should be downgraded.
	 * @param  $subscriber_id	Int	ID of subscriber in the database.
	 * @param  $nren_id		Int	ID of NREN in the database.
	 * @return void
	 * @access private
	 */
	private function downgradeNRENAdmin($admin_uid, $subscriber_id)
	{
		if (empty($subsriber_id)) {
			$msg  = "Tried to downgrade NREN admin " . htmlentities($this->person->getEPPN()) . " from NREN " .
			        htmlentities($this->person->getNREN()->getName()) . " to subscriber admin, ";
			$msg .= "but admin's subscriber affiliaton is not set. Cannot continue.";
			Logger::log_event(LOG_NOTICE,$msg);
			Framework::error_output($msg);
		}

		try {
			$query  = "UPDATE admins SET admin_level='1', subscriber=:subscriber_id ";
			$query .= "WHERE admin=:admin AND nren=:nren_id";
			$data = array();
			$data['subscriber_id']	= $subscriber_id;
			$data['admin']	        = $admin_uid;
			$data['nren_id']	= $this->person->getNREN()->getID();
			$res = MDB2Wrapper::update($query, null, $data);
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem updating your admin status. Server said: " .
						htmlentities($dbqe->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not update admin status of admin $admin_uid to subscriber admin " .
							" of subscriber with ID $subscriber_id");
			return;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem updating your admin status. Server said: " .
						htmlentities($dbse->getMessage()));
			Logger::log_event(LOG_NOTICE,
					  "ADMIN: Could not update admin status of admin $admin_uid to subscriber admin " .
					  " of subscriber with ID $subscriber_id");
			return;
		}

		Logger::log_event(LOG_NOTICE, "Admin: NREN admin $admin_id downgraded his/her status to subscriber admin of " .
						"subscriber with ID $subscriber_id");
		Framework::success_output($this->translateTag('l10n_suc_downgrnren', 'admin') .
		                          htmlentities($subscriber_id));
	}

	/*
	 * "Downgrade" a subscriber admin to the level of a subscriber-sub-admin
	 *
	 * @param $admin The eduPersonPN of the subscriber that is downgrader
	 * @param $subscriberID integer The ID of the subscriber within which that happens
	 */
	private function downgradeSubscriberAdmin($admin, $subscriberID)
	{
		$update = "UPDATE admins SET admin_level='0' WHERE admin=? ";
		$update .= "AND subscriber=?";

		try {
			MDB2Wrapper::update($update,
								array('text', 'text'),
								array($admin, $subscriberID));
		} catch (DBStatementException $dbse) {
			Framework::error_output("ADMIN: Could not downgrade admin " . htmlentities($admin) .
			                        "! Seems like a problem " .
									"with the configuration of Confusa! Server said: " .
									htmlentities($dbse->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not downgrade subscriber-admin $admin of subscriber " .
							"with ID $subscriberID to a subscriber-sub-admin. Something seems to " .
							"be wrong with the statement: " . $dbse->getMessage());
			return;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("ADMIN: Could not downgrade admin " . htmlentities($admin) .
			                        "! Seems like a problem " .
									"with the supplied data! Server said: " .
									htmlentities($dbqe->getMessage()));
			Logger::log_event(LOG_NOTICE, "ADMIN: Could not downgrade subscriber-admin $admin of subscriber " .
							"with ID $subscriberID to a subscriber-sub-admin. Error with the " .
							"supplied data: " . $dbqe->getMessage());
			return;
		}

		Logger::log_event(LOG_NOTICE, "ADMIN: Downgraded admin $admin from subscriber-admin to subscriber-" .
						"sub-admin in subscriber with ID $subscriberID.");
		Framework::success_output($this->translateTag('l10n_suc_downgrsubs1', 'admin') . htmlentities($admin) .
		                          " " . $this->translateTag('l10n_suc_downgrsubs2', 'admin'));
	}

	/**
	 * ugradeSubscriberAdmin() Upgrade an admin from subscriber to NREN
	 *
	 * @param String admin the unique identifier (e.g. eppn) of the admin to upgrade
	 *
	 * @return void
	 */
	private function upgradeSubscriberAdmin($admin)
	{

		$nren_id = $this->person->getNREN()->getID();

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

		Logger::log_event(LOG_NOTICE, "ADMIN: Subscriber admin $admin upgraded to NREN level (NREN-ID $nren_id)");
		Framework::success_output($this->translateTag('l10n_suc_upgrsubs1', 'admin') . " " . htmlentities($admin)
		                          . " " . $this->translateTag('l10n_suc_upgrsubs2', 'admin'));
	}

	/*
	 * "Upgrade" a subscriber-sub-admin to a subscriber admin
	 *
	 * @param $admin The unique identifier (e.g. eppn) of the admin
	 * @param $subscriberID integer The ID of the subscriber within which everything happens
	 */
	private function upgradeSubscriberSubAdmin($admin, $subscriberID)
	{
		$update="UPDATE admins SET admin_level='1' WHERE admin=? and subscriber=?";

		try {
			MDB2Wrapper::update($update,
								array('text','text'),
								array($admin,$subscriberID));
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_NOTICE, "ADMIN: Problem when trying to upgrade subscriber-sub-admin " .
							"$admin in subscriber $subscriberID. Error with the statement: " .
							$dbse->getMessage());
			Framework::error_output("Problem when upgrading sub-admin " . htmlentities($admin) .
			                        " Probably an error with the configuration! Server said: " .
			                        htmlentities($dbse->getMessage()));
			return;
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_NOTICE, "ADMIN: Problem when trying to upgrade subscriber-sub-admin " .
							"$admin in subscriber with ID $subscriberID. Error with supplied data: " .
							$dbqe->getMessage());
			Framework::error_output("Problem when upgrading sub_admin " . htmlentities($admin) .
			                        " Probably a problem with the supplied data! Server said: " .
			                        htmlentities($dbqe->getMessage()));
			return;
		}

		Logger::log_event(LOG_NOTICE, "[sadm] Upgraded subscriber-sub-admin $admin to a subscriber-admin " .
						"within subscriber with ID $subscriberID");
		Framework::success_output($this->translateTag('l10n_suc_upgrsubss1', 'admin') . " "
		                          . htmlentities($admin) . " " .
		                          $this->translateTag('l10n_suc_upgrsubss2', 'admin'));
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

		Framework::success_output($this->translateTag('l10n_suc_deleteadm1', 'admin') . " " .
		                          htmlentities($admin));
	}
}

$fw = new Framework(new CP_Admin());
$fw->start();


?>

