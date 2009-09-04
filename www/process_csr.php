<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'logger.php';
require_once 'csr_lib.php';
require_once 'file_upload.php';
require_once 'config.php';
require_once 'send_element.php';
require_once 'input.php';

/**
 * ProcessCsr - the web frontend for handling of CSRs
 *
 * @author Henrik Austad <henrik.austad@uninett.no>
 */
final class CP_ProcessCsr extends FW_Content_Page
{
	private $signing_ok;

	function __construct()
	{
		parent::__construct("Process CSR", true);
		Framework::sensitive_action();

		$this->signing_ok = false;
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		$res = false;
		if (isset($_GET['sign_csr'])) {
			$res = $this->approveCsr(htmlentities($_GET['sign_csr']));
		}
		return $res;

	}
	
	public function process()
	{
		/* show upload-form. If it returns false, no uploaded CSRs were processed */
		$this->processFileCSR($this->person);

		/* if flags are set, process the CSR*/
		if ($this->processCSRFlagsSet()) {
			if (!$this->processDBCsr()) {
				Framework::error_output("Errors were encountered when processing " . $this->getActualFlags());
			}
		}

		if($this->signing_ok) {
			$this->tpl->assign('signingOk', $this->signing_ok);
			$this->tpl->assign('sign_csr', htmlentities($_GET['sign_csr']));
		}
		$this->tpl->assign('inspect_csr',	$this->tpl->fetch('csr/inspect_csr.tpl'));
		$this->tpl->assign('csrList',		$this->listAllCSR($this->person));
		$this->tpl->assign('list_all_csr',	$this->tpl->fetch('csr/list_all_csr.tpl'));
		$this->tpl->assign('upload_csr_file',	$this->tpl->fetch('csr/upload_csr_file.tpl'));
		$this->tpl->assign('content',		$this->tpl->fetch('csr/process_csr.tpl'));
	}

	/**
	 * processCSRFlags_set - test to see if any of the CSR flags are set.
	 */
	private function processCSRFlagsSet()
	{
		return isset($_GET['delete_csr']) || isset($_GET['inspect_csr']);
	}

	private function getActualFlags()
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
	 * processFileCSR - walk an uploaded CSR through the steps towards a certificate
	 *
	 * If a new CSR has been uploaded via FILE, this will retrieve it, store it in
	 * the database and pass control over to CertManager to process it. 
	 */
	private function processFileCSR()
	{
		/* Testing for uploaded files */
		if(isset($_FILES['user_csr']['name'])) {
			$fu = new FileUpload('user_csr', true, true, 'test_content');
			if ($fu->file_ok()) {
				$csr = $fu->get_content();
				$subject = openssl_csr_get_subject($csr, true);
				$authvar = substr(pubkey_hash($fu->get_content(), true), 0, (int)Config::get_config('auth_length'));
				/* is the CSR already uploaded? */
				$res = MDB2Wrapper::execute("SELECT auth_key, from_ip FROM csr_cache WHERE csr=?",
							    array('text'),
							    array($csr));
				if (count($res)>0) {
					Framework::error_output("CSR already present in the database, no need for second upload");
				} else if (test_content($csr, $authvar) && match_dn($subject, $this->person)) {
					$ip	= $_SERVER['REMOTE_ADDR'];
					$query  = "INSERT INTO csr_cache (csr, uploaded_date, from_ip,";
					$query .= " common_name, auth_key)";
					$query .= " VALUES(?, current_timestamp(), ?, ?, ?)";

					MDB2Wrapper::update($query,
							    array('text', 'text', 'text', 'text'),
							    array($csr, $ip, $this->person->getX509ValidCN(), $authvar));

					$logmsg  = __FILE__ . " Inserted new CSR from $ip (" . $this->person->getX509ValidCN();
					$logmsg .=") with hash " . pubkey_hash($csr, true);
					Logger::log_event(LOG_INFO, $logmsg);
				}
			} else {
				/* File NOT OK */
					$msg  = "There were errors encountered when processing the file.<br />";
					$msg .= "Please create a new keypair and upload a new CSR to the server.";
					Framework::error_output($msg);
			}
		}
	}

