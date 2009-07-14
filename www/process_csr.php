<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'logger.php';
require_once 'csr_lib.php';
require_once 'upload_form.php';
require_once 'file_upload.php';
require_once 'config.php';
require_once 'send_element.php';

final class ProcessCsr extends ContentPage
{
	private $signing_ok;

	function __construct()
	{
		parent::__construct("Process CSR", true);
		$this->signing_ok = false;
	}

	public function pre_process($person)
	{
		$this->setPerson($person);
		$this->setManager();
		$res = false;
		if (isset($_GET['sign_csr'])) {
			$res = $this->approve_csr(htmlentities($_GET['sign_csr']), $person);
		}
		return $res;

	}
	
	public function process($person)
	{
		echo "<H3>Requesting new Certificates</H3>\n";
		/* show upload-form. If it returns false, no uploaded CSRs were processed */
		$this->process_file_csr($person);

		/* if flags are set, process the CSR*/
		if ($this->process_csr_flags_set()) {
			if (!$this->process_db_csr($person)) {
				error_output("Errors were encountered when processing " . $this->get_actual_flags());
			}

		}
		if ($this->signing_ok) {
			echo "<DIV class=\"message\">\n";
			echo "The certificate is now being provessed by the CA (Certificate Authority)<BR />\n";
			echo "Depending on the load, this takes approximately 2 minutes.<BR />\n";
			echo "<BR />\n";
			echo "You will now be redirected to the certificate-download area found ";
			echo "<A HREF=\"download_certificate.php\">here</A><BR>\n";
			echo "</DIV>\n";
			echo "<BR />\n";
		}

		/* Show the inspect-dialogue */
		set_value($name='inspect_csr', 'index.php', 'Inspect CSR', 'GET');


		/* List all CSRs for the person */
		$this->list_all_csr($person);

	}
	public function post_render($person)
	{
		/* cleanups etc? */
	}


	/**
	 * process_csr_flags_set - test to see if any of the CSR flags are set.
	 */
	private function process_csr_flags_set()
	{
		return isset($_GET['delete_csr']) || isset($_GET['inspect_csr']);
	}

	private function get_actual_flags()
	{
		$msg = "";
		if (isset($_GET['delete_csr']))
			$msg .= "delete_csr : " . htmlentities($_GET['delete_csr']) . " ";

		if (isset($_GET['sign_csr']))
			$msg .= "sign_csr : " . htmlentities($_GET['sign_csr']) . " ";

		if (isset($_GET['inspect_csr']))
			$msg .= "inspect_csr : " . htmlentities($_GET['inspect_csr']) . " ";
		return $msg;
	}
	/**
	 * process_file_csr - walk an uploaded CSR through the steps towards a certificate
	 *
	 * If a new CSR has been uploaded via FILE, this will retrieve it, store it in
	 * the database and pass control over to CertManager to process it. 
	 */
	private function process_file_csr($person)
	{
		/* Testing for uploaded files */
		if(isset($_FILES['user_csr']['name'])) {
			decho("Found new CSR<BR>\n");
			$fu = new FileUpload('user_csr', true, 'test_content');
			if ($fu->file_ok()) {
				decho("File-upload OK, starting test");
				$csr = $fu->get_content();
				$authvar = pubkey_hash($fu->get_content(), true);
			

				/* are the CSR already uploaded? */
				$res = MDB2Wrapper::execute("SELECT auth_key, from_ip FROM csr_cache WHERE csr=?",
							    array('text'),
							    array($csr));
				if (count($res)>0) {
					error_output("CSR already present in the database, no need for second upload");
				} else {
					decho("Inserting into system");
					$ip=$_SERVER['REMOTE_ADDR'];
					$query  = "INSERT INTO csr_cache (csr, uploaded_date, from_ip,";
					$query .= " common_name, auth_key)";
					$query .= " VALUES(?, current_timestamp(), ?, ?, ?)";
					MDB2Wrapper::update($query,
							    array('text', 'text', 'text', 'text'),
							    array($csr, $ip, $person->get_valid_cn(), $authvar));
					$logmsg  = __FILE__ . " Inserted new CSR from $ip (" . $person->get_valid_cn();
					$logmsg .=") with hash " . pubkey_hash($csr, true);
					Logger::log_event(LOG_INFO, $logmsg);
				}
				/* CertManager will test content of CSR before sending it off for signing
				 *
				 * As we upload the key manually, the user-script won't
				 * be called for creating a auth-token. We therefore
				 * create a random string containing the correct amount
				 * of characters. It will contain more letters than the
				 * user-script (which uses sha1sum of some random text).
				 */
			} else {
<<<<<<< HEAD:www/process_csr.php
				decho("Inserting into system");
				$ip=$_SERVER['REMOTE_ADDR'];
				/* first, a cert_user row must exist, otherwise a constraint
				 * violation will happen */
				$cm = CertManagerHandler::getManager($person);
				$expiry_array = Config::get_config('csr_default_timeout');
				$cm->touch_cert_user($expiry_array[0], $expiry_array[1]);

				$query  = "INSERT INTO csr_cache (csr, uploaded_date, from_ip,";
				$query .= " common_name, auth_key)";
				$query .= " VALUES(?, current_timestamp(), ?, ?, ?)";
				MDB2Wrapper::update($query,
						    array('text', 'text', 'text', 'text'),
						    array($csr, $ip, $person->get_valid_cn(), $authvar));
				$logmsg  = __FILE__ . " Inserted new CSR from $ip (" . $person->get_valid_cn();
				$logmsg .=") with hash " . pubkey_hash($csr, true);
				Logger::log_event(LOG_INFO, $logmsg);
=======
				error_output("There were errors encountered when processing the file.");
				error_output("Please create a new keypair and upload a new CSR to the server.");
>>>>>>> fw:www/process_csr.php
			}
		}
		show_upload_form($_SERVER['PHP_SELF']);
	}

