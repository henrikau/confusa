<?php
require_once "include.inc";
require_once "unit_tester.php";
require_once "autorun.php";
require_once "exceptions.php";
require_once "Person.php";
require_once "Logger.php";

require_once "../lib/actors/NRENAccount.php";

class TestNRENAccount extends UnitTestCase
{
	function testAccountCreation()
	{
		$account = NRENAccount::get(null);
		$this->assertEqual($account, false);

		/* FIXME: this needs MDB2Wrapper to receive a major workover as this is
		 * riddled with queries */

		/* $account = NRENAccount::get(new Person()); */
		/* $this->assertNotNull($account); */
	}

	function testMultipleNrenMaps()
	{
		MDB2Wrapper::setMode(MDB2Wrapper::NRENAccountError);
		$account = NRENAccount::get(new Person());
		$this->assertFalse($account->read(), "NRENAccount received corrupted data but did not fail");
	}
}

?>