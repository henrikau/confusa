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
	private $org_states;
	private $org_name_cache;
	private $urls;
	function __construct()
	{
		parent::__construct("Admin", true);
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		/* IF user is not subscirber- or nren-admin, we stop here */
		if (!($this->person->is_subscriber_admin() || $this->person->is_nren_admin()))
			return false;

		if (isset($_POST['operation'])) {
			switch(htmlentities($_POST['operation'])) {
			case 'delete':
				$privilege = Input::sanitize($_POST['privilege']);
				$admin = Input::sanitize($_POST['admin']);
				$level = $this->getLevelForPrivilege($privilege);
				$this->deleteAdmin($admin, $level);
				break;
			case 'add':
				$privilege = Input::sanitize($_POST['privilege']);
				$admin = Input::sanitize($_POST['ePPN']);
				$nren = Input::sanitize($_POST['nren']);
				$subscriber = Input::sanitize($_POST['subscriber']);
				$level = $this->getLevelForPrivilege($privilege);

				$this->addAdmin($admin, $level, $subscriber, $nren);
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
		/* IF user is not subscirber- or nren-admin, we stop here */
		if (!($this->person->is_subscriber_admin() || $this->person->is_nren_admin())) {
			echo "<H3>Not Authorized for this action</H3>\n";
			Logger::log_event(LOG_NOTICE, "User " . $this->person->get_valid_cn() . " was rejected at the admin-interface");
			$this->tpl->assign('reason', 'You do not have sufficient rights to view this page');
			$this->tpl->assign('content', 'restricted_access.tpl');
			return false;
		}

		if ($this->person->is_nren_admin()) {
			$admins=$this->getNRENAdmins($this->person->get_nren());
			$this->tpl->assign('nren_admins', $admins);
			$this->tpl->assign('nren', $this->person->get_nren());
		}

		$this->tpl->assign('link_urls', $this->urls);
		$this->tpl->assign('content', $this->tpl->fetch('admin.tpl'));
	}


	/**
	 * get all the NREN admins that belong to a certain NREN
	 */
	private function getNRENAdmins($nren)
	{
		$query = "SELECT admin FROM admins WHERE admin_level='2' AND nren=";
		$query .= "(SELECT nren_id FROM nrens WHERE name = ?)";

		$res = MDB2Wrapper::execute($query,
									array('text'),
									array($nren));

		if (count($res) > 0) {

			$admins = array();

			foreach($res as $row) {
				$admins[] = $row['admin'];
			}
		}

		return $admins;
	}

	private function getLevelForPrivilege($privilege)
	{

		switch($privilege) {
		case 'nren':
			return 2;
			break;
		case 'subscriber':
			return 1;
			break;
		case 'subsubscriber':
			return 0;
			break;
		default:
			throw new ConfusaGenException("Unknown privilege specified!");
			break;
		}
	}

	private function addAdmin($admin, $level, $subscriber=NULL, $nren=NULL)
	{
		$nren_id = NULL;
		$subscriber_id = NULL;

		if ($nren === NULL && $subscriber === NULL) {
			throw new ConfusaGenException("Not both NREN and subscriber can be NULL!");
		}

		if ($nren != NULL) {
			$query = "SELECT nren_id FROM nrens WHERE name=?";
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));

			if (count($res) === 1) {
				$nren_id = $res[0]['nren_id'];
			} else {
				throw new DBQueryException("Could not retrieve exactly one NREN" .
						" with name $nren WHEN inserting a new admin! Got " . count($res) . "results!");
			}
		} else if ($subscriber != NULL) {
			$query = "SELECT subscriber_id FROM subscribers WHERE name=?";
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));

			if (count($res) === 1) {
				$subscriber_id = $res[0]['subscriber_id'];
			} else {
				throw new DBQueryException("Could not retrieve exactly one subscriber" .
						" with name $subscriber WHEN inserting a new admin! Got " . count($res) . "results!");
			}
		}

		$query = "INSERT INTO admins(admin, admin_level, nren, subscriber) ";
		$query .= "VALUES(?,?,?,?)";

		MDB2Wrapper::execute($query,
							 array('text','text','text','text'),
							 array($admin,$level,$nren_id,$subscriber_id));
	}
	/*
	 * @param $level the privilege level of the admin that is to be deleted
	 *			(this is for added security)
	 * @param $admin The eduPersonPrincipalName (or similar identifier) for the
	 *			admin that is to be deleted
	 */
	private function deleteAdmin($admin, $level)
	{
		$query = "DELETE FROM admins WHERE admin=? and admin_level=?";
		MDB2Wrapper::update($query,
							array('text','text'),
							array($admin, $level));
	}
}

$fw = new Framework(new CP_Admin());
$fw->start();


?>

