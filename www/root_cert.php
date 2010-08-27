<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once('Certificate.php');
include_once 'Framework.php';
require_once('CRL.php');

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
		parent::__construct("Root Certificate(s)", false, "rootcert");

		if (Config::get_config('ca_mode') == CA_COMODO) {
			if (Config::get_config('cert_product') == PRD_ESCIENCE) {
				$this->cert_path = tempnam("/tmp/", "tcs-escience-ca.pem.");
				$this->crl_path = tempnam("/tmp/", "tcs-escience-crl.crl.");

				$this->cert_url = ConfusaConstants::$CAPI_ESCIENCE_ROOT_CERT;
				$this->crl_url = ConfusaConstants::$CAPI_ESCIENCE_CRL;
			} else if (Config::get_config('cert_product') == PRD_PERSONAL) {
				$this->cert_path = tempnam("/tmp/", "tcs-personal-ca.pem.");
				$this->crl_path = tempnam("/tmp/", "tcs-personal-crl.crl.");

				$this->cert_url = ConfusaConstants::$CAPI_PERSONAL_ROOT_CERT;
				$this->crl_url = ConfusaConstants::$CAPI_PERSONAL_CRL;
			}
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
				$cert = new Certificate(file_get_contents($this->cert_path));
				$idx = strrpos($this->cert_url, "/");
				$cert_name = substr($this->cert_url, $idx+1);
				download_file($cert->getPEMContent(), $cert_name);
				break;
			case 'cachain':
				$this->makeChainAvailable();
				$idx = strrpos($this->cert_url, "/");
				$cert_name = substr($this->cert_url, $idx+1);
				$dot_idx = strrpos($cert_name, ".");
				$cert_name = substr($cert_name, 0, $dot_idx);
				$cert_name = $cert_name . '_bundle.pem';
				download_file(file_get_contents($this->cert_path), $cert_name);
				break;
			case 'crl':
				$this->makeCRLAvailable();
				$crl = new CRL(file_get_contents($this->crl_path));
				$idx = strrpos($this->crl_url, "/");
				$crl_name = substr($this->crl_url, $idx+1);
				download_file($crl->getPEMContent(), $crl_name);
				break;
			default:
				return;
			}
			exit(1);
		} else if (isset($_GET['link']) && file_exists($this->cert_path)) {
			switch(htmlentities($_GET['link'])) {
			case 'cacert':
				require_once('Certificate.php');
				$cert = new Certificate(file_get_contents($this->cert_path));
				header("Content-type: application/x-x509-ca-cert");
				// IE fix (for HTTPS only)
				header("Cache-Control: private");
				header("Pragma: private");
				header("Content-Length: " . strlen($cert->getPEMContent()));
				header("Content-Disposition: inline; filename=confusa.pem");
				echo $cert->getPEMContent();
				exit(0);
				break;
			case 'crl':
				$crl = new CRL(file_get_contents($this->crl_path));
				// IE fix (for HTTPS only)
				header("Cache-Control: private");
				header("Pragma: private");
				header("Content-type: application/x-pkcs7-crl");
				header("Content-Length: " . strlen($crl->getPEMContent()));
				header("Content-Disposition: inline; filename=confusa.crl");
				echo $crl->getPEMContent();
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
			$cdata = file_get_contents($this->cert_path);
			$cert = new Certificate($cdata);
			$this->tpl->assign('ca_dump', $cert->getPEMContent(false));
		}

		if (isset($_GET['show_crl'])) {
			$this->makeCRLAvailable();
			$crl = new CRL(file_get_contents($this->crl_path));
			$this->tpl->assign('crl_dump', $crl->getPEMContent(false));
		}

		$this->tpl->assign('ca_download_link',	$this->cert_url);
		$this->tpl->assign('crl_download_link',	$this->crl_url);
		$this->tpl->assign('content',		$this->tpl->fetch('root_cert.tpl'));
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
			$ch = curl_init($this->crl_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			$crl_content = curl_exec($ch);

			$crl = new CRL($crl_content);

			curl_close($ch);
			file_put_contents($this->crl_path, $crl->getPEMContent(true));
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
			$ch = curl_init($this->cert_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			$ca_file_content = curl_exec($ch);

			/* convert from DER to PEM */
			$cert = new Certificate($ca_file_content);

			curl_close($ch);
			file_put_contents($this->cert_path, $cert->getPEMContent(true));
		}
	}

	/**
	 * Provision the whole CA chain (the signing CA cert plus the intermediate
	 * CA cert, plus the root CA).
	 *
	 * @see makeCRLAvailabe
	 */
	private function makeChainAvailable()
	{
		if (Config::get_config('ca_mode') == CA_COMODO) {
			$ch = curl_init(ConfusaConstants::$CAPI_ROOT_CA);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$root_ca_content = curl_exec($ch);
			curl_close($ch);

			$ch = curl_init(ConfusaConstants::$CAPI_INTERMEDIATE_CA);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$interm_ca_content = curl_exec($ch);
			curl_close($ch);

			$ch = curl_init($this->cert_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$actual_ca_cert = curl_exec($ch);
			curl_close($ch);

			/* convert from DER to PEM */
			$cert = new Certificate($actual_ca_cert);

			$ca_chain = $root_ca_content .
			            $interm_ca_content .
			            $cert->getPEMContent(true);

			file_put_contents($this->cert_path, $ca_chain);
		}
	}
}
$fw = new Framework(new CP_Root_Certificate());
$fw->start();
?>
