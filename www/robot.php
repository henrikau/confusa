<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'mdb2_wrapper.php';
require_once 'cert_lib.php';

class CP_Robot_Interface extends Content_Page
{
	function __construct()
	{
		parent::__construct("Robot Interface", true);
		Framework::sensitive_action();
	}
	function pre_process($person)
	{
		parent::pre_process($person);
		if (isset($_POST['robot_action'])) {
			$action = Input::sanitize($_POST['robot_action']);
			switch($action) {
			case 'paste_new':
				if (isset($_POST['cert']) && $_POST['cert'] != "") {
					$this->insertCertificate($_POST['cert']);
				}
				break;
			case 'upload_new':
				$this->handleFileCertificate();
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
		$query .= " FROM robot_certs rc, admins a,  subscribers s where s.subscriber_id=rc.subscriber_id ";
		$query .= "AND rc.uploaded_by=a.admin_id AND s.name=?";
		try {
			$res = MDB2Wrapper::execute($query, array('text'), array($this->person->getSubscriberOrgName()));
		} catch (Exception $e) {
			/* fixme */
			Framework::error_output("Errors getting robot-certificates from DB.<br />" . $e->getMessage());
		}
		return $res;
	}

	private function handleFileCertificate()
	{
		Framework::message_output("Adding new certificate! (file)");
	}

	/**
	 * insertNewCertificate() insert the new certificate into the robot hold
	 *
	 * Take a string holding the certificate and insert it into the keyhold
	 * given that the string is actually holding a valid certificate.
	 *
	 * @param String base64 encoded PEM formatted X.509 certificate
	 * @return boolean indicating the success of the opreation (true means inserted OK)
	 */
	private function insertCertificate($cert)
	{
		/* validate certificate */
		/* FIXME */

		/* is the certificate already in the robot_certs */
		$fingerprint = openssl_x509_fingerprint($cert);
		try {
			$query  = "SELECT subscriber_id, uploaded_by, uploaded_date, valid_until, fingerprint ";
			$query .= "FROM robot_certs WHERE fingerprint = ?";
			$res = MDB2Wrapper::execute($query, array('text'), array($fingerprint));
			if (count($res) > 0) {
				Framework::error_output("Certificate already present in Database. Cannot upload.");
				return false;
			}
		} catch (Exception $e) {
			/* FIXME, add better exception mask & handling */
		}
		/* Get subscriber and nren id */
		try {
			$query = "SELECT * FROM subscribers s LEFT JOIN nrens n ON n.nren_id = s.nren_id WHERE s.name=? AND n.name=?";
			$res = MDB2Wrapper::execute($query, array('text', 'text'), array($this->person->getSubscriberOrgName(), $this->person->getNREN()));
			switch(count($res)) {
			case 0:
				Framework::error_output("No hits - subscriber not in database! The Subscriber must be added by an NREN-admin. Something is seriously wrong");
				/* fixme: add logging and proper
				 * error-message. DB inconsistency */
				return false;
			case 1:
				$nren_id = $res[0]['nren_id'];
				$subscriber_id = $res[0]['subscriber_id'];
				break;
			default:
				/* FIXME: DB-inconsistency */
				return false;
			}
		} catch (Exception $e) {
			/* Fixme, add proper exception handling */
			return false;
		}
		/* get admin_id */
		$res = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=?",
					    array('text'), array($this->person->getEPPN()));
		Framework::message_output("No errors found wile uploading certificate to keystore");
		return true;
	}
}

$fw = new Framework(new CP_Robot_Interface());
$fw->start();

?>
