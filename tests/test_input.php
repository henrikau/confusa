<?php
require_once "include.inc";
require_once "unit_tester.php";
require_once "autorun.php";
require_once "Config.php";
require_once "../lib/io/Input.php";

class TestOfInput extends UnitTestCase {

	function setUp()
	{
		/* init */
	}

	function tearDown()
	{
		/* cleanup */
	}

	function testLang()
	{
		$this->assertEqual("en", Input::sanitizeLangCode("en"));
		$this->assertEqual("no", Input::sanitizeLangCode("no123"));
		$this->assertEqual("de-DE", Input::sanitizeLangCode("de-DE"));
		$this->assertEqual("abcdefgh", Input::sanitizeLangCode("ab123cde8493fg8989h"));
	}

	function testACSRF()
	{
		$res = true;
		$t = "123:".sha1("123"."hello world");
		$this->assertEqual($t, Input::sanitizeAntiCSRFToken($t));
		$this->assertNotEqual(Input::sanitizeAntiCSRFToken("134:hello world"),$t);
	}

	function testNumbers()
	{
		$res = true;
		$this->assertEqual(Input::sanitizeNumeric(10), 10);
		$this->assertEqual(Input::sanitizeNumeric("10"), 10);
		$this->assertEqual(Input::sanitizeNumeric(-3), -3);
		$this->assertEqual(Input::sanitizeNumeric(380243), 380243);
		$this->assertNotEqual(Input::sanitizeNumeric("hello world"),"hello world");
		$this->assertNotEqual(Input::sanitizeNumeric("x10"), "x10");
	}

	function testURL()
	{
		$url_ugly = "https:////meh.idp.org//whatever///meh.php?var=foo";
		$url      = "https://meh.idp.org/whatever/meh.php?var=foo";
		$this->assertEqual(Input::sanitizeURL($url_ugly), $url);

	}

	function testEmail()
	{
		$emails_ok = array("dclo@us.ibm.com",
				   "A12345@example.com",
				   "john.doe@example.com",
				   "user+mailbox@example.com",
				   "peter.piper@example.com");

		$emails_nok = array("hello world@example.com" => "helloworld@example.com");


		foreach($emails_ok as $email) {
			$this->assertEqual(Input::sanitizeEmail($email), $email);
		}

		foreach($emails_nok as $email => $correct) {
			$this->assertEqual(Input::sanitizeEmail($email), trim($correct));
		}
	}

	function testSanitizeDiff()
	{
		$originalString = "Stichting FOM - Nikhef";
		$sanitizedString = "Stichting FOM  Nikhef";
		$difference = "-";
		$this->assertEqual(Input::findSanitizedCharacters($originalString, $sanitizedString), $difference);

		$originalString = "avalid@Orgname,.";
		$sanitizedString = "avalid@Orgname,.";
		$difference = "";
		$this->assertEqual(Input::findSanitizedCharacters($originalString, $sanitizedString), $difference);

		$originalString = "--'??";
		$sanitizedString = "";
		$difference = $originalString;
		$this->assertEqual(Input::findSanitizedCharacters($originalString, $sanitizedString),$difference);

		$originalString = "";
		$sanitizedString = "";
		$difference = "";
		$this->assertEqual(Input::findSanitizedCharacters($originalString, $sanitizedString),$difference);

		$originalString = "-?'";
		$sanitizedString = "-";
		$difference = "?'";
		$this->assertEqual(Input::findSanitizedCharacters($originalString, $sanitizedString),$difference);

		$originalString = "@-?";
		$sanitizedString = "-";
		$difference = "@?";
		$this->assertEqual(Input::findSanitizedCharacters($originalString, $sanitizedString),$difference);

		$originalString = "@?-";
		$sanitizedString = "-";
		$difference = "@?";
		$this->assertEqual(Input::findSanitizedCharacters($originalString, $sanitizedString),$difference);
	}

	function testMaintMode()
	{
		$this->assertEqual(Input::sanitizeMaintMode('y'), 'y');
		$this->assertNotEqual(Input::sanitizeMaintMode('y'), 'n');
		$this->assertNotEqual(Input::sanitizeMaintMode('n'), 'y');
		$this->assertEqual(Input::sanitizeMaintMode('æ'), '');
		$this->assertEqual(Input::sanitizeMaintMode('<asdfasdfasdf'), '');
		$this->assertEqual(Input::sanitizeMaintMode('<asdfasdfasdfn'), 'n');
	}

	function testGoodSubscriberName()
	{
		$good = "some subscriber-name";
		$this->assertEqual(Input::sanitizeOrgName($good), $good);
	}
	function testTooLongSubscriberName()
	{
		/* CPS 3.1.3 eScience state that O= should not be longer than 64 char */
		$toolong = "This name is way too long and should be truncated by Input::sanitizeIdPName";
		$sanitized = Input::sanitizeOrgName($toolong);
		$this->assertNotEqual($sanitized,
							  $toolong,
							  "Error when sanitizing name - should truncate name that is too long");
		$this->assertEqual(64, strlen($sanitized));
	}

	function testCommaSubscriberName()
	{
		$bad = "Some, subscriber";
		$this->assertEqual(Input::sanitizeOrgName($bad), "Some subscriber");
	}
} /* end TestOfInput */
?>