<?php
require_once 'confusa_include.php';
include_once 'framework.php';
class CP_Root_Certificate extends Content_Page
{
	/* The local (filesystem) path to the CA-certificate*/
	private $cert_path;
	/* The local path to the CRL*/
	private $crl_path;
	/* The URL to the CA-certificate */
	private $cert_url;
	/* The URL to the CRL */
	private $crl_url;

	function __construct()
	{
		parent::__construct("Root Certificate(s)", false);

		if (Config::get_config('ca_mode') == CA_COMODO) {
			$this->cert_path = tempnam("/tmp/", "tcs-ca.pem.");
			$this->crl_path = tempnam("/tmp/", "tcs-crl.crl.");

			$this->cert_url = ConfusaConstants::$CAPI_ROOT_CERT;
			$this->crl_url = ConfusaConstants::$CAPI_CRL;
		} else {
			$this->cert_path = Config::get_config('install_path') .
								Config::get_config('ca_cert_base_path') .
								Config::get_config('ca_cert_path') .
								Config::get_config('ca_cert_name');
			$this->crl_path = ConfusaConstants::$OPENSSL_CRL_FILE;

			$this->cert_url = "?link=cacert";
			$this->crl_url = "?link=crl";
		}
	}

	function __destruct()
	{
		parent::__destruct();
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		if (isset($_GET['send_file'])) {
			include_once 'file_download.php';
			switch(htmlentities($_GET['send_file'])) {
			case 'cacert':
				$this->makeCertAvailable();
				download_file(file_get_contents($this->cert_path), "confusa_cert.pem");
				break;
			case 'crl':
				$this->makeCRLAvailable();
				download_file(file_get_contents($this->crl_path), "confusa.crl");
				break;
			default:
				return;
			}
			exit(1);
		} else if (isset($_GET['link']) && file_exists($this->cert_path)) {
			switch(htmlentities($_GET['link'])) {
			case 'cacert':
				$cert = file_get_contents($this->cert_path);
				$cert = CA::PEMtoDER($cert, 'cert');
				header("Content-type: application/x-x509-ca-cert");
				// IE fix (for HTTPS only)
				header("Cache-Control: private");
				header("Pragma: private");
				header("Content-Length: " . strlen($cert));
				header("Content-Disposition: inline; filename=confusa.pem");
				echo $cert;
				exit(0);
				break;
			case 'crl':
				$crl = file_get_contents($this->crl_path);
				$crl = CA::PEMtoDER($crl, 'crl');
				// IE fix (for HTTPS only)
				header("Cache-Control: private");
				header("Pragma: private");
				header("Content-type: application/x-pkcs7-crl");
				header("Content-Length: " . strlen($crl));
				header("Content-Disposition: inline; filename=confusa.crl");
				echo $crl;
				exit(0);
				break;
			}
		}

		return false;
	}
	public function process()
	{
		if (isset($_GET['show_root_cert'])) {
			$this->makeCertAvailable();
			$ca_file_content = file_get_contents($this->cert_path);
			openssl_x509_export($ca_file_content, $tmp, false);
			$this->tpl->assign('ca_dump', $tmp);
		}

		if (isset($_GET['show_crl'])) {
			$this->makeCRLAvailable();
			$crl_content = file_get_contents($this->crl_path);
			$crl_dump = openssl_crl_export($crl_content);
			$this->tpl->assign('crl_dump', $crl_dump);
		}

		$this->tpl->assign('ca_download_link', $this->cert_url);
		$this->tpl->assign('crl_download_link', $this->crl_url);
		$this->tpl->assign('content', $this->tpl->fetch('root_cert.tpl'));
	}

	/**
	 * JIT-download the CRL and provision it at the path defined in crl_path
	 *
	 * Doesn't cause too much overhead (even though the file is written, then
	 * read again) and leaves the code somewhat intact and does not cause too
	 * much obfuscation.
	 *
	 */
	private function makeCRLAvailable()
	{
		if(Config::get_config('ca_mode') == CA_COMODO) {
			$ch = curl_init(ConfusaConstants::$CAPI_CRL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			$crl_content = curl_exec($ch);
			curl_close($ch);
			/* get the right encoding */
			$crl_file = CA::DERtoPEM($crl_content, 'crl');
			file_put_contents($this->crl_path, $crl_file);
		}
	}

	/**
	 * Provision the certificate at cert_path
	 *
	 * @see makeCRLAvailable
	 */
	private function makeCertAvailable()
	{
		if(Config::get_config('ca_mode') == CA_COMODO) {
			$ch = curl_init(ConfusaConstants::$CAPI_ROOT_CERT);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			$ca_file_content = curl_exec($ch);
			curl_close($ch);
			$cert_file = CA::DERtoPEM($ca_file_content, 'cert');
			file_put_contents($this->cert_path, $cert_file);
		}
	}
}
$fw = new Framework(new CP_Root_Certificate());
$fw->start();
?>
