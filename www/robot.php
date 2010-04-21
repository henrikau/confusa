<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'Person.php';
require_once 'MDB2Wrapper.php';
require_once 'cert_lib.php';
require_once 'file_upload.php';
require_once 'certificate.php';
require_once 'CertificateException.php';
/**
 * CP_Robot_Interface
 */
class CP_Robot_Interface extends Content_Page
{
	function __construct()
	{
		parent::__construct("Robot Interface", true, 'robot');
		Framework::sensitive_action();
	}
	function pre_process($person)
	{
		$res = false;
		parent::pre_process($person);
		if (!$this->person->isSubscriberAdmin()) {
			Framework::error_output("You are not authorized to view this page");
			return false;
		}
		$this->person->setMode(ADMIN_MODE);

		/* Set flags to default-values */
		$this->tpl->assign('cert_info', false);

		if (isset($_POST['robot_action'])) {
			$action = Input::sanitize($_POST['robot_action']);
			$comment = Input::sanitize($_POST['comment']);
			switch($action) {
			case 'paste_new':
				if (isset($_POST['cert']) && $_POST['cert'] != "") {
					$cert = Input::sanitizeBase64($_POST['cert']);
					$res = $this->insertCertificate($cert, $comment);
				}
				break;
			case 'upload_new':
				$res = $this->handleFileCertificate($comment);
				break;
			default:
				Framework::error_output("Unknown robot-action (" .
				                        htmlentities($action) . ").");
				$res = false;
			}
		} else if (isset($_GET['robot_action'])) {
			$action = Input::sanitize($_GET['robot_action']);
			$serial = Input::sanitize($_GET['serial']);
			if (!isset($serial) || $serial=="") {
				$res = false;
			}
			switch($action) {
			case 'delete':
				$res = $this->deleteCertificate($serial);
				break;
			case 'info':
				$this->tpl->assign('cert_info', true);
				$this->tpl->assign('cert_info_serial', $serial);
				break;
			case 'download_archive':
				if ($this->person->isAdmin()) {
					if($this->downloadArchive()) {
						exit(0);
					}
				}
				break;
			default:
				Framework::error_output("Unknown action");
				$res = false;
			}
		}
		return $res;
	}


	public function process()
	{
		if (!$this->person->isSubscriberAdmin()) {
			/* not authorized */
			return false;
		}
		/* get menu-flags and assign to the framework */
		$this->tpl->assign('rv_list', false);
		$this->tpl->assign('rv_upload', false);
		$this->tpl->assign('rv_info', false);
		if (isset($_GET['robot_view'])) {
			switch(Input::sanitize($_GET['robot_view'])) {
			case 'list':
				$this->tpl->assign('rv_list', true);
				$this->tpl->assign('robotCerts', $this->getRobotCertList());
				break;
			case 'upload':
				$this->tpl->assign('rv_upload', true);
				break;
			case 'info':
				$this->tpl->assign('rv_info', true);
				$this->tpl->assign('ri_path', Config::get_config('server_url') . "ri.php");
				break;
			default:
				break;
			}
		} else {
			/* We default to listing the certificates */
			$this->tpl->assign('robotCerts', $this->getRobotCertList());
			$this->tpl->assign('rv_list', true);

		}
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
		$query = "SELECT * FROM robot_certs rc LEFT JOIN admins a ON rc.uploaded_by = a.admin_id WHERE subscriber_id=?";
		$params = array('text');
		$data = array($this->person->getSubscriber()->getDBID());
		$res = null;

		try {
			$res = MDB2Wrapper::execute($query, $params, $data);
		} catch (Exception $e) {
			/* fixme */
			Framework::error_output("Errors getting robot-certificates from DB.<br />" .
			                        htmlentities($e->getMessage()));
			return null;
		}
		$certs = array();
		foreach ($res as $key => $val) {
			try {
				$cert = new Certificate($val['cert']);
				$cert->setMadeAvailable($val['uploaded_date']);
				$cert->setOwner($val['admin']);
				$cert->setComment($val['comment']);
				$cert->setLastWarningSent($val['last_warning_sent']);
				$certs[] = $cert;
			} catch (CertificateException $ce) {
				continue;
			}
		}
		return $certs;
	}

	/**
	 * handleFileCertificate() Insert new RI-cert from FILE-upload
	 *
	 * This function is called whenever a certificate is uploaded via the
	 * FILE-interface. Simple validation is performed before passing the
	 * content on to the generic insertCertificate()-function
	 *
	 * It will use the comment provided via Post, so a mixture of FILE and
	 * POST is used here.
	 *
	 * @param String $comment The comment associated with the certificate
	 * @param Bolean $res indication if the certificate was uploaded successfully
	 */
	private function handleFileCertificate($comment)
	{
		$res = false;
		if (FileUpload::testError('cert')) {
			$cert = openssl_x509_read(FileUpload::getContent('cert'));
			if (openssl_x509_export($cert, $certDump, true)) {
				$cert = Input::sanitizeBase64($cert);
				$res= $this->insertCertificate($certDump, $comment);
			}
		}
		return $res;
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
			Framework::error_output(htmlentities($knfe->getMessage()));
			return false;
		} catch (CertificateException $ce) {
			Framework::error_output(htmlentities($ce->getMessage()));
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
				Framework::error_output($this->translateTag('l10n_err_certalrthere', 'robot'));
				return false;
			}
		} catch (Exception $e) {
			/* FIXME, add better exception mask & handling */
			Framework::error_output(__FILE__ . ":" . __LINE__ . " FIXME: " .
			                        htmlentities($e->getMessage()));
			return false;
		}

		/* Get subscriber,  nren and admin_id */
		try {
			$query = "SELECT * FROM admins WHERE admin=? AND subscriber=? AND nren=? ";
			$params = array('text', 'text', 'text');
			$data = array($this->person->getEPPN(),
				      $this->person->getSubscriber()->getDBID(),
				      $this->person->getNREN()->getID());
			$res = MDB2Wrapper::execute($query, $params, $data);

			switch(count($res)) {

			case 0:
				/*
				 * Strange error. User is admin, yet not admin.
				 *
				 * Fixme: better error-reporting here, even
				 * though we cannot do much about it.
				 */
				$error_code = strtoupper(PW::create(8));
				$error_msg  = "[error_code: $error_code]<br /><br />\n";
				$log_msg  = "[$error_code] ";

				$query	= "SELECT * FROM admins WHERE admin=? AND admin_level=? AND subscriber IS NULL";
				$params = array('text', 'text');
				$data	= array($this->person->getEPPN(), SUBSCRIBER_ADMIN);
				$admin_query_res = MDB2Wrapper::execute($query, $params, $data);
				if (count($admin_query_res) != 0) {
					$error_msg .= "The subscriber-admin (". htmlentites($this->person->getEPPN()) .") is not properly connected ";
					$error_msg .= "to any database. This is due to a database inconsistency ";
					$error_msg .= "and is a direct result of someone manually adding the admin to the database ";
					$error_msg .= "without connecting the admin to a subscriber.";

					$log_msg   .= "Subscriber-admin " . $this->person->getEPPN();
					$log_msg   .= " has not set any affilitated subscriber in the database.";
					$log_msg   .= " It should be " . $this->person->getSubscriber()->getOrgName();
					$log_msg   .= ", but is NULL. Please update the database.";
				} else {
					$error_msg .= "For some reason, the subscriber (".htmlentities($this->person->getSubscriber()->getOrgName()).") ";
					$error_msg .= "is not properly configured in the database. ";
					$error_msg .= "The exact reason is unknown. Please contact operational support.";

					$log_msg   .= "Subscriber " . $this->person->getSubscriber()->getOrgName();
					$log_msg   .= " is not properly configured in the database.";

				}
				$error_msg .= "<br /><br />\nThis event has been logged, please contact operational support (provide the error-code) ";
				$error_msg .= "to resolve this issue.";
				Framework::error_output($error_msg);
				Logger::log_event(LOG_ALERT, $log_msg);

				return false;
			case 1:
				$admin_id	= $res[0]['admin_id'];
				$nren_id	= $res[0]['nren'];
				$subscriber_id	= $res[0]['subscriber'];
				break;
			default:
				/* FIXME: DB-inconsistency */
				$error_code = strtoupper(PW::create(8));
				$error_msg  = "[error_code: $error_code] multiple instances of admin (";
				$error_msg .= $this->person->getEPPN() . ") found in the database.";

				$log_msg    = "[$error_code] multiple hits (".count($res).")on ";
				$log_msg   .= $this->person->getEPPN() . " in admins-table.";

				Framework::error_output($error_msg);
				Logger::log_event(LOG_ALERT, $log_msg);
				return false;
			}
		} catch (Exception $e) {
			Framework::error_output(hmtlentities($e->getMessage()));
			/* FIXME, add proper exception handling */
			return false;
		}

		try {
			if (!isset($comment) || $comment == "") {
				$comment = " ";
			}
			$update  = "INSERT INTO robot_certs (subscriber_id, uploaded_by, uploaded_date, valid_until, cert, fingerprint, serial, comment)";
			$update .= " VALUES(?, ?, current_timestamp(), ?, ?, ?, ?, ?)";
			$params	= array('text', 'text', 'text', 'text', 'text', 'text', 'text');
			$data	= array($subscriber_id, $admin_id, $cert->validTo(), $cert->getCert(), $cert->fingerprint(), $cert->serial(), $comment);
			MDB2Wrapper::update($update, $params, $data);
			Logger::log_event(LOG_INFO, "[RI] Added new certificate (". $cert->serial() .
					  ") for subscriber " . $this->person->getSubscriber()->getOrgName() .
					  " associated with admin " . $this->person->getEPPN());

		} catch (Exception $e) {
			/* FIXME */
			Framework::error_output("Couldn't update robot_certs, server said:<br />\n" .
			                        htmlentities($e->getMessage()));
			return false;
		}
		Framework::success_output($this->translateTag('l10n_suc_insertcert1', 'robot') . " " .
		                          $cert->serial() .
		                          $this->translateTag('l10n_suc_insertcert2', 'robot'));
		return true;
	}


	/**
	 * deleteCertificate() - remove a certificate associated with the
	 * subscriber from the database.
	 *
	 * @param String $serial the serial-number of the certificate.
	 * @return Boolean the result.
	 */
	private function deleteCertificate($serial)
	{
		$cert = $this->getRobotCert($serial);
		if (isset($cert)) {
			try {
				MDB2Wrapper::update("DELETE FROM robot_certs WHERE id=?",
						    array('text'),
						    array($cert['id']));
				Framework::success_output($this->translateTag('l10n_suc_deletecert1', 'robot') .
				                          htmlentities($serial) .
				                          $this->translateTag('l10n_suc_deletecert2', 'robot'));
				Logger::log_event(LOG_NOTICE, "[RI] " . $this->person->getEPPN() .
						  " from " .$this->person->getSubscriber()->getOrgName().
						  " deleted certificate $serial from the database");
				return true;
			} catch (Exception $e) {
				Framework::error_output(htmlentities($e->getMessage()));
				return false;
			}
		} else {
			Framework::error_output("Could not find certificate (".htmlentities($serial).") in database.");
			return false;
		}

		/* Unreachable, but nevertheless */
		return false;
	}
	private function getRobotCert($serial)
	{
		$query = "SELECT * FROM robot_certs where subscriber_id=? AND serial=?";

		$params = array('text', 'text');
		$data	= array($this->person->getSubscriber()->getDBID(), $serial);
		try {
			$res = MDB2Wrapper::execute($query,$params, $data);
			if (count($res)!= 1) {
				return null;
			}
			return $res[0];
		} catch (Exception $e) {
			Framework::error_output("Could not find cert. Server said: " . htmlentities($e->getMessage()));
			return null;
		}
		return null;
	} /* end getRobotCert */

	/**
	 * downloadArchive() pack the RI-library in a zip-file and present it as
	 * a file to download.
	 *
	 * @param  : void
	 * @return : Boolean True if no errors were encountered.
	 */
	private function downloadArchive()
	{
		require_once 'file_download.php';
		$confusa_client = file_get_contents(Config::get_config('install_path')
						    . "/extlibs/XML_Client/Confusa_Client.py");
		$confusa_parser = file_get_contents(Config::get_config('install_path')
						    . "/extlibs/XML_Client/Confusa_Parser.py");
		$https_client	= file_get_contents(Config::get_config('install_path')
						    . "/extlibs/XML_Client/HTTPSClient.py");
		$timeout	= file_get_contents(Config::get_config('install_path')
						    . "/extlibs/XML_Client/Timeout.py");
		$readme		= file_get_contents(Config::get_config('install_path')
						    . "/extlibs/XML_Client/README");
		$license	= file_get_contents(Config::get_config('install_path')
						    . "/extlibs/XML_Client/LICENSE");
		$gplv3		= file_get_contents(Config::get_config('install_path')
						    . "/COPYING");
		$init = file_get_contents(Config::get_config('install_path') . "/extlibs/XML_Client/__init__.py");

		$zip = new ZipArchive();
		$name = tempnam($ZIP_CACHE, "XML_Cli_");
		$zip->open($name, ZipArchive::OVERWRITE);
		$zip->addFromString("XML_Client/Confusa_Client.py",	$confusa_client);
		$zip->addFromString("XML_Client/Confusa_Parser.py",	$confusa_parser);
		$zip->addFromString("XML_Client/HTTPSClient.py",	$https_client);
		$zip->addFromString("XML_Client/Timeout.py",	$timeout);
		$zip->addFromString("XML_Client/README",		$readme);
		$zip->addFromString("XML_Client/LICENSE",		$license);
		$zip->addFromString("XML_Client/COPYING",		$gplv3);
		$zip->addFromString("XML_Client/__init__.py",		$init);
		if ($zip->numFiles != 8) {
			Logger::log_event(LOG_NOTICE,  " Could not add all RI-library files to ZIP-archive.");
			Framework::error_output("Error creating archive. Cannot send");
			return False;
		}
		if ($zip->close()) {
			$contents = file_get_contents($name);
			download_zip($contents, "XML_Client.zip");
		}
		unlink($name);
		Logger::log_event(LOG_NOTICE, "Sending XML_Client.zip to " . $this->person->getEPPN());
		return True;
	}
}

$fw = new Framework(new CP_Robot_Interface());
$fw->start();

?>
