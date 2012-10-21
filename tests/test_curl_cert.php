<?php
require_once "include.inc";
require_once "../lib/io/CurlWrapper.php";
require_once "../lib/ca/Certificate.php";

class TestCurlCert extends UnitTestCase {

	private $defurl;
	private $defkey;
	private $defcert;
	private $defpw;
	private $defdata;
	function setUp()
	{
		$data = $this->gen_new_keypair(false);
		$this->defurl  = "http://www.google.com/";
		$this->defkey  = $data['key'];
		$this->defcert = $data['cert'];
		$this->defpw   = $data['pw'];
		$this->defdata = array('key' => 'value');
	}

	function tearDown()
	{
		Logger::empty_loglines();
	}

	function gen_new_keypair($expired = false)
	{
		$config = array('private_key_bits' => 384,
						'digest_alg'	   => 'sha1',
						'private_key_type' => OPENSSL_KEYTYPE_RSA);

		$privkey = openssl_pkey_new($config);
		$pw = "c0nfusa";
		$dn = array("countryName"		=> 'NO',
					"localityName"		=> 'Drammen',
					"organizationName"	=> 'Austad IT',
					"commonName"		=> 'austad.us',
					"emailAddress"		=> 'henrik@austad.us');
		$csr = openssl_csr_new($dn, $privkey);
		if ($expired) {
			$cert = openssl_csr_sign($csr, null, $privkey, -1);
		} else {
			$cert = openssl_csr_sign($csr, null, $privkey, 14);
		}
		openssl_pkey_export($privkey, $privkeystr, $pw);
		openssl_x509_export($cert, $certstr);
		openssl_csr_export($csr, $csrstr);

		return array('key'  => $privkeystr,
					 'cert' => $certstr,
					 'csr'  => $csrstr,
					 'pw'   => $pw);
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
		$data = $this->gen_new_keypair(true);
		$res = CurlWrapper::curlContactCert($this->defurl,
											$data['key'],
											$data['cert'],
											$data['pw'],
											$this->defdata);
		$this->assertFalse($res, "CurlWrapper should not accept an expired certificate.");
 	}

	function testCurlWrapper_storeCertFile()
	{
		$cert = new Certificate($this->defcert);
		$hash = $cert->getHash();
		$res =  CurlWrapper::curlContactCert($this->defurl,
											 $this->defkey,
											 $this->defcert,
											 $this->defpw,
											 $this->defdata);
		$this->assertTrue(file_exists("/tmp/$hash.key "), "CurlWrapper should have cached key (/tmp/$hash.key) to disk");
		$this->assertTrue(file_exists("/tmp/$hash.crt "), "CurlWrapper should have cached cert (/tmp/$hash.crt) to disk");
		//Logger::dump_loglines();
	}

	function testCurlWrapper_contactEndpoint()
	{
		$url = 'https://10.0.0.3/index.php';
		$res =  CurlWrapper::curlContactCert($this->defurl,
											 $this->defkey,
											 $this->defcert,
											 $this->defpw,
											 $this->defdata);
		$this->assertNotNull($res);
	}
}