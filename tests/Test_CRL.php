<?php
require_once 'Test.php';
require_once 'CRL.php';

class Test_CRL extends Test
{
	private $crl_pem;
	private $crl_der;

	function __construct()
	{
		parent::__construct("CRL");
		$this->crl_pem  = $this->getFile('TERENAPersonalCA.crl.pem');
		$this->crl_der  = $this->getFile('TERENAPersonalCA.crl.der');
		$this->crl_dump = trim($this->getFile('TERENAPersonalCA.crl.dump'), "\n");
		$this->crlp = new CRL($this->crl_pem);
		$this->crld = new CRL($this->crl_der);
	}

	public function runTests()
	{
		if (!$this->valid) {
			return false;
		}
		$res  = $this->testConversion();
		$res &= $this->testDump();
		$res &= $this->testSubject();
		$res &= $this->testValid();
		return $res;
	} /* end runTests() */

	private function testConversion()
	{
		$res = true;
		if ($this->crld->getPEMContent() !== $this->crlp->getPEMContent()) {
			/* echo substr(bin2hex($this->crlp->getPEMContent()), -40) . "\n"; */
			/* echo substr(bin2hex($this->crld->getPEMContent()), -40) . "\n"; */
			$this->printMsg("failed matching DER-content.");
			$res = false;
		}
		if ($this->crld->getDERContent() !== $this->crlp->getDERContent()) {
			/* echo substr(bin2hex($this->crlp->getDERContent()), -40) . "\n"; */
			/* echo substr(bin2hex($this->crld->getDERContent()), -40) . "\n"; */
			echo $this->printMsg("failed matching DER-content.");
			$res = false;
		}
		return $res;
	} /* end testConversion */

	private function testDump()
	{
		/* echo sha1($this->crlp->getPemContent(false)) . "\n"; */
		/* echo sha1($this->crl_dump). "\n"; */
		/* echo substr(bin2hex($this->crlp->getPemContent(false)), -40) . "\n"; */
		/* echo substr(bin2hex($this->crl_dump), -40) . "\n"; */
		if ($this->crlp->getPemContent(false) !== $this->crl_dump &&
		    $this->crld->getPemContent(false) !== $this->crl_dump) {
			$this->printMsg("Error when dumping CRL in human readable format.");
			return false;
		}
		return true;
	} /* end testDump */

	private function testSubject()
	{
		$expected = "/C=NL/O=TERENA/CN=TERENA Personal CA";
		if (($expected !== $this->crld->getSubject() ) &&
		    ($expected !== $this->crlp->getSubject() )) {
			$this->printMsg("Error when getting Hash, result was not as expected:");
			return false;
		}
		return true;
	}

	private function testValid()
	{
		return ! $this->crlp->isValid();
	}
}
?>
