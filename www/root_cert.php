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
			download_file(file_get_contents($this->cert_file), Config::get_config('ca_cert_name'));
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
		$ca_key		= Config::get_config('ca_cert_name');
		openssl_x509_export(file_get_contents($this->cert_file), $tmp, false);

		$this->tpl->assign('ca_file', "ca/ca_cert.pem");
		$this->tpl->assign('ca_dump', $tmp);
		$this->tpl->assign('content', $this->tpl->fetch('root_cert.tpl'));
	}
}
$fw = new Framework(new Root_Certificate());
$fw->start();
?>
