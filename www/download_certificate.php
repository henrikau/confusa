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

		echo "<H3>Certificate Download Area</H3>\n";
		/* test and handle flags */
		$this->processDBCert();
		/* show all stored certificates (with links to handle) */
		$this->showDBCert();

	}


	private function processDBCert()
	{
		$res		= false;

		if(isset($_GET['delete_cert']))
			$res = $this->deleteCert(htmlentities($_GET['delete_cert']));

		else if (isset($_GET['inspect_cert']))
			$res = $this->inspectCert(htmlentities($_GET['inspect_cert']));

		else if (isset($_GET['email_cert']))
			$res = $this->mailCert(htmlentities($_GET['email_cert']));

		return $res;
	} /* end process_db_cert */



	/* show_db_cert
	 *
	 * Retrieve certificates from the database and show them to the user
	 */
	private function showDBCert()
	{
		try {
			$res = $this->certManager->get_cert_list();
		} catch (ConfusaGenException $e) {
			echo $e->getMessage();
		}

		$num_received = count($res);
		if ($num_received > 0) {
			$counter = 0;
			echo "<TABLE CLASS=\"small\">\n";
			echo "<TR>";
			echo "<TH></TH>\n";
			echo "<TH></TH>\n";
			echo "<TH>Expires (from DB)</TH>\n";
			echo "<TH></TH>\n";
			echo "<TH>AuthToken</TH>";
			echo "<TH>Owner</TH>";
			echo "</TR>\n";
			while($counter < $num_received) {
				$row = $res[$counter];
				$counter++;
				echo "<tr>\n";
				if (Config::get_config('standalone')) {
					echo "<TD>[ <A HREF=\"".$_SERVER['PHP_SELF']."?email_cert="	. $row['auth_key'] . "\">Email</A> ]</TD>\n";
					echo "<TD>[ <A HREF=\"".$_SERVER['PHP_SELF']."?file_cert="	. $row['auth_key'] . "\">Download</A> ]</td>\n";
					echo "<TD>"	. $row['valid_untill']	. "</td>\n";
					echo "<TD>"	. $row['cert_owner']	. "</td>\n";
					echo "<TD>[ <A HREF=\"".$_SERVER['PHP_SELF']."?inspect_cert="	. $row['auth_key'] . "\">Inspect</A> ]</TD>\n";
					echo "<TD>[ <A HREF=\"".$_SERVER['PHP_SELF']."?delete_cert="	. $row['auth_key'] . "\">Delete</A> ]</TD>\n";
				} else {
					echo "<TD>[ <A HREF=\"".$_SERVER['PHP_SELF']."?email_cert="	. $row['order_number'] . "\">Email</A> ]</TD>\n";
					echo "<TD>[ <A HREF=\"".$_SERVER['PHP_SELF']."?file_cert="	. $row['order_number'] . "\">Download</A> ]</TD>\n";
					echo "<TD>[ <A HREF=\"".$_SERVER['PHP_SELF']."?inspect_cert="	. $row['order_number'] . "\">Inspect</A> ]</TD>\n";
					/* deletion of a certificate won't make sense
					 * with the remote API. When we implement the
					 * remote-revocation-API we can provide a revoke
					 * link here. */
					echo "<TD></TD>\n";
					echo "<TD>" . $row['order_number']	. "</TD>\n";
					echo "<TD>" . $row['cert_owner']	. "</TD>\n";
				}
				echo "</TR>\n";
			}
			echo "</TABLE>\n";
		}
		echo "<BR />\n";
	}


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
			error_output("Certificate does not exist in cert_cache");
			Logger::log_event(LOG_NOTICE, "Could not delete given CSR with id ".$authKey." from ip ".$_SERVER['REMOTE_ADDR']);
			return false;
		}

		MDB2Wrapper::update("DELETE FROM cert_cache WHERE auth_key=? AND cert_owner=?",
				    array('text', 'text'),
				    array($authKey, $this->person->get_valid_cn()));

		Logger::log_event(LOG_NOTICE, "Dropping CERT with ID ".$authKey." belonging to ".$this->person->get_valid_cn());
		return true;
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

		$status = false;

		try {
			$cert = $this->certManager->get_cert($authKey);
			if (isset($cert)) {
				echo "<BR>\n";
				echo "<BR>\n";
				$csr_test = openssl_x509_read($cert);
				if (openssl_x509_export($csr_test, $text, false)) {
					echo "[ <a href=\"".$_SERVER['PHP_SELF']."?email_cert=$authKey\">Email</a> ]\n";
					echo "[ <a href=\"".$_SERVER['PHP_SELF']."?file_cert=$authKey\">Download</a> ]\n";
					echo "[ <B>Inspect</B> ]\n";
					if (Config::get_config('standalone')) {
						echo "[ <a href=\"".$_SERVER['PHP_SELF']."?delete_cert=$authKey\">Delete</a> ]\n";
					}
					echo "<pre>$text</pre>\n";
					$status = true;
				} else {
					/* not able to show it properly, dump content to screen */
					echo "There were errors encountered when formatting the certificate. Here is a raw-dump.<BR>\n";
					echo "<PRE>\n";
					print_r ($cert);
					echo "</PRE>\n";
				}
			}
		} catch (ConfusaGenException $e) {
			echo $e->getMessage();
		}

		return $status;
	} /* end inspectCert */

	private function mailCert($authKey)
	{
		$send_res = false;
		try {
			$cert = $this->certManager->get_cert($authKey);
			if (isset($cert)) {
				$mm = new MailManager($this->person,
						      Config::get_config('sys_from_address'),
						      "Signed certificate from " . Config::get_config('system_name'), 
						      "Attached is your new certificate. Remember to store this in \$HOME/.globus/usercert.pem for ARC to use");
				$mm->add_attachment($cert, 'usercert.pem');
				if (!$mm->send_mail()) {
					error_output("Could not send mail properly!");
					return false;
				}
			}
		} catch (ConfusaGenException $e) {
			echo $e->getMessage();
		}
		echo "Email sent OK<BR />\n";
		return $send_res;
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
    echo "Errors occured when listing user certificates: " . $params["errorMessage"];
  }

  return $res;

} /* end list_remote_certs() */

?>