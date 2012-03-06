<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'CSR_PKCS10.php';
require_once 'CSRUpload.php';

/**
 * Class for processing uploaded/pasted CSR files
 * @author tzangerl
 * @since v0.7-rc0
 *
 */
final class CP_Upload_CSR extends Content_Page
{
	/** CSR object - populated after successful CSR upload */
	private $csr;
	/** authKey - populated after successful signing operation */
	private $authKey;

	function __construct()
	{
		parent::__construct("Upload CSR", true, "processcsr");
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		$authvar = "";
		$csr = null;

		if (isset($_POST['signCSR'])) {
			$this->signCSR(Input::sanitizeCertKey($_POST['signCSR']));
			return;
		}

		/* Testing for uploaded files */
		if(isset($_FILES['user_csr']['name'])) {
			try {
				$csr = CSRUpload::receiveUploadedCSR('user_csr', true);
			} catch (FileException $fileEx) {
				$msg  = $this->translateTag('l10n_err_csrproc', 'processcsr');
				Framework::error_output($msg . $fileEx->getMessage());
				$this->csr = null;
				return;
			}
		} else if (isset($_POST['user_csr'])) {
			try {
				$csr = CSRUPload::receivePastedCSR('user_csr');
			} catch 
			
		}

		if (!$csr->isValid()) {
			$msg = $this->translateTag('l10n_err_csrinvalid1', 'processcsr');
			$msg .= Config::get_config('min_key_length');
			$msg .= $this->translateTag('l10n_err_csrinvalid2', 'processcsr');
			Framework::error_output($msg);
			$this->csr = null;
			return;
		}

		if (Config::get_config('ca_mode') == CA_COMODO ||
			match_dn($csr->getSubject(), $this->ca->getFullDN())) {

			$csr->setUploadedDate(date("Y-m-d H:i:s"));
			$csr->setUploadedFromIP($_SERVER['REMOTE_ADDR']);
			$csr->storeDB($this->person);
			$this->csr = $csr;
		}
	}

	public function process()
	{
		if (CS::getSessionKey('hasAcceptedAUP') !== true) {
			Framework::error_output($this->translateTag("l10n_err_aupagreement",
				"processcsr"));
			return;
		}

		if (isset($this->authKey)) {
			/* redirect the user to the certificate download page */
			header("Location: download_certificate.php");
			exit(0);
		} else if (isset($this->csr)) {
			$this->tpl->assign('csrInspect', true);
			$this->tpl->assign('subject', $this->csr->getSubject());
			$this->tpl->assign('uploadedDate', $this->csr->getUploadedDate());
			$this->tpl->assign('uploadedFromIP', $this->csr->getUploadedFromIP());
			$this->tpl->assign('authToken', $this->csr->getAuthToken());
			$this->tpl->assign('length', $this->csr->getLength());
			$this->tpl->assign('legendTitle',
			                   $this->translateTag('l10n_legend_pastedcsr', 'processcsr'));
			$this->tpl->assign('finalDN',   $this->ca->getFullDN());
			$this->tpl->assign('content', $this->tpl->fetch('upload_csr.tpl'));
		} else {
			Framework::error_output($this->translateTag('l10n_err_procuploaded',
				'processcsr'));
		}
	}

	/**
	 * Sign the CSR with the passed authToken. If signing succeeds, the class
	 * member authKey is set to the orderNumber/certHash. If not, an error is
	 * displayer
	 * @param $authToken pubkey hash of the CSR that is to be signed
	 */
	private function signCSR($authToken) {
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
				Framework::error_output($this->translateTag('l10n_err_noca',
					'processcsr'));
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

			$this->authKey = $this->ca->signKey($csr);

		} catch (CGE_ComodoAPIException $capie) {
			Framework::error_output($this->translateTag('l10n_sign_error', 'processcsr') .
						htmlentities($capie));
			return false;
		} catch (ConfusaGenException $e) {
			$msg = $this->translateTag('l10n_sign_error', 'processcsr') . "<br /><br /><i>" .
				htmlentities($e->getMessage()) . "</i><br />";
			Framework::error_output($msg);
			return false;
		} catch (KeySigningException $kse) {
			Framework::error_output($this->translateTag('l10n_sign_error', 'processcsr') .
						htmlentites($kse->getMessage()));
			return false;
		}
		CSR::deleteFromDB($this->person, $authToken);
	}
}

$fw = new Framework(new CP_Upload_CSR());
$fw->start();
?>