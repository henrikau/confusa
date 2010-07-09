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
