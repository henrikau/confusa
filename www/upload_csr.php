<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'CSR_PKCS10.php';

/**
 * Class for processing uploaded/pasted CSR files
 * @author tzangerl
 * @since v0.7-rc0
 *
 */
final class CP_Upload_CSR extends Content_Page
{
	private $csr;

	function __construct()
	{
		parent::__construct("Upload CSR", true, "processcsr");
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		$authvar = "";
		$csr = null;
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
			$csr = new CSR_PKCS10(Input::sanitizeBase64($_POST['user_csr']));
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
		if (is_null($this->csr)) {
			Framework::error_output("Processing of the uploaded/pasted CSR failed.");
		} else {
			$this->tpl->assign('csrInspect', true);
			$this->tpl->assign('subject', $this->csr->getSubject());
			$this->tpl->assign('uploadedDate', $this->csr->getUploadedDate());
			$this->tpl->assign('uploadedFromIP', $this->csr->getUploadedFromIP());
			$this->tpl->assign('authToken', $this->csr->getAuthToken());
			$this->tpl->assign('length', $this->csr->getLength());
			$this->tpl->assign('legendTitle',
			                   $this->translateTag('l10n_legend_pastedcsr', 'processcsr'));
			$this->tpl->assign('content', $this->tpl->fetch('csr/approve_csr.tpl'));
		}
	}
}

$fw = new Framework(new CP_Upload_CSR());
$fw->start();
?>