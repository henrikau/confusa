<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'Person.php';
require_once 'MailManager.php';
require_once 'confusa_gen.php';
require_once 'Output.php';

final class CP_DownloadCertificate extends Content_Page
{

	private $showAll = false;

	public function __construct()
	{
		parent::__construct("Download Certificates", true, "download");
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
					$cert = $this->ca->getCert($authKey);
					if (isset($cert)) {
						include 'file_download.php';
						download_file($cert, 'usercert.pem');
						exit(0);
					}
				} catch(ConfusaGenException $cge) {
					Framework::error_output($this->translateMessageTag('downl_err_nodownload')
					                        . " " . htmlentities($cge->getMessage()));
				}
			} else if (isset($_GET['cert_status'])) {
				$this->pollCertStatusAJAX(Input::sanitizeCertKey($_GET['cert_status']));
			} else if (isset($_GET['certlist_all'])) {
				$this->showAll = ($_GET['certlist_all'] == "true");
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

		$subscriber = $this->person->getSubscriber();

		if (empty($subscriber) || !$subscriber->isSubscribed()) {
			$this->tpl->assign('not_subscribed_header',
					   $this->translateTag('l10n_not_sub_header', 'messages'));
			$this->tpl->assign('not_subscribed_1',
					   $this->translateTag('l10n_not_sub_1', 'messages'));
			$this->tpl->assign('not_subscribed_2',
					   $this->translateTag('l10n_not_sub_2', 'messages'));
			$this->tpl->assign('content', $this->tpl->fetch('errors/unsubscribed.tpl'));
			return;
		}

		/* test and handle flags */
		$this->processDBCert();
		try {
			$certList = $this->ca->getCertList($this->showAll);
			/* sort the revoked certificates after the active certificates */
			$revoked = array_filter($certList, array($this, 'revokedFilter'));
			$non_revoked = array_diff_assoc($certList, $revoked);
			$certList = $non_revoked + $revoked;
			$this->tpl->assign('certList', $certList);
			$this->tpl->assign('showAll', $this->showAll);
			$this->tpl->assign('defaultDays',
				               Config::get_config('capi_default_cert_poll_days'));
		} catch (ConfusaGenException $e) {
			Framework::error_output($this->translateMessageTag('downl_err_db') .
			                        htmlentities($e->getMessage()));
		}

		/* animate the user to install the root certificate in personal mode,
		 * so Thunderbird will fully trust the certs when using them in S/MIME
		 */
		if (Config::get_config('cert_product') == PRD_PERSONAL) {
			$this->tpl->assign('ca_certificate',
			                   ConfusaConstants::$CAPI_PERSONAL_ROOT_CERT);
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
				Framework::error_output($this->translateMessageTag('downl_err_noemail'));
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
		if ($this->ca->deleteCertFromDB($authKey)) {
			$this->tpl->assign('processingResult', 'Certificate deleted');
		}
	} /* end deleteCert */

	private function installCert($authKey)
	{
		$ua = Output::getUserAgent();
		$script = $this->ca->getCertDeploymentScript($authKey, $ua);

		if ($ua == "keygen") {
			include 'file_download.php';
			download_certificate($script, "install.crt");
			exit(0);
		} else {
			$script .= "<noscript><b>" .
			           $this->translateTag('l10n_noscript_notice', 'download') .
			           "</b></noscript>";
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
			$cert = $this->ca->getCert($authKey);
			if (isset($cert)) {
				$csr_test = openssl_x509_read($cert);
				if (openssl_x509_export($csr_test, $text, false)) {

					/* call made from AJAX or from someone acting as if AJAX,
					 * just print the textual string
					 */
					if (isset($_GET['ajax'])) {
						echo "Success:<pre class=\"certificate\">" .
						     htmlentities($text) .
						     "</pre>";
						exit(0);
					}

					$this->tpl->assign('pem', $text);
					$this->tpl->assign('standalone', (Config::get_config('ca_mode') === CA_STANDALONE));
				} else {
					$this->tpl->assign('certificate', print_r($cert));
				}
			}
		} catch (ConfusaGenException $e) {
			Framework::error_output($this->translateMessageTag('downl_err_misc')
			                        . " " . htmlentities($e->getMessage()));
		}

		$inspectElement = array();
		$inspectElement[$authKey] = $this->tpl->fetch('inspect_certificate.tpl');
		$this->tpl->assign('processingToken',  $authKey);
		$this->tpl->assign('inspectElement', $inspectElement);
	} /* end inspectCert */

	private function mailCert($authKey)
	{
		try {
			$cert = $this->ca->getCert($authKey);

			if (isset($cert)) {
				$mm = new MailManager($this->person,
						      Config::get_config('sys_from_address'),
						      Config::get_config('system_name'),
						      Config::get_config('sys_header_from_address'));
				$mm->setSubject($this->translateTag('l10n_mail_subject', 'download'));
				$mm->setBody($this->translateTag('l10n_mail_body', 'download'));
				$mm->addAttachment($cert, 'usercert.pem');

				if (!$mm->sendMail()) {
					Framework::error_output($this->translateMessageTag('downl_err_sendmail'));
					return false;
				}
			} else {
				return false;
			}
		} catch (ConfusaGenException $e) {
			Framework::error_output($this->translateMessageTag('downl_err_sendmail2')
			                        . " " . htmlentities($e->getMessage()));
			return false;
		}
		Framework::success_output($this->translateMessageTag('downl_suc_mail'));
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

	/**
	 * Call this to poll the status of the certificate identified by the given
	 * key from an AJAX function.
	 *
	 * @param $key mixed The key (order-number, auth-key) identifying the certificate
	 * @return "done" if the certificate is available
	 *         "processing" if it is still being processed
	 */
	private function pollCertStatusAJAX($key)
	{
		$status = $this->ca->pollCertStatus($key);

		if ($status === true) {
			echo "done";
			exit(0);
		} else {
			echo "processing";
			exit(0);
		}
	}
} /* end class DownloadCertificate */

$fw = new Framework(new CP_DownloadCertificate());
$fw->start();

?>
