<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'mdb2_wrapper.php';

class CP_Robot_Interface extends Content_Page
{
	function __construct()
	{
		parent::__construct("Robot", true);
		if (isset($_POST['robot_action'])) {
			$action = Input::sanitize($_POST['robot_action']);
			switch($action) {
			case 'add_new':
				Framework::message_output("Adding new certificate!");
				break;
			default:
				Framework::error_output("Unknown robot-action ($action)");
				return false;
			}
			return true;
		}
		return false;
	}


	public function process()
	{
		/* get a list of certificates and assign to template */
		$this->tpl->assign('robotCerts', $this->getRobotCertList());
		/* tet main template */
		$this->tpl->assign('content', $this->tpl->fetch('robot.tpl'));
	}

	/**
	 * getRobotCertList() find the list of certificates for the current subscriber
	 *
	 * @param void
	 * @retun array list of robotic certificates
	 */
	private function getRobotCertList()
	{
		$query = "SELECT uploaded_date, a.admin, valid_until, last_warning_sent, cert, comment ";
		$query .= " FROM robot_cert rc, admins a,  subscribers s where s.subscriber_id=rc.subscriber_id ";
		$query .= "AND rc.uploaded_by=a.admin_id AND s.name=?";
		try {
			$res = MDB2Wrapper::execute($query, array('text'), array($this->person->getSubscriberOrgName()));
		} catch (Exception $e) {
			/* fixme */
			Framework::error_output("Errors getting robot-certificates from DB.<br />" . $e->getMessage());
		}
		echo "<pre>\n";
		print_r($res);
		echo "</pre>\n";
		return $res;
	}
}

$fw = new Framework(new CP_Robot_Interface());
$fw->start();

?>
