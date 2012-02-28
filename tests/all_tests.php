<?php
require_once "include.inc";
require_once "unit_tester.php";
require_once "reporter.php";

/* require tests */
require_once "test_csr_upload.php";
require_once "test_ca.php";
require_once "test_input.php";
require_once "test_www.php";

/* start the test-harness */
$test = new TestSuite("Confusa test-suite");
$test->add(new TestCSRUpload());
$test->add(new TestOfInput());
$test->add(new TestWeb());
$test->add(new TestCA());

if (php_sapi_name() === "cli") {
	$test->run(new TextReporter());
} else {
	$test->run(new HTMLReporter());
}

?>