	/**
	 * process_db_csr()
	 *
	 * This function shall look at all the csr's in the csr_cache, and present the
	 * CSR belonging to the user, to the user.
	 *
	 * Note: approve is not handled here, as that requires header-rewriting.
	 */
	private function process_db_csr($person)
	{
		$res = false;
		if (isset($_GET['delete_csr'])) {
			$res = delete_csr_from_db($person, htmlentities($_GET['delete_csr']));
		}
		elseif (isset($_GET['inspect_csr'])) {
			$res = print_csr_details($person, htmlentities($_GET['inspect_csr']));
		}
		return $res;
	}


	/**
	 * approve_csr - send the CSR to cert-manager for signing
	 *
	 * This function approves a CSR for signing. It uses the auth-token as a
	 * paramenter to find the CSR in the database coupled with the valid CN for the
	 * user.
	 */
	private function approve_csr($auth_token, $person)
	{
		try  {
			$csr = get_csr_from_db($person, $auth_token);
		} catch (ConfusaGenException $e) {
			error_output("Too many hits. Database incosistency.");
			return false;
		}

		if (!isset($csr)) {
			error_output("Did not find CSR with auth_token $auth_token");
			Logger::log_event(LOG_NOTICE, "User " . $person->get_common_name() . " tried to delete CSR with auth_token " . $auth_token . " but was unsuccessful");
			return false;
		}

		$cm = CertManagerHandler::getManager($person);

		try {
			$cm->sign_key($auth_token, $csr);
		} catch (ConfusaGenException $e) {
			echo __FILE__ .":".__LINE__." Error signing key<BR>\n";
			return false;
		}
		delete_csr_from_db($person, $auth_token);
		$url = "http";
		if ($_SERVER['SERVER_PORT'] == 443)
			$url .= "s";
		$url .= "://" . $_SERVER['HTTP_HOST'] . "/" . dirname($_SERVER['PHP_SELF']) . "/download_certificate.php?poll=$auth_token";
		$this->signing_ok = true;
		return "<META HTTP-EQUIV=\"REFRESH\" content=\"3; url=$url\">\n";
	} /* end approve_csr_remote() */


/**
 * list_all_csr
 *
 * List all currently active CSRs for the user. Since we will only accept upload
 * of CSRs through authenticated channels, no expiry will be enforced on CSRs.
 */
	private function list_all_csr($person)
	{
		$query = "SELECT csr_id, uploaded_date, common_name, auth_key, from_ip FROM csr_cache WHERE common_name=?";
		$res = MDB2Wrapper::execute($query,
					    array('text'),
					    $person->get_valid_cn());
		if (count($res) > 0) {
			/* Handle each separate instance */
			echo "<TABLE CLASS=\"small\">\n";
			echo "<TR>";
			echo "<TH>Uploaded date</TH>";
			echo "<TH>Common Name</TH>";
			echo "<TH>From IP</TH>";
			echo "<TH>Inspect</TH>";
			echo "<TH>Delete</TH>";
			echo "</tr>\n";
			foreach ($res as $key => $value) {
				echo "<TR>";
				echo "<TD>"	. $value['uploaded_date'] . "</TD>\n";
				echo "<TD><I>"	. $value['common_name'] . "</I></TD>\n";

				echo "<TD>".format_ip($value['from_ip'], true) ."</TD>\n";
				echo "<TD>[ <A HREF=\""	.$_SERVER['PHP_SELF']."?inspect_csr=".$value['auth_key']."\">Inspect</A> ]</TD>\n";
				echo "<TD>[ <A HREF=\""	.$_SERVER['PHP_SELF']."?delete_csr=".$value['auth_key']."\">Delete</A> ]</TD>\n";
				echo "</tr>\n";
			}
			echo "</TABLE>\n";
		} else {
			decho("There are no valid CSRs currently stored in the database for " . $person->get_valid_cn());
		}
	}

}

$fw = new Framework(new ProcessCsr());
$fw->start();

?>