	/**
	 * processDBCsr()
	 *
	 * This function shall look at all the csr's in the csr_cache, and present the
	 * CSR belonging to the user, to the user.
	 *
	 * Note: approve is not handled here, as that requires header-rewriting.
	 */
	private function processDBCSR()
	{
		$res = false;
		if (isset($_GET['delete_csr'])) {
			$res = delete_csr_from_db($this->person, htmlentities($_GET['delete_csr']));
			if ($res) {
				Framework::message_output("Successfully deleted CSR for user " . $this->person->getEPPN() . ".");
			} else {
				Framework::error_output("Could not delete CSR.");
			}
		}
		elseif (isset($_GET['inspect_csr'])) {
			try {
				$this->tpl->assign('csrInspect', get_csr_details($this->person, Input::sanitize($_GET['inspect_csr'])));
				$res = true;
			} catch (CSRNotFoundException $csrnfe) {
				$msg  = "Error with auth-token ($auth_key) - not found. ";
				$msg .= "Please verify that you have entered the correct auth-url and try again.";
				$msg .= "If this problem persists, try to upload a new CSR and inspect the fields carefully";
				Framework::error_output($msg);
				return false;
			} catch (ConfusaGenException $cge) {
				$msg = "Too menu returns received. This can indicate database inconsistency.";
				Framework::error_output($msg);
				Logger::log_event(LOG_ALERT, "Several identical CSRs (" .
						  $auth_token . ") exists in the database for user " .
						  $this->person->getX509ValidCN());
				return false;
			}	
		}
		return $res;
	}


	/**
	 * approveCsr - send the CSR to cert-manager for signing
	 *
	 * This function approves a CSR for signing. It uses the auth-token as a
	 * paramenter to find the CSR in the database coupled with the valid CN for the
	 * user.
	 */
	private function approveCSR($authToken)
	{
		try  {
			$csr = get_csr_from_db($this->person, $authToken);
		} catch (ConfusaGenException $e) {
			Framework::error_output("Too many hits. Database incosistency.");
			Logger::log_event(LOG_ALERT, $this->person->getX509ValidCN() .
					  " tried to find CSR with key $authToken which resulted in multiple hits");
			return false;
		} catch (CSRNotFoundException $csrnfe) {
			Framework::error_output("CSR not found, are you sure this is your CSR?\n");
			return false;
		}

		if (!isset($csr)) {
			Framework::error_output("Did not find CSR with auth_token $auth_token");
			$msg  = "User " . $this->person->getEPPN() . " ";
			$msg .= "tried to delete CSR with auth_token " . $authToken . " but was unsuccessful";
			Logger::log_event(LOG_NOTICE, $msg);
			return false;
		}

		try {
			if (!isset($this->certManager)) {
				Framework::error_output("certManager is NULL!");
				return false;
			}
			$this->certManager->sign_key($authToken, $csr);
		} catch (RemoteAPIException $rapie) {
			Framework::error_output("Error with remote API when trying to ship CSR for signing.<BR />\n" . $rapie);
			return false;
		} catch (ConfusaGenException $e) {
			$msg = __FILE__ .":".__LINE__." Error signing key.<BR />\nRemote said: " . $e;
			Framework::error_output($msg);
			return false;
		} catch (KeySigningException $kse) {
			Framework::error_output("Could not sign certificate. Server said: " . $kse->getMessage());
			return false;
		}
		delete_csr_from_db($this->person, $authToken);
		$this->signing_ok = true;

		/* Construct a meta http-equiv to be included in the
		 * <HEAD>-section in framework. This is to provide a
		 * auto-refrech for the user, resulting in a cleaner user
		 * experience. */
		$url = "http";
		if ($_SERVER['SERVER_PORT'] == 443)
			$url .= "s";
		$url .= "://" . $_SERVER['HTTP_HOST'] . "/" . dirname($_SERVER['PHP_SELF']) . "/download_certificate.php?poll=$authToken";
		return "<META HTTP-EQUIV=\"REFRESH\" content=\"3; url=$url\">\n";
	} /* end approve_csr_remote() */


	/**
	 * listAllCSR
	 *
	 * List all currently active CSRs for the user. Since we will only accept upload
	 * of CSRs through authenticated channels, no expiry will be enforced on CSRs.
	 */
	private function listAllCSR()
	{
		$query = "SELECT csr_id, uploaded_date, common_name, auth_key, from_ip FROM csr_cache WHERE common_name=? ORDER BY uploaded_date DESC LIMIT 10";
		$res = MDB2Wrapper::execute($query,
					    array('text'),
					    $this->person->getX509ValidCN());
		/* Format the IPs */
		foreach ($res as $key => $value) {
			$res[$key]['from_ip'] = format_ip($value['from_ip'], true);
		}
		return $res;
	}

}

$fw = new Framework(new CP_ProcessCsr());
$fw->start();

?>
