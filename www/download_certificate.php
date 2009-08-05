<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'mail_manager.php';
final class DownloadCertificate extends FW_Content_Page
{
	public function __construct()
	{
		parent::__construct("Download Certificates", true);
	}
	public function pre_process($person)
	{
		parent::pre_process($person);

		$res = false;
		if ($this->person->is_auth()){
			if (isset($_GET['file_cert'])) {
				$authKey = htmlentities($_GET['file_cert']);
				try {
					$cert = $this->certManager->get_cert($authKey);
					if (isset($cert)) {
						require_once 'file_download.php';
						download_file($cert, 'usercert.pem');
						exit(0);
					}
				} catch(ConfusaGenException $cge) {
					;
				}
			}
		}
		return false;
	}

	public function process()
	{
		if (!$this->person->is_auth()) {
			error_msg("This is an impossible condition. How did you get in here?");
			return;
		}
		/* test and handle flags */
		$this->processDBCert();
		try {
			$this->tpl->assign('certList', $this->certManager->get_cert_list());
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

		else if (isset($_GET['email_cert']))
			$this->mailCert(htmlentities($_GET['email_cert']));

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
		try {
			$cert = $this->certManager->get_cert($authKey);
		} catch (ConfusaGenException $cge) {
			Framework::error_output("Certificate does not exist in cert_cache");
			Logger::log_event(LOG_NOTICE, "Could not delete given CSR with id ".$authKey." from ip ".$_SERVER['REMOTE_ADDR']);
			return false;
		}

		try {
		MDB2Wrapper::update("DELETE FROM cert_cache WHERE auth_key=? AND cert_owner=?",
				    array('text', 'text'),
				    array($authKey, $this->person->get_valid_cn()));
		} catch (Exception $e) {
			/* FIXME: better error-handling */
			Framework::error_output($e->getMessage);
		}
		Logger::log_event(LOG_NOTICE, "Dropping CERT with ID ".$authKey." belonging to ".$this->person->get_valid_cn());
		$this->tpl->assign('processingResult', 'Certificate deleted');
	} /* end deleteCert */

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
			echo $e->getMessage();
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
						      "Signed certificate from " . Config::get_config('system_name'), 
						      "Attached is your new certificate. Remember to store this in \$HOME/.globus/usercert.pem for ARC to use");
				$mm->add_attachment($cert, 'usercert.pem');
				if (!$mm->send_mail()) {
					Framework::error_output("Could not send mail properly!");
					return false;
				}
			}
		} catch (ConfusaGenException $e) {
			echo $e->getMessage();
		}
		$this->tpl->assign('processingResult', 'Email sent OK');
	} /* end send_cert */

} /* end class DownloadCertificate */

$fw = new Framework(new DownloadCertificate());
$fw->start();


function list_remote_certs($person)
{
  $list_endpoint = Config::get_config('capi_listing_endpoint');
  $postfields_list["loginName"] = Config::get_config('capi_login_name');
  $postfields_list["loginPassword"] = Config::get_config('capi_login_pw');

  $test_prefix = "";
  if (Config::get_config('capi_test')) {
    /* TODO: this should go into a constant. However, I don't want to put it into confusa_config, since people shouldn't directly fiddle with it */
    $test_prefix = "TEST PERSON ";
  }

  $postfields_list["commonName"] = $test_prefix . $person->get_valid_cn();
  $ch = curl_init($list_endpoint);
  curl_setopt($ch, CURLOPT_HEADER,0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_POST,1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields_list);
  $data=curl_exec($ch);
  curl_close($ch);

  $params=array();
  $res = array();
  parse_str($data, $params);

  if ($params["errorCode"] == "0") {
    for ($i = 1; $i <= $params['noOfResults']; $i = $i+1) {
      $res[$i-1]['order_number'] = $params[$i . "_orderNumber"];
      $res[$i-1]['cert_owner'] = $person->get_valid_cn();
    }
  } else {
    Framework::error_output("Errors occured when listing user certificates: " . $params["errorMessage"]);
  }

  return $res;

} /* end list_remote_certs() */

?>
