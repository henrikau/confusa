<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'confusa_constants.php';

/**
 * Class that displays a number of possibilities for CSR creation to the
 * user (browser generation, upload, pasting) and provides the corresponding
 * templates
 * @author tzangerl
 * @since v0.7-rc0
 *
 */
final class CP_Receive_CSR extends Content_Page
{
	function __construct()
	{
		parent::__construct("Receive CSR", true, "processcsr");
		Framework::sensitive_action();
	}

	function pre_process($person)
	{
		parent::pre_process($person);

		/* can be received when pressing "Back" on the CSR-signing overview */
		if (isset($_POST['deleteCSR'])) {
			$authToken = Input::sanitizeCertKey($_POST['deleteCSR']);
			CSR::deleteFromDB($person, $authToken);
		}

		$this->tpl->assign('extraScripts', array('js/jquery-1.4.1.min.js'));
		$this->tpl->assign('rawScript', file_get_contents('../include/rawToggleExpand.js'));

		if (isset($_POST['subjAltName_email']) &&
		    is_array($_POST['subjAltName_email'])) {

			foreach($_POST['subjAltName_email'] as $key => $value) {
				Logger::logEvent(LOG_INFO, "CP_Select_Email", "pre_process()",
				                 "User " . $this->person->getEPPN() . ", registering " .
				                 "the following e-mail: " . $value);
				$this->person->regCertEmail(Input::sanitizeText($value));
			}

			$this->person->storeRegCertEmails();
		}
	}

	function process()
	{
		if (CS::getSessionKey('hasAcceptedAUP') !== true) {
			Framework::error_output($this->translateTag("l10n_err_aupagreement",
				"processcsr"));
			return;
		}

		if (isset($_GET['show'])) {
			switch($_GET['show']) {
			case 'upload_csr':
				/* FIXME: constants */
				$this->tpl->assign('nextScript', 'upload_csr.php');
				$this->tpl->assign('upload_csr', true);
				break;
			case 'paste_csr':
				$this->tpl->assign('nextScript', 'upload_csr.php');
				$this->tpl->assign('paste_csr', true);
				break;
			default:
				$this->tpl->assign('nextScript', 'browser_csr.php');
				$this->tpl->assign('browser_csr', true);
				break;
			}
		} else {
			$this->tpl->assign('nextScript', 'browser_csr.php');
			$this->tpl->assign('browser_csr', true);
		}

		$user_cert_enabled = $this->person->testEntitlementAttribute(Config::get_config('entitlement_user'));
		$this->tpl->assign('user_cert_enabled', $user_cert_enabled);
		$this->tpl->assign('content', $this->tpl->fetch('receive_csr.tpl'));
	}

}

$fw = new Framework(new CP_Receive_CSR());
$fw->start();
?>