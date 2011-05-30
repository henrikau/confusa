<?php
require_once 'Test.php';
require_once 'Input.php';
require_once 'NREN_Handler.php';

class Test_Input extends Test
{
	function __construct()
	{
		parent::__construct("Input");
	}

	public function runTests()
	{
		if (!$this->valid) {
			return false;
		}
		$res=true;
		$res &= $this->testURL();
		$res &= $this->testSanitizeDiff();
		$res &= $this->testEmail();
		$res &= $this->testNumbers();
		$res &= $this->testOrgName();
		$res &= $this->testACSRF();
		return $res;
	}

	private function testACSRF()
	{
		$res = true;
		$t = "123:".sha1("123"."hello world");
		$res &= Input::sanitizeAntiCSRFToken($t) == $t;
		$res &= !(Input::sanitizeAntiCSRFToken($t) == "x:meh");
		$res &= !(Input::sanitizeAntiCSRFToken("134:hello world") == $t);
		return $res;
	}
	private function testOrgName()
	{
		$res = true;
		/* $res &= Input::sanitizeOrgName("test"); */

		if (!$res)
			$this->printMsg("Failed sanitizing of orgname");
		return $res;
	}

	private function testNumbers()
	{
		$res = true;
		$res &= Input::sanitizeNumeric(10) == 10;
		$res &= Input::sanitizeNumeric("10") == 10;
		$res &= Input::sanitizeNumeric(-3) == -3;
		$res &= Input::sanitizeNumeric(380243) == 380243;
		$res &= !Input::sanitizeNumeric("hello world") == "hello world";
		$res &= !Input::sanitizeNumeric("x10") == "x10";
		if (!$res)
			$this->printMsg("failed sanitizing numbers");
		return $res;
	}

	private function testURL()
	{
		$url_ugly = "https:////meh.idp.org//whatever///meh.php?var=foo";
		$url      = "https://meh.idp.org/whatever/meh.php?var=foo";
		if (Input::sanitizeURL($url_ugly) !== $url)
			return false;
		return true;
	}

	private function testEmail()
	{
		$emails_ok = array("dclo@us.ibm.com",
				   "A12345@example.com",
				   "john.doe@example.com",
				   "user+mailbox@example.com",
				   "peter.piper@example.com");

		$emails_nok = array("hello world@example.com" => "helloworld@example.com");


		foreach($emails_ok as $email) {
			if (Input::sanitizeEmail($email) != $email) {
				$this->printMsg("Input failed on sanitizing $email");
				return false;
			}
		}

		foreach($emails_nok as $email => $correct) {
			if (trim(Input::sanitizeEmail($email)) != trim($correct)) {
				$this->printMsg("Input failed on sanitizing $email -> expected $correct, got " . Input::sanitizeEmail($email) . ".");
				return false;
			}
		}
		return true;
	}

	private function testSanitizeDiff()
	{
		$originalString = "Stichting FOM - Nikhef";
		$sanitizedString = "Stichting FOM  Nikhef";
		$difference = "-";

		if (Input::findSanitizedCharacters($originalString, $sanitizedString)
		                                                    != $difference) {
			return false;
		}

		$originalString = "avalid@Orgname,.";
		$sanitizedString = "avalid@Orgname,.";
		$difference = "";

		if (Input::findSanitizedCharacters($originalString, $sanitizedString)
		                                               != $difference) {
			return false;
		}

		$originalString = "--'??";
		$sanitizedString = "";
		$difference = $originalString;

		if (Input::findSanitizedCharacters($originalString, $sanitizedString)
		                                               != $difference) {
			return false;
		}

		$originalString = "";
		$sanitizedString = "";
		$difference = "";

		if (Input::findSanitizedCharacters($originalString, $sanitizedString)
		                                                != $difference) {
			return false;
		}

		$originalString = "-?'";
		$sanitizedString = "-";
		$difference = "?'";

		if (Input::findSanitizedCharacters($originalString, $sanitizedString)
		                                                 != $difference) {
			return false;
		}

		$originalString = "@-?";
		$sanitizedString = "-";
		$difference = "@?";

		if (Input::findSanitizedCharacters($originalString, $sanitizedString)
		                                                 != $difference) {
			return false;
		}

		$originalString = "@?-";
		$sanitizedString = "-";
		$difference = "@?";

		if (Input::findSanitizedCharacters($originalString, $sanitizedString)
		                                                != $difference) {
			return false;
		}

		return true;
	}
}
?>
