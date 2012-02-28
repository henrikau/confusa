<?php
require_once "include.inc";
require_once "exceptions.php";
require_once "../lib/io/Input.php";
require_once "../lib/file/CSRUpload.php";

/* Files containing compromised RSA-keys
 * http://research.naumachiarius.com/download/inkey-OSSL-4096-X86-NOR-NEW-XXXX-REG.tar.gz
 * http://research.naumachiarius.com/download/inkey-OSSL-3072-X86-NOR-OLD-XXXX-REG.tar.gz
 */
class TestCSRUpload extends UnitTestCase
{

	function testEmptyCSRUpload()
	{
		;						/* fixme */
	}

	function testBlacklist()
	{
		$csr = $this->genCSR();
		$this->assertFalse(empty($csr));
		try {
			CSRUpload::testBlacklist($csr);
			$this->pass();
		} catch (Exception  $e) {
			$this->fail("newly generated CSR should not result in an exception.");
		}
	}

	function testBlacklistCompromisedKey()
	{
		$files = array('compromised_1.pem','compromised_2.pem','compromised_3.pem');
		foreach($files as $f) {
			$csr = $this->getCSRFromFile($f);
			try {
				CSRUpload::testBlacklist($csr);
				$this->fail("Compromised RSA-key should fail CSRUpload::testBlacklist()");
			} catch (Exception  $e) {
				$this->pass();
			}
		}
	}

	private function genCSR()
	{
		$pkey = openssl_pkey_new(array('private_key_bits' => 2048));
		$res = openssl_csr_new(array(), $pkey);
		openssl_csr_export($res, $out);
		return $out;
	}

	private function getCSRFromFile($file)
	{
		$rsa = $this->getFile($file);
		$csr = openssl_csr_new(array(), $rsa);
		openssl_csr_export($csr, $csr_out);
		return $csr_out;
	}
	private function getFile($filename)
	{
		$path = 'files/'.$filename;
		if (!is_file($path)) {
			return null;
		}
		return file_get_contents($path, false, null, 0, filesize($path));
	}

}
?>