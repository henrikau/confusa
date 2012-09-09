<?php
require_once "include.inc";
require_once "../lib/io/CurlWrapper.php";

class TestCurlCert extends UnitTestCase {

	private $defurl;
	private $defkey;
	private $defcert;
	private $defpw;
	private $defdata;
	function setUp()
	{
		$this->defurl = "google.com";
		$this->defkey = file_get_contents('files/my.key');
		$this->defcert = file_get_contents('files/my.crt');
		$this->defpw = "foobar";
		$this->defdata = array('key' => 'value');
	}

	function gen_new_keypair($expired = false)
	{
		/* generate key/cert on the fly, shamelessly ripped from
		 * http://www.php.net/manual/en/function.openssl-pkey-new.php#42800
		 */
		$config = array('private_key_bits' => 512);
		$privKey = openssl_pkey_new($config);
		$privkeypass = "c0nfusa";
		$dn = array("countryName" => 'XX',
					"stateOrProvinceName" => 'State',
					"localityName" => 'SomewhereCity',
					"organizationName" => 'MySelf',
					"organizationalUnitName" => 'Whatever',
					"commonName" => 'mySelf',
					"emailAddress" => 'user@domain.com');
		$csr = openssl_csr_new($dn, $privkey);
		if ($expired) {
			$sscert = openssl_csr_sign($csr, null, $privkey, -1);
		} else {
			$sscert = openssl_csr_sign($csr, null, $privkey, 14);
		}
		openssl_x509_export($sscert, $publickey);
		openssl_pkey_export($privKey, $privatekey, $privkeypass);
		openssl_csr_export($csr, $csrStr);
		return array('key'  => $privatekey,
					 'cert' => $publickey,
					 'csr'  => $csrStr,
					 'pw'   => $privkeypass);
	}

	function testCurlWrapper_NullCert()
	{
		$this->expectException('ConfusaGenException');
		$data = CurlWrapper::curlContactCert($this->defurl, $this->defkey, null, $this->defpw, $this->defdata);
	}

	function testCurlWrapper_NullKey()
	{
		$this->expectException('ConfusaGenException');
		$data = CurlWrapper::curlContactCert($this->defurl, null, $this->defcert, $this->defpw, $this->defdata);
	}
	function testCurlWrapper_SetupEmptyData()
	{
		$res = CurlWrapper::curlContactCert($this->defurl, $this->defkey, $this->defcert, $this->defpw, null);
		$this->assertEqual($res, null);
	}

	Function testCurlWrapper_EmptyURL()
	{
		$res = CurlWrapper::curlContactCert(null ,$this->defkey, $this->defcert, $this->defpw, $this->defdata);
		$this->assertFalse($res, "CurlWrapper should fail at empty URL");
	}

	function testCurlWrapper_InvalidURL()
	{
		$res =  CurlWrapper::curlContactCert("http://134566.48",
											 $this->defkey,
											 $this->defcert,
											 $this->defpw,
											 $this->defdata);
		$this->assertFalse($res, "CurlWrapper should fail at invalid URL!");
	}

	function testCurlWrapper_unencryptedkey()
	{
		/* generate key */
		$unenc_key = file_get_contents('files/my.unencrypted.key');
		$res =  CurlWrapper::curlContactCert($this->defurl,
											 $unenc_key,
											 $this->defcert,
											 $this->defpw,
											 $this->defdata);
		$this->assertFalse($res, "CurlWrapper should not accept unencrypted private keys.");
	}
	function testCurlWrapper_wrongKeyPW()
	{
		$res =  CurlWrapper::curlContactCert($this->defurl,
											 $this->defkey,
											 $this->defcert,
											 $this->defpw . "foobar123",
											 $this->defdata);
		$this->assertFalse($res, "CurlWrapper should not accept wrong passphrase for private key");
	}

	function testCurlWrapper_wrongCertForKey()
	{
		$data = $this->gen_new_keypair();
		$res =  CurlWrapper::curlContactCert($this->defurl,
											 $this->defkey,
											 $data['cert'],
											 $this->defpw,
											 $this->defdata);
		$this->assertFalse($res, "CurlWrapper should not accept wrong key/cert pair.");
	}

	function testCurlWrapper_noAcceptExpiredCert()
	{
		/* mismatch between cert & key from gen_new_keypair... */
		echo "testCurlWrapper_noAcceptExpiredCert\n";
		$data = $this->gen_new_keypair(false);
		$res =  CurlWrapper::curlContactCert($this->defurl,
											 $data['key'],
											 $data['cert'],
											 $data['pw'],
											 $this->defdata);
		/* $this->assertFalse($res, "CurlWrapper should not accept an expired certificate."); */
		$this->assertEqual($res, "data");
	}
}