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

		return $res;
	}

	private function testURL()
	{
		$url_ugly = "https:////meh.idp.org//whatever///meh.php?var=foo";
		$url      = "https://meh.idp.org/whatever/meh.php?var=foo";
		if (Input::sanitizeURL($url_ugly) !== $url)
			return false;
		return true;
	}}
?>