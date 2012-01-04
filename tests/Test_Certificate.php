<?php
require_once 'Test.php';
require_once 'CRL.php';

class Test_Certificate extends Test
{
	function __construct()
	{
		parent::__construct("Certificate");
	}

	public function runTests()
	{
		return false;
	}

}
