<?php
require_once "include.inc";
require_once "exceptions.php";
require_once "../lib/actors/NRENAccount.php";

class TestNRENAccount extends UnitTestCase
{
	function testAccountCreation()
	{
		$account = NRENAccount::get(null);
		$this->assertEqual($account, false);
		$account = NRENAccount::get(new Person());
		$this->assertNotNull($account);
	}

	function testMultipleNrenMaps()
	{
		MDB2Wrapper::setMode(MDB2Wrapper::NRENAccountError);
		$account = NRENAccount::get(new Person());
		$this->assertFalse($account->read(), "NRENAccount received corrupted data but did not fail");
	}
}

?>