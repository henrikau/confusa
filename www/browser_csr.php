<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'confusa_constants.php';
require_once 'Output.php';
require_once 'CSR_SPKAC.php';
require_once 'CSR_PKCS10.php';

final class CP_Browser_CSR extends Content_Page
{
	function __construct()
	{
		parent::__construct("Browser CSR", true, "processcsr");
		Framework::sensitive_action();
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		$this->tpl->assign('extraScripts', array('js/jquery-1.4.1.min.js'));
		$this->tpl->assign('rawScript', file_get_contents('../include/rawToggleExpand.js'));

		/* FIXME: more security checks */
		if (CS::getSessionKey('hasAcceptedAUP') !== true) {
			return;
		}

		if (isset($_GET['status_poll'])) {
				$order_number = Input::sanitizeCertKey($_GET['status_poll']);
				/* assign the order_number again */
				$this->tpl->assign('order_number', $order_number);
				$this->tpl->assign('status_poll', true);
				$this->tpl->assign('ganticsrf', "anticsrf=".Input::sanitizeAntiCSRFToken($_GET['anticsrf']));
				if ($this->ca->pollCertStatus($order_number)) {
					/* redirect to certificate download area */
					header("Location: download_certificate.php");
				}
		}

		/* when the key has been generated in the browser and the
		 * resulting CSR has been uploaded to the server, we end up
		 * here.
		 */
		if (isset($_POST['browserRequest'])) {
			$ua = Output::getUserAgent();

			switch($ua) {
			case "opera":
			case "safari":
			case "mozilla":
			case "chrome":
				$csr = new CSR_SPKAC(trim(Input::sanitizeBase64($_POST['browserRequest'])));
				break;
			case "msie_pre_vista":
			case "msie_post_vista":
				$csrContent = CSR::$PEM_PREFIX . "\n" .
				              trim(Input::sanitizeBase64($_POST['browserRequest'])) . "\n" .
				              CSR::$PEM_SUFFIX;
				$csr = new CSR_PKCS10($csrContent);
				break;
			}

			if (!empty($csr)) {
				try {
					$order_number = $this->signCSR($csr);
					$this->tpl->assign('order_number', $order_number);
				} catch (KeySignException $kse) {
					Framework::error_output($this->translateTag('l10n_sign_error', 'processcsr')
								."<br /><br />".$kse->getMessage());
					Logger::logEvent(LOG_WARNING, "CP_Browser_CSR", "pre_process()",
					                 "Could not sign CSR because of " . $kse->getMessage() .
					                 " User: " . $this->person->getEPPN(),
					                 __LINE__);
					unset($_POST['browserSigning']);

				}
			} else {
				Framework::error_output("Could not parse CSR from browser output!");
				Logger::logEvent(LOG_NOTICE, "CP_Browser_CSR", "pre_process()",
				                 "Received browser-CSR that could not be parsed!" .
				                 " User: " . $this->person->getEPPN(),
				                 __LINE__);
			}
		}
	}

	public function process()
	{
		$user_cert_enabled = $this->person->testEntitlementAttribute(Config::get_config('entitlement_user'));
		$this->tpl->assign('user_cert_enabled', $user_cert_enabled);
		$this->tpl->assign('finalDN',   $this->ca->getFullDN());
		$browser_adapted_dn = $this->ca->getBrowserFriendlyDN();
		$this->tpl->assign('dn',				$browser_adapted_dn);
		$this->tpl->assign('default_keysize',	Config::get_config('default_key_length'));
		$this->tpl->assign('min_keysize',		Config::get_config('min_key_length'));

		$ua = Output::getUserAgent();

		switch($ua) {
		case "msie_post_vista":
			$this->tpl->assign('content', $this->tpl->fetch('browser_csr/vista7.tpl'));
			break;
		case "msie_pre_vista":
			$this->tpl->assign('content', $this->tpl->fetch('browser_csr/xp2003.tpl'));
			break;
		case "chrome":
		case "safari":
		case "opera":
		case "mozilla":
			$this->tpl->assign('content', $this->tpl->fetch('browser_csr/keygen.tpl'));
			break;
		case "other":
			$this->tpl->assign('content', $this->tpl->fetch('browser_csr/unsupported.tpl'));
			break;
		default:
			$this->tpl->assign('content', $this->tpl->fetch('browser_csr/unsupported.tpl'));
			break;
		}
	}

	/**
	 * signCSR()
	 *
	 * The function ships a CSR generated in the browser for
	 * signing.
	 *
	 * @param	SPKAC
	 * @return	Ordernumber|false
	 * @access	private
	 */
	private function signCSR($csr)
	{
		$permission = $this->person->mayRequestCertificate();
		if ($permission->isPermissionGranted() === false) {
			Framework::error_output($this->translateTag('l10n_err_noperm1', 'processcsr') .
			                        "<br /><br />" .
			                        $permission->getFormattedReasons() . "<br />" .
			                        $this->translateTag('l10n_err_noperm2', 'processcsr'));
			return false;
		}
		$order_number = $this->ca->signKey($csr);
		return $order_number;
	} /* end approveBrowserGenerated() */
}

$fw = new Framework(new CP_Browser_CSR());
$fw->start();
?>