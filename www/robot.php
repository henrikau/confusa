<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'mdb2_wrapper.php';
require_once 'cert_lib.php';
require_once 'file_upload.php';
require_once 'certificate.php';

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
			$comment = Input::sanitize($_POST['comment']);
			switch($action) {
			case 'paste_new':
				if (isset($_POST['cert']) && $_POST['cert'] != "") {
					$this->insertCertificate($_POST['cert'], $comment);
				}
				break;
			case 'upload_new':
				$this->handleFileCertificate($comment);
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
		$certs = array();
		foreach ($res as $key => $val) {
			$cert = new Certificate($val['cert']);
			$cert->setMadeAvailable($val['uploaded_date']);
			$cert->setOwner($val['admin']);
			$certs[] = $cert;
		}
		return $certs;
	}

	private function handleFileCertificate($comment)
	{
		if (FileUpload::testError('cert')) {
			$cert = openssl_x509_read(FileUpload::getContent('cert'));
			if (openssl_x509_export($cert, $certDump, true)) {
				return $this->insertCertificate($certDump);
			}
		}
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
	private function insertCertificate($certificate, $comment)
	{
		/* validate certificate */
		try {
			$cert = new Certificate($certificate);
		} catch (KeyNotFoundException $knfe) {
			Framework::error_output($knfe->getMessage());
			return false;
		}

		/* Find valid_until for cert */
		try {
			$query  = "SELECT subscriber_id, uploaded_by, uploaded_date, valid_until, fingerprint ";
			$query .= "FROM robot_certs WHERE fingerprint = ? OR serial=?";
			$res = MDB2Wrapper::execute($query,
						    array('text', 'text'),
						    array($cert->fingerprint(), $cert->serial()));
			if (count($res) > 0) {
				Framework::error_output("Certificate already present in Database. Cannot upload.");
				return false;
			}
		} catch (Exception $e) {
			/* FIXME, add better exception mask & handling */
			Framework::error_output(__FILE__ . ":" . __LINE__ . " FIXME: " . $e->getMessage());
			return false;
		}

		/* Get subscriber,  nren and admin_id */
		try {
			$query  = "SELECT anj.admin AS admin, anj.admin_id AS aid, ";
			$query .= "anj.name AS nren_name, s.name AS subscriber_name, ";
			$query .= "s.subscriber_id, anj.nren_id AS nren_id FROM  ";
			$query .= "(SELECT * FROM ";
			$query .= "admins a LEFT JOIN nrens n ON n.nren_id = a.nren WHERE ";
			$query .= "subscriber IS NOT NULL) anj left join subscribers s ON ";
			$query .= "s.subscriber_id = anj.subscriber ";
			$query .= "WHERE admin=? AND s.name=? AND anj.name=?";
			$params = array('text', 'text', 'text');
			$data = array($this->person->getEPPN(),
				      $this->person->getSubscriberOrgName(),
				      $this->person->getNREN());
			$res = MDB2Wrapper::execute($query, $params, $data);
			switch(count($res)) {
			case 0:
				$msg  = "No hits - subscriber not in database! <br />\n";
				$msg .= "The Subscriber (".$this->person->getSubscriberOrgName().") ";
				$msg .= "must be added by an NREN-admin for nren '" .$this->person->getNREN(). "'. Something is seriously wrong.";

				Framework::error_output($msg);
				/* fixme: add logging and proper
				 * error-message. DB inconsistency */
				return false;
			case 1:
				$admin_id	= $res[0]['aid'];
				$nren_id	= $res[0]['nren_id'];
				$subscriber_id	= $res[0]['subscriber_id'];
				break;
			default:
				/* FIXME: DB-inconsistency */
				return false;
			}
		} catch (Exception $e) {
			echo $e->getMessage();
			/* FIXME, add proper exception handling */
			return false;
		}

		try {
			$update  = "INSERT INTO robot_certs (subscriber_id, uploaded_by, uploaded_date, valid_until, cert, fingerprint, serial)";
			$update .= " VALUES(?, ?, current_timestamp(), ?, ?, ?, ?)";
			$params	= array('text', 'text', 'text', 'text', 'text', 'text');
			$data	= array($subscriber_id, $admin_id, $cert->validTo(), $cert->getCert(), $cert->fingerprint(), $cert->serial());
			MDB2Wrapper::update($update, $params, $data);

		} catch (Exception $e) {
			/* FIXME */
			Framework::error_output("coultn't update robot_certs, server said:<br />\n" . $e->getMessage());
			return false;
		}
		Framework::message_output("No errors found wile uploading certificate to keystore");
		return true;
	}
}

$fw = new Framework(new CP_Robot_Interface());
$fw->start();

?>
