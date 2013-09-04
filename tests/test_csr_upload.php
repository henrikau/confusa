<?php
require_once "include.inc";
require_once "../lib/file/CSRUpload.php";
require_once "exceptions.php";
require_once "../lib/io/Input.php";
require_once "unit_tester.php";
require_once "autorun.php";

/* Getting the compromised CSR-test to work
 *
 * Download either (or both) archived containing compromised RSA-keys:
 * http://research.naumachiarius.com/download/inkey-OSSL-4096-X86-NOR-NEW-XXXX-REG.tar.gz
 *
 * Unzip to <confusa_dir>/tests/files/keys
 * Convert all files in this directory to PEM (will take a few minutes to complete):
 *
 * for i in $(ls); do \
 *		openssl rsa -inform DER -outform PEM -in $i -out $i.pem; \
 *		rm -fv $i; \
 *		done
 *
 * Now it should be ready to run
 */
class TestCSRUpload extends UnitTestCase
{

	function testEmptyCSRUpload()
	{
		;						/* fixme */
	}

	function testBlacklistWithGood()
	{
		$c = 2;
		while($c-- > 0) {
			$csr = $this->genCSR();
			$this->assertFalse(empty($csr));
			try {
				CSRUpload::testBlacklist($csr);
				$this->pass();
			} catch (Exception  $e) {
				$this->fail("newly generated CSR should not result in an exception.");
			}
		}
	}

	function testBlacklistCompromisedKey()
	{
		$list = $this->getCompromisedList(2);
		if ($list) {
			foreach ($list as $file) {
				$csr = $this->getCSRFromFile($file);
				try {
					CSRUpload::testBlacklist($csr);
					$this->fail("Compromised RSA-key should fail CSRUpload::testBlacklist() -> $file");
				} catch (Exception  $e) {
					$this->pass();
				}
			}
		} else {
			$this->fail("Missing library of compromised keys, please download and unpack as instructed in " .
						__FILE__);
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

	private function getCompromisedList($size = 5)
	{
		$dir = dirname(__FILE__) . "/files/keys";
		if (!is_dir($dir)) {
			echo "$dir not present, cannot retrieve list of compromised keys!";
			return false;
		}

		/* FIXME: make the scan _faster_, this times out */
		return false;
		$list = scandir($dir);
		$lsize = count($list) - 1;
		$res = array();
		while($size-- > 0) {
			do {
				$f = $list[rand(0, $lsize)];
			} while (!$this->_isValid($f, 'files'));
			$res[] = $f;
		}
		return $res;
	}

	private function _isValid($filename, $path)
	{
		$file = $path . "/" . $filename;
		return is_file($file) && is_readable($file);
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
