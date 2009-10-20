<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'mail_manager.php';
require_once 'confusa_gen.php';
require_once 'output.php';

final class CP_DownloadCertificate extends Content_Page
{
	public function __construct()
	{
		parent::__construct("Download Certificates", true);
		Framework::sensitive_action();
	}
	public function pre_process($person)
	{
		parent::pre_process($person);

		$res = false;
		if ($this->person->isAuth()){
			if (isset($_GET['file_cert'])) {
				$authKey = htmlentities($_GET['file_cert']);
				try {
					$cert = $this->certManager->get_cert($authKey);
					if (isset($cert)) {
						include 'file_download.php';
						download_file($cert, 'usercert.pem');
						exit(0);
					}
				} catch(ConfusaGenException $cge) {
					Framework::error_output("Could not download the certificate, server said: " . $cge->getMessage());
				}
			}
		}
		return false;
	}

	public function process()
	{
		if (!$this->person->isAuth()) {
			error_msg("This is an impossible condition. How did you get in here?");
			return;
		}
		/* test and handle flags */
		$this->processDBCert();
		try {
			$certList = $this->certManager->get_cert_list();
			/* sort the revoked certificates after the active certificates */
			$revoked = array_filter($certList, array($this, 'revokedFilter'));
			$non_revoked = array_diff_assoc($certList, $revoked);
			$certList = array_merge($non_revoked, $revoked);
			$this->tpl->assign('certList', $certList);
		} catch (ConfusaGenException $e) {
			Framework::error_output("Could not retrieve certificates from the database. Server said: " .  $e->getMessage());
		}
		$this->tpl->assign('standalone', (Config::get_config('ca_mode') === CA_STANDALONE));
		$this->tpl->assign('content', $this->tpl->fetch('download_certificate.tpl'));
	}


	private function processDBCert()
	{
		if(isset($_GET['delete_cert']))
			$this->deleteCert(htmlentities($_GET['delete_cert']));

		else if (isset($_GET['inspect_cert']))
			$this->inspectCert(htmlentities($_GET['inspect_cert']));

		else if (isset($_GET['email_cert'])) {
			$mail = $this->person->getEmail();
			if (!isset($mail) || $mail === "") {
				$msg = "No email-address is set. Cannot email certificate to you!<br />\n";
				$msg .= "This is a required attribute for many operations, and you should therefore contact ";
				$msg .= "your local IT-support and ask them to verify your user-cerdentials.<br />\n";
				Framework::error_output($msg);
			} else {
				$this->mailCert(htmlentities($_GET['email_cert']));
			}
		}

		else if (isset($_GET['install_cert']))
			$this->installCert(htmlentities($_GET['install_cert']));

	} /* end process_db_cert */

	/**
	 * deleteCert - delete a certificate from cert_cache with supplied
	 *		authKey as long as it belongs to the current user.
	 *
	 * @authKey : the authKey for the certificate (hash of the pubkey) also
	 *	      found in the database.
	 */
	private function deleteCert($authKey)
	{
		if ($this->certManager->deleteCertFromDB($authKey)) {
			$this->tpl->assign('processingResult', 'Certificate deleted');
		}
	} /* end deleteCert */

	private function installCert($authKey)
	{
		$ua = getUserAgent();
		$script = $this->certManager->getCertDeploymentScript($authKey, $ua);

		if ($ua == "keygen") {
			include 'file_download.php';
			download_certificate($script, "install.crt");
			exit(0);
		} else {
			$script .= "<noscript><b>Please enable JavaScript to install certificates ";
			$script .= "in your browser's keystore!</b></noscript>";
			$this->tpl->assign("script", $script);
		}
	}

	/**
	 * inspectCert - take a given authKey and inspect the certificate it
	 * points to, given that the cert exists.
	 *
	 * This function will 'verbosify' a certificate with given cert_id.
	 * Basically it will print it in human-readable form and let the user verify it.
	 */
	private function inspectCert($authKey)
	{
		/* FIXME */
		try {
			$cert = $this->certManager->get_cert($authKey);
			if (isset($cert)) {
				$csr_test = openssl_x509_read($cert);
				if (openssl_x509_export($csr_test, $text, false)) {
					$this->tpl->assign('pem', $text);
					$this->tpl->assign('standalone', (Config::get_config('ca_mode') === CA_STANDALONE));
				} else {
					$this->tpl->assign('certificate', print_r($cert));
				}
			}
		} catch (ConfusaGenException $e) {
			Framework::error_output("Could not retrieve the certificate, server said: " . $e->getMessage());
		}

		$this->tpl->assign('processingToken',  $authKey);
		$this->tpl->assign('processingResult', $this->tpl->fetch('inspect_certificate.tpl'));
	} /* end inspectCert */

	private function mailCert($authKey)
	{
		try {
			$cert = $this->certManager->get_cert($authKey);
			if (isset($cert)) {
				$mm = new MailManager($this->person,
						      Config::get_config('sys_from_address'),
						      Config::get_config('sys_header_from_address'),
						      "Signed certificate from " . Config::get_config('system_name'),
						      "Attached is your new certificate. Remember to store this in \$HOME/.globus/usercert.pem for ARC to use");
				$mm->add_attachment($cert, 'usercert.pem');
				if (!$mm->send_mail()) {
					Framework::error_output("Could not send mail properly!");
					return false;
				}
			}
		} catch (ConfusaGenException $e) {
			Framework::error_output("Could not mail the certificate, server said: " . $e->getMessage());
		}
		$this->tpl->assign('processingResult', 'Email sent OK');
	} /* end send_cert */

	/**
	 * include only revoked certificates from the result array
	 *
	 * @param $var a row of the result array
	 * @return true if the row corresponds to a revoked result, false otherwise
	 */
	private function revokedFilter($var)
	{
		return (isset($var['revoked']) && $var['revoked'] === true);
	}

} /* end class DownloadCertificate */

$fw = new Framework(new CP_DownloadCertificate());
$fw->start();

?>
