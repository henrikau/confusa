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
require_once 'output.php';
require_once 'permission.php';

/**
 * ProcessCsr - the web frontend for handling of CSRs
 *
 * @author Henrik Austad <henrik.austad@uninett.no>
 */
final class CP_ProcessCsr extends Content_Page
{
	private $signing_ok;

	function __construct()
	{
		parent::__construct("Process CSR", true);
		Framework::sensitive_action();

		$this->signing_ok = false;
	}

	/**
	 * pre_process - run before the template-system is called into action.
	 *
	 * We use this to test for pending certificate requests in the
	 * POST-hold.
	 *
	 * @param Person $person
	 */
	public function pre_process($person)
	{
		parent::pre_process($person);
		$res = false;
		if (isset($_GET['sign_csr'])) {
			$res = $this->approveCsr(Input::sanitizeBase64($_GET['sign_csr']));

		} else if (isset($_GET['status_poll'])) {
			$order_number = Input::sanitizeCertKey($_GET['status_poll']);
			/* assign the order_number again */
			$this->tpl->assign('order_number', $order_number);
			$this->tpl->assign('status_poll', true);

			if ($this->ca->pollCertStatus($order_number)) {
			    $this->tpl->assign('done', TRUE);
			}

		} else if (isset($_GET['install_cert'])) {
			$order_number = Input::sanitizeCertKey($_GET['install_cert']);
			$ua = getUserAgent();
			$script = $this->ca->getCertDeploymentScript($order_number, $ua);

			if ($ua == "keygen") {
			    include_once 'file_download.php';
			    download_certificate($script, "install.crt");
			    exit(0);
			} else {
				$this->tpl->assign('deployment_script', $script);
			}
		}

		if (isset($_POST['browserRequest'])) {
			$request = Input::sanitizeBase64($_POST['browserRequest']);
			$request = trim($request);
			if (!empty($request)) {
				$order_number = $this->approveBrowserGenerated($request, getUserAgent());
				$this->tpl->assign('order_number', $order_number);
			}
		}
		/* If $res is false, we risk that a '1' is printed, we do not
		 * want that :-) */
		if (!$res)
			return;
		return $res;
	}

	public function process()
	{

		/* show upload-form. If it returns false, no uploaded CSRs were processed */
		$this->processUploadedCSR($this->person);

		/* if flags are set, process the CSR*/
		if ($this->processCSRFlagsSet()) {
			if (!$this->processDBCsr()) {
				Framework::error_output("Errors were encountered when processing " . $this->getActualFlags());
			}
		}

		if($this->signing_ok) {
			$this->tpl->assign('signingOk', $this->signing_ok);
			$this->tpl->assign('sign_csr', Input::sanitizeBase64($_GET['sign_csr']));
		}

		$this->tpl->assign('inspect_csr',	$this->tpl->fetch('csr/inspect_csr.tpl'));
		$this->tpl->assign('csrList',		$this->listAllCSR($this->person));
		$this->tpl->assign('list_all_csr',	$this->tpl->fetch('csr/list_all_csr.tpl'));
		if ($this->person->testEntitlementAttribute(Config::get_config('entitlement_user'))) {
			$this->tpl->assign('user_cert_enabled', true);
		}

		/* set the browser signing variables only if browser signing is enabled */
		if (isset($_POST['browserSigning']) || isset($_GET['status_poll'])) {
			$browser_adapted_dn = $this->person->getBrowserFriendlyDN();
			$this->tpl->assign('dn',				$browser_adapted_dn);
			$this->tpl->assign('keysize',			Config::get_config('key_length'));
			$browserTemplate = $this->dispatchBrowserTemplate();
			$extraScript = array('js/cert_request.js');
			$this->tpl->assign('extraScripts', $extraScript);
			Framework::message_output("Generating certificate signing request in " .
			                          "the browser. <a href=\"process_csr.php\">Change</a>.");
			$this->tpl->assign('content',	$this->tpl->fetch($browserTemplate));
			return;
		}

		$this->tpl->assign('upload_csr_file', $this->tpl->fetch('csr/upload_csr_file.tpl'));
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
	 * processUploadedCSR - walk an uploaded CSR through the steps towards a certificate
	 *
	 * If a new CSR has been uploaded via FILE, this will retrieve it, store it in
	 * the database and pass control over to the CA to process it.
	 */
	private function processUploadedCSR()
	{
		$csr = null;
		/* Testing for uploaded files */
		if(isset($_FILES['user_csr']['name'])) {
			$fu = new FileUpload('user_csr', true, true, 'test_content');
			if ($fu->file_ok()) {
				$csr = $fu->get_content();
			} else {
				/* File NOT OK */
				$msg  = "There were errors encountered when processing the file.<br />";
				$msg .= "Please create a new keypair and upload a new CSR to the server.";
				Framework::error_output($msg);
			}
		} else if (isset($_POST['user_csr'])) {
			$csr = Input::sanitizeBase64($_POST['user_csr']);
		}

		if (!is_null($csr)) {
			$subject = openssl_csr_get_subject($csr, true);
			$authvar = substr(pubkey_hash($csr, true), 0, ConfusaConstants::$AUTH_KEY_LENGTH);
			if (is_null($authvar) || $authvar == "") {
				Framework::error_output("Problems with CSR. Please create a new CSR and try again.");
				return;
			}
			/* is the CSR already uploaded? */
			$res = MDB2Wrapper::execute("SELECT auth_key, from_ip FROM csr_cache WHERE auth_key=?",
						    array('text'),
						    array($authvar));
			if (count($res)>0) {
				Framework::error_output("CSR with matching public-key already in the database. ".
							"Cannot upload this CSR. Please generate a new keypair and try again.");
				/* match the DN only when using standalone CA, no need to do it for Comodo */
			} else if (Config::get_config('ca_mode') == CA_COMODO ||
				   match_dn($subject, $this->person)) {
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
		}
	} /* end processUploadedCSR() */

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
			$res = delete_csr_from_db($this->person, Input::sanitizeCertKey($_GET['delete_csr']));
			if ($res) {
				Framework::message_output("Successfully deleted CSR for user " . htmlentities($this->person->getEPPN()) . ".");
			} else {
				Framework::error_output("Could not delete CSR.");
			}
		}
		elseif (isset($_GET['inspect_csr'])) {
			try {
				$this->tpl->assign('csrInspect', get_csr_details($this->person,
				                   Input::sanitizeCertKey($_GET['inspect_csr'])));
				$res = true;
			} catch (CSRNotFoundException $csrnfe) {
				$msg  = "Error with auth-token (" . htmlentities($auth_key) . ") - not found. ";
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
	} /* end processDBCSR() */

	private function approveBrowserGenerated($csr, $browser)
	{
		$permission = $this->person->mayRequestCertificate();

		if ($permission->isPermissionGranted() === false) {
			Framework::error_output("You may not request a new certificate, because:<br /><br />" .
							$permission->getFormattedReasons() .
							"<br />Please contact an IT-administrator about that!");
			return;
		}

		$order_number = $this->ca->signBrowserCSR($csr, $browser);
		return $order_number;
	}

	/**
	 * approveCsr - send the CSR to cert-manager for signing
	 *
	 * This function approves a CSR for signing. It uses the auth-token as a
	 * paramenter to find the CSR in the database coupled with the valid CN for the
	 * user.
	 *
	 * @param String $authToken the unique id of the CSR the user wants to
	 * approve for signing.
	 *
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
			Framework::error_output("Did not find CSR with auth_token " . htmlentities($auth_token));
			$msg  = "User " . $this->person->getEPPN() . " ";
			$msg .= "tried to delete CSR with auth_token " . $authToken . " but was unsuccessful";
			Logger::log_event(LOG_NOTICE, $msg);
			return false;
		}

		try {
			if (!isset($this->ca)) {
				Framework::error_output("CA is NULL!");
				return false;
			}

			$permission = $this->person->mayRequestCertificate();

			if ($permission->isPermissionGranted() === false) {
				Framework::error_output("You may not request a new certificate, because:<br /><br />" .
							$permission->getFormattedReasons() .
							"<br />Please contact an IT-administrator about that!");
				return;
			}

			$this->ca->signKey($authToken, $csr);

		} catch (CGE_ComodoAPIException $capie) {
			Framework::error_output("Error with remote API when trying to ship CSR for signing.<BR />\n" . htmlentities($capie));
			return false;
		} catch (ConfusaGenException $e) {
			$msg = "Error signing key, remote said: <br /><br /><i>" . htmlentities($e->getMessage()) . "</i><br />";
			Framework::error_output($msg);
			return false;
		} catch (KeySigningException $kse) {
			Framework::error_output("Could not sign certificate. Server said: " . htmlentites($kse->getMessage()));
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

	/**
	 * Show the right template for the browser of the user
	 */
	private function dispatchBrowserTemplate()
	{
		$ua = getUserAgent();

		switch($ua) {
		case "msie_post_vista":
			return "browser_csr/vista7.tpl";
			break;
		case "msie_pre_vista":
			return "browser_csr/xp2003.tpl";
			break;
		case "keygen":
			return "browser_csr/keygen.tpl";
			break;
		case "other":
			return "browser_csr/unsupported.tpl";
			break;
		default:
			return "browser_csr/unsupported.tpl";
			break;
		}
	}

}

$fw = new Framework(new CP_ProcessCsr());
$fw->start();

?>
