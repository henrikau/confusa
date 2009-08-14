<?php
require_once 'confusa_include.php';
include_once 'framework.php';
class Root_Certificate extends FW_Content_Page
{
	private $cert_file;
	function __construct()
	{
		parent::__construct("Root Certificate(s)", false);
		$this->cert_file = Config::get_config('install_path') .
			Config::get_config('ca_cert_base_path') .
			Config::get_config('ca_cert_path') .
			Config::get_config('ca_cert_name');
		$this->crl_file = Config::get_config('install_path') . "www/ca" . Config::get_config('ca_crl_name');
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
				download_file(file_get_contents($this->cert_file), Config::get_config('ca_cert_name'));
				break;
			case 'crl':
				download_file(file_get_contents($this->crl_file), Config::get_config('ca_crl_name'));
				break;
			default:
				return;
			}
			exit(1);
		}
		if (isset($_GET['install_root']) && file_exists($this->cert_file)) {
			$myCert = join("", file($this->cert_file));
			header("Content-Type: application/x-x509-ca-cert");
			print $myCert;
			exit(1);
		}
		return false;
	}
	public function process()
	{
		if (Config::get_config('ca_mode') != CA_STANDALONE) {
			return false;
		}

		$ca_key		= Config::get_config('ca_cert_name');
		$crl_file	= Config::get_config('ca_crl_name');

		if ($_GET['show_root_cert']) {
			openssl_x509_export(file_get_contents($this->cert_file), $tmp, false);
			$this->tpl->assign('ca_dump', $tmp);
		}
		if ($_GET['show_crl']) {
			$crl_dump = openssl_crl_export(file_get_contents($this->crl_file));
			$this->tpl->assign('crl_dump', $crl_dump);
		}

		$this->tpl->assign('crl_file', "ca" . Config::get_config('ca_crl_name'));
		$this->tpl->assign('ca_file', "ca/ca_cert.pem");
		$this->tpl->assign('content', $this->tpl->fetch('root_cert.tpl'));
	}
}
$fw = new Framework(new Root_Certificate());
$fw->start();
?>
