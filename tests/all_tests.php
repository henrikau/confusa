<?php
require_once "include.inc";
require_once "unit_tester.php";
require_once "reporter.php";

/* start the test-harness */
class ConfusaSuite extends TestSuite
{
	function __construct() {
		parent::__construct("Confusa Test Suite");
		$this->find_tests();
	}

	function runTests()
	{
		if (php_sapi_name() === "cli") {
			$reporter = new TextReporter();
		} else {
			parent::run(new HTMLReporter());
		}
		parent::run($reporter);
	}
	private function find_tests()
	{
		$start="test_";
		$end=".php";
		$f = scandir(dirname(__FILE__));
		foreach ($f as $key => $file) {
			if ((strncmp(substr($file, 0, strlen($start)), $start, strlen($start)) == 0) &&
				(strncmp(substr($file, -strlen($end)), $end, strlen($end)) == 0)) {
				$this->addFile($file);
			}
		}

	}
}

$cs = new ConfusaSuite();
$cs->runTests();

?>