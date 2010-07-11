<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'MDB2Wrapper.php';
require_once 'Logger.php';
require_once 'csr_lib.php';
require_once 'file_upload.php';
require_once 'Config.php';
require_once 'send_element.php';
require_once 'Input.php';
require_once 'Output.php';
require_once 'Permission.php';
require_once 'CS.php';

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
		parent::__construct("Process CSR", true, "processcsr");
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

		$subscriber = $this->person->getSubscriber();
		/* Test subscriber-status: */
		if (empty($subscriber) || !$subscriber->isSubscribed()) {
			return false;
		}

		/* has user accepted the AUP? */
		$this->aup_set = false;

		if (array_key_exists('aup_box', $_POST) &&
		    isset($_POST['aup_box']) &&
		    Input::sanitize($_POST['aup_box']) == "user_agreed") {
			$this->aup_set = true;
			CS::setSessionKey('aup_box', 'yes');
		} else {
			/* should the session aup_box be unset for some reason? */
			/* browser signing, paste and upload require the box to
			 * be ticked. If those present and box not set, reset agreement */
			$browserSigningMask = isset($_POST['browserSigning']) &&
			                      ($_POST['browserSigning'] == 'start');
			if ($browserSigningMask ||
			    isset($_POST['pastedCSR']) ||
			    isset($_POST['uploadedCSR'])) {
				CS::deleteSessionKey('aup_box');
				$this->aup_set = false;
			} else {
				$this->aup_set = CS::getSessionKey('aup_box') == 'yes';
			}
		}

		if (isset($_GET['sign_csr']) && $this->aup_set) {
			try {
				$res = $this->approveCsr(Input::sanitizeBase64($_GET['sign_csr']));
			} catch (KeySignException $kse) {
				Framework::error_output($this->translateTag('l10n_sign_error', 'processcsr')
							."<br /><br />".$kse->getMessage());
			}

		} else if (isset($_GET['status_poll'])) {
			$order_number = Input::sanitizeCertKey($_GET['status_poll']);
			/* assign the order_number again */
			$this->tpl->assign('order_number', $order_number);
			$this->tpl->assign('status_poll', true);
			$this->tpl->assign('ganticsrf', "anticsrf=".Input::sanitizeAntiCSRFToken($_GET['anticsrf']));
			if ($this->ca->pollCertStatus($order_number)) {
			    $this->tpl->assign('done', TRUE);
			}

		} else if (isset($_GET['install_cert'])) {
			$order_number = Input::sanitizeCertKey($_GET['install_cert']);
			$ua = Output::getUserAgent();
			$script = $this->ca->getCertDeploymentScript($order_number, $ua);

			switch($ua) {
			case "opera":
			case "safari":
			case "mozilla":
			case "chrome":
			    include_once 'file_download.php';
			    download_certificate($script, "install.crt");
			    exit(0);
				break;
			default:
				$this->tpl->assign('deployment_script', $script);
				break;
			}
		}

		/* when the key has been generated in the browser and the
		 * resulting CSR has been uploaded to the server, we end up
		 * here.
		 */
		if (isset($_POST['browserRequest']) && $this->aup_set) {
			$request = Input::sanitizeBase64($_POST['browserRequest']);
			$request = trim($request);
			if (!empty($request)) {
				try {
					$order_number = $this->approveBrowserGenerated($request, Output::getUserAgent());
					$this->tpl->assign('order_number', $order_number);
				} catch (KeySignException $kse) {
					Framework::error_output($this->translateTag('l10n_sign_error', 'processcsr')
								."<br /><br />".$kse->getMessage());
					unset($_POST['browserSigning']);
				}
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

		if (Config::get_config('cert_product') == PRD_ESCIENCE) {
			$this->tpl->assign('cps', ConfusaConstants::$LINK_ESCIENCE_CPS);
		} else {
			$this->tpl->assign('cps', ConfusaConstants::$LINK_PERSONAL_CPS);
		}

		$this->processDBCsr();
		/* Set default-values to false to avoid warnings */
		$this->tpl->assign('approve_csr', false);
		$this->tpl->assign('browser_csr', false);
		$this->tpl->assign('upload_csr',  false);
		$this->tpl->assign('paste_csr',   false);
		$this->tpl->assign('aup_box_checked', $this->aup_set);
		$this->tpl->assign('privacy_notice_text', $this->person->getNREN()->getPrivacyNotice($this->person));
		$this->tpl->assign('finalDN',   $this->ca->getFullDN());

		/* signing finished, redirect to download */
		if($this->signing_ok) {
			$this->tpl->assign('signingOk', $this->signing_ok);
			$this->tpl->assign('sign_csr',  Input::sanitizeBase64($_GET['sign_csr']));
			$this->tpl->assign('approve_csr.tpl', true);
			return;
		}

		/* if email is set, add to person to get only the required
		 * emails. Since the only setting that won't allow anything to
		 * be stored is '0', we mask this out.
		 *
		 * The database will only allow valid entiries into the
		 * enum-field, so only '1', 'n' and 'm' can get through.
		 */
		$ece = $this->person->getNREN()->getEnableEmail();
		if ($ece != '0') {
			if (array_key_exists('subjAltName_email', $_POST)	&&
			    isset($_POST['subjAltName_email'])			&&
			    is_array($_POST['subjAltName_email'])		&&
			    $this->aup_set)	{
				foreach($_POST['subjAltName_email'] as $key => $value) {
					$this->person->regCertEmail(Input::sanitizeText($value));
				}
				$this->person->storeRegCertEmails();
			}
		}
		/* set the browser signing variables only if browser signing is enabled */
		/* browser-signing.
		 *
		 * This is where the user ends up after pressing 'Apply' in the
		 * browser-gen section. The user will now have to decide upon
		 * keylength and then generate the private key and return the CSR
		 */
		if ((isset($_POST['browserSigning']) || isset($_GET['status_poll'])) &&
		    $this->aup_set) {
			$browser_adapted_dn = $this->ca->getBrowserFriendlyDN();
			$this->tpl->assign('dn',				$browser_adapted_dn);
			$this->tpl->assign('keysize',			Config::get_config('key_length'));
			$browserTemplate = $this->dispatchBrowserTemplate();
			Framework::message_output($this->translateTag('l10n_msg_browsergen', 'processcsr') .
			                          " <a href=\"process_csr.php\">" .
			                          $this->translateTag('l10n_link_change', 'processcsr') .
			                          "</a>.");

			if (Config::get_config('cert_product') == PRD_PERSONAL) {
				$this->tpl->assign('ca_certificate',
				                   ConfusaConstants::$CAPI_PERSONAL_ROOT_CERT);
			}

			$this->tpl->assign('content',	$this->tpl->fetch($browserTemplate));
			return;
		/* signing of a copied/pasted CSR */
		} else if (isset($_POST['pastedCSR']) && $this->aup_set) {
			/* show upload-form. If it returns false, no uploaded CSRs were processed */
			$authkey = $this->processUploadedCSR($this->person);
			Framework::message_output($this->translateTag('l10n_msg_pastecert', 'processcsr') .
			                          "<a href=\"process_csr.php\">" .
			                          $this->translateTag('l10n_link_change', 'processcsr') .
			                          "</a>.");
			$this->tpl->assign('post', 'pastedCSR');

			$this->tpl->assign('csrInspect', get_csr_details($this->person,
			                                 $authkey));

			$this->tpl->assign('legendTitle',
			                   $this->translateTag('l10n_legend_pastedcsr', 'processcsr'));
			$this->tpl->assign('content',	$this->tpl->fetch('csr/approve_csr.tpl'));
			return;
		/* signing of a CSR that was uploaded from a file */
		} else if (isset($_POST['uploadedCSR']) && $this->aup_set) {
			/* show upload-form. If it returns false, no uploaded CSRs were processed */
			$authkey = $this->processUploadedCSR($this->person);
			Framework::message_output($this->translateTag('l10n_msg_uploadcsr', 'processcsr') .
			                          " <a href=\"process_csr.php\">" .
									  $this->translateTag('l10n_link_change', 'processcsr') .
									  "</a>.");
			$this->tpl->assign('post', 'uploadedCSR');

			$this->tpl->assign('csrInspect', get_csr_details($this->person,
			                                 $authkey));
			$this->tpl->assign('legendTitle',
			                   $this->translateTag('l10n_legend_uploadedcsr', 'processcsr'));
			$this->tpl->assign('content',	$this->tpl->fetch('csr/approve_csr.tpl'));
			return;
		}

		/* showing the normal UI */
		$user_cert_enabled = $this->person->testEntitlementAttribute(Config::get_config('entitlement_user'));
		$this->tpl->assign('user_cert_enabled', $user_cert_enabled);

		/* decide which page to view */
		if (array_key_exists('show', $_GET) &&  !is_null($_GET['show'])) {
			switch (htmlentities($_GET['show'])) {
			case 'browser_csr':
				$this->tpl->assign('browser_csr', true);
				break;
			case 'upload_csr':
				$this->tpl->assign('upload_csr', true);
				break;
			case 'paste_csr':
				$this->tpl->assign('paste_csr', true);
				break;
			default:
				$this->tpl->assign('browser_csr', true);
				break;
			}
		} else {
			$this->tpl->assign('browser_csr', true);
		}
		$this->tpl->assign('email_status',
				   $this->person->getNREN()->getEnableEmail());

		$this->tpl->assign('content',	$this->tpl->fetch('csr/process_csr.tpl'));
	}

	/**
	 * processUploadedCSR - walk an uploaded CSR through the steps towards a certificate
	 *
	 * If a new CSR has been uploaded via FILE, this will retrieve it, store it in
	 * the database and pass control over to the CA to process it.
	 */
	private function processUploadedCSR()
	{
		$authvar = "";
		$csr = null;
		/* Testing for uploaded files */
		if(isset($_FILES['user_csr']['name'])) {
			$fu = new FileUpload('user_csr', true, true, 'test_content');
			if ($fu->file_ok()) {
				$csr = new CSR($fu->get_content());
			} else {
				/* File NOT OK */
				$msg  = $this->translateTag('l10n_err_csrproc', 'processcsr');
				Framework::error_output($msg);
			}
		} else if (isset($_POST['user_csr'])) {
			$csr = new CSR(Input::sanitizeBase64($_POST['user_csr']));
		}

		if (Config::get_config('ca_mode') == CA_COMODO ||
		    match_dn($csr->getsubject(), $this->ca->getFullDN())) {
			if ($csr->storeDB()) {
				return $csr->getAuthToken();
			}
		}
		return false;
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
		if (isset($_GET['delete_csr'])) {
			if (CSR::deleteFromDB($this->person, Input::sanitizeCertKey($_GET['delete_csr']))) {
				Framework::message_output($this->translateTag('l10n_suc_delcsr', 'processcsr') .
				                          htmlentities($this->person->getEPPN()) . ".");
			} else {
				Framework::error_output("Could not delete CSR.");
			}
		} elseif (isset($_GET['inspect_csr'])) {
			try {
				$csr = CSR::getFromDB($this->person->getX509ValidCN(),
						      Input::sanitizeCertKey($_GET['inspect_csr']));
				$res = array(
					'auth_token'	=> $csr->getAuthToken(),
					'length'	=> $csr->getLength(),
					'uploaded'	=> $csr->getUploadedDate(),
					'from_ip'	=> $csr->getUploadeFromIP()
					);

			} catch (CSRNotFoundException $csrnfe) {
				$msg  = "Error with auth-token (" . htmlentities($auth_key) . ") - not found. ";
				$msg .= "Please verify that you have entered the correct auth-url and try again.";
				$msg .= "If this problem persists, try to upload a new CSR and inspect the fields carefully";
				Framework::error_output($msg);
				return;
			} catch (ConfusaGenException $cge) {
				$msg = "Too menu returns received. This can indicate database inconsistency.";
				Framework::error_output($msg);
				Logger::logEvent(LOG_ALERT, "Process_CSR", "processDBCSR()",
				                 "Several identical CSRs (" .
				                 $auth_token . ") exists in the database for user " .
				                 $this->person->getX509ValidCN(), __LINE__);
				return;
			}
		}
	} /* end processDBCSR() */

	private function approveBrowserGenerated($csr)
	{
		$permission = $this->person->mayRequestCertificate();
		if ($permission->isPermissionGranted() === false) {
			Framework::error_output($this->translateTag('l10n_err_noperm1', 'processcsr') .
			                        "<br /><br />" .
			                        $permission->getFormattedReasons() . "<br />" .
			                        $this->translateTag('l10n_err_noperm2', 'processcsr'));
			return;
		}
		$order_number = $this->ca->signBrowserCSR($csr->getPEMContent(),
							  Output::getUserAgent());
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
		$csr = CSR::getFromDB($this->person->getX509ValidCN(), $authToken);
		if (!isset($csr) || !$csr) {
			$errorTag = PW::create();
			Framework::error_output("[$errorTag] Did not find CSR with auth_token " .
						htmlentities($auth_token));
			$msg  = "User " . $this->person->getEPPN() . " ";
			$msg .= "tried to delete CSR with auth_token " . $authToken . " but was unsuccessful";
			Logger::logEvent(LOG_NOTICE, "Process_CSR", "approveCSR($authToken)",
			                 $msg, __LINE__, $errorTag);
			return false;
		}

		try {
			if (!isset($this->ca)) {
				Framework::error_output("No available CA, cannot contine signing the CSR.");
				return false;
			}

			$permission = $this->person->mayRequestCertificate();
			if ($permission->isPermissionGranted() === false) {
				Framework::error_output($this->translateTag('l10n_err_noperm1', 'processcsr') .
				                        "<br /><br />" .
				                        $permission->getFormattedReasons() . "<br />" .
				                        $this->translateTag('l10n_err_noperm2', 'processcsr'));
				return;
			}

			$this->ca->signKey($authToken, $csr);

		} catch (CGE_ComodoAPIException $capie) {
			Framework::error_output("Error with remote API when trying to ship CSR for signing.<BR />\n" .
						htmlentities($capie));
			return false;
		} catch (ConfusaGenException $e) {
			$msg = "Error signing key, remote said: <br /><br /><i>" .
				htmlentities($e->getMessage()) . "</i><br />";
			Framework::error_output($msg);
			return false;
		} catch (KeySigningException $kse) {
			Framework::error_output("Could not sign certificate. Server said: " .
						htmlentites($kse->getMessage()));
			return false;
		}
		delete_csr_from_db($this->person, $authToken);
		$this->signing_ok = true;

		/* Construct a meta http-equiv to be included in the
		 * <HEAD>-section in framework. This is to provide a
		 * auto-refrech for the user, resulting in a cleaner user
		 * experience. */
		$url = "https://";
		$url .= $_SERVER['HTTP_HOST'] . "/" . dirname($_SERVER['PHP_SELF']) .
			"/download_certificate.php?poll=$authToken" .
			"&amp;anticsrf=" . Framework::getAntiCSRF();
		return "<META HTTP-EQUIV=\"REFRESH\" content=\"3; url=$url\">\n";
	} /* end approveCSR() */


	/**
	 * Show the right template for the browser of the user
	 */
	private function dispatchBrowserTemplate()
	{
		$ua = Output::getUserAgent();

		switch($ua) {
		case "msie_post_vista":
			return "browser_csr/vista7.tpl";
			break;
		case "msie_pre_vista":
			return "browser_csr/xp2003.tpl";
			break;
		case "chrome":
		case "safari":
		case "opera":
		case "mozilla":
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
