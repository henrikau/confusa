<?php
require_once "include.inc";
require_once "unit_tester.php";
require_once "autorun.php";
require_once '../lib/ca/CRL.php';

class TestCRL extends UnitTestCase
{

	private $crl_pem;
	private $crl_der;
	private $crld;
	private $crlp;
	private $valid = true;

	function __construct()
	{
		parent::__construct();
		$this->crl_pem  = $this->getFile('TERENAPersonalCA.crl.pem');
		$this->crl_der  = $this->getFile('TERENAPersonalCA.crl.der');
		$this->crl_dump = trim($this->getFile('TERENAPersonalCA.crl.dump'), "\n");
		$this->crlp = new CRL($this->crl_pem);
		$this->crld = new CRL($this->crl_der);
		if (!$this->valid || $this->crlp == NULL || $this->crlp == NULL)
			$this->valid = false;
	}

	function printMsg($msg) { ; }
	function testBase()
	{
		$this->assertNotNull($this->crlp);
		$this->assertNotNull($this->crld);
	}

	function testConversion()
	{
		if (!$this->valid) return;
		$crl_pem  = $this->getFile('TERENAPersonalCA.crl.pem');
		$crl_der  = $this->getFile('TERENAPersonalCA.crl.der');
		$this->assertEqual($this->crld->getPEMContent(), $this->crlp->getPEMContent());
		$this->assertEqual($this->crld->getDERContent(), $this->crlp->getDERContent());
	}

	function testDump()
	{
		if (!$this->valid) return;
		if ($this->crlp->getPemContent(false) !== $this->crl_dump &&
		    $this->crld->getPemContent(false) !== $this->crl_dump) {
			$this->printMsg("Error when dumping CRL in human readable format.");
			return false;
		}
		return true;
	} /* end testDump */

	function testSubject()
	{
		if (!$this->valid) return;
		if (!$this->valid) return;
		$expected = "/C=NL/O=TERENA/CN=TERENA Personal CA";
		if (($expected !== $this->crld->getSubject() ) &&
		    ($expected !== $this->crlp->getSubject() )) {
			$this->printMsg("Error when getting Hash, result was not as expected:");
			return false;
		}
		return true;
	}

	function testValid()
	{
		if (!$this->valid) return;
		return ! $this->crlp->isValid();
	}


	private function getFile($filename)
	{
		$path = 'files/'.$filename;
		if (!is_file($path)) {
			$this->printMsg("Cannot open $path, test will fail.");
			$this->valid = false;
			return null;
		}

	}
}
?>
