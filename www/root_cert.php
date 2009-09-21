<?php
require_once 'confusa_include.php';
include_once 'framework.php';
class CP_Root_Certificate extends Content_Page
{
	/* The local (filesystem) path to the CA-certificate*/
	private $cert_path;
	/* The local path to the CRL*/
	private $crl_path;
	/* A web URL to the CA-certificate */
	private $cert_url;
	/* A web URL to the CRL */
	private $crl_url;

	function __construct()
	{
		parent::__construct("Root Certificate(s)", false);

		switch(Config::get_config('ca_mode')) {

			case CA_STANDALONE:
				$this->cert_path = Config::get_config('install_path') .
									Config::get_config('ca_cert_base_path') .
									Config::get_config('ca_cert_path') .
									Config::get_config('ca_cert_name');
				$this->crl_path = Config::get_config('install_path') . "www/ca" .
									Config::get_config('ca_crl_name');
				$this->cert_url = 'ca/' . Config::get_config('ca_cert_name');
				$this->crl_url = 'ca/' . Config::get_config('ca_crl_name');
				break;

			/**
			 * Temporarily store certificate and CRL, so they don't have to be
			 * kept entirely in memory
			 */
			case CA_ONLINE:
				$this->cert_url = Config::get_config('capi_root_cert');
				$this->crl_url = Config::get_config('capi_crl');
				$this->cert_path = "/tmp/cert.pem";
				$this->crl_path = "/tmp/comodo.crl";
				break;

			default:
				exit(1);
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
		}
		if (isset($_GET['install_root']) && file_exists($this->cert_path)) {
			$myCert = join("", file($this->cert_path));
			header("Content-Type: application/x-x509-ca-cert");
			print $myCert;
			exit(1);
		}
		return false;
	}
	public function process()
	{
		if ($_GET['show_root_cert']) {
			$this->makeCertAvailable();
			$ca_file_content = file_get_contents($this->cert_path);
			openssl_x509_export($ca_file_content, $tmp, false);
			$this->tpl->assign('ca_dump', $tmp);
		}

		if ($_GET['show_crl']) {
			$this->makeCRLAvailable();
			$crl_content = file_get_contents($this->crl_path);
			$crl_dump = openssl_crl_export($crl_content);
			$this->tpl->assign('crl_dump', $crl_dump);
		}

		$this->tpl->assign('crl_file', $this->crl_url);
		$this->tpl->assign('ca_file', $this->cert_url);
		$this->tpl->assign('content', $this->tpl->fetch('root_cert.tpl'));
	}

	/**
	 * JIT-download the CRL and provision it at the path defined in crl_path
	 *
	 * Doesn't cause too much overhead (even though the file is written, then
	 * read again) and leaves the code somewhat intact and does not cause too
	 * much obfuscation.
	 *
	 * TODO: Maybe the CRL in standalone can be JIT-copied from cert_handle to
	 * www/ca if it's not yet available there?
	 */
	private function makeCRLAvailable()
	{
		if(Config::get_config('ca_mode') == CA_ONLINE) {
			$ch = curl_init($this->crl_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			$crl_content = curl_exec($ch);
			curl_close($ch);
			/* get the right encoding */
			$crl_file = CertManager::DERtoPEM($crl_content, 'crl');
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
		if(Config::get_config('ca_mode') == CA_ONLINE) {
			$ch = curl_init($this->cert_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			$ca_file_content = curl_exec($ch);
			curl_close($ch);
			$cert_file = CertManager::DERtoPEM($ca_file_content, 'cert');
			file_put_contents($this->cert_path, $cert_file);
		}
	}
}
$fw = new Framework(new CP_Root_Certificate());
$fw->start();
?>
