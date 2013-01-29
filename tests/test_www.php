<?php
require_once "include.inc";
require_once "web_tester.php";

class TestWeb extends WebTestCase
{
	function testSSLWarning()
	{
		$this->get("http://localhost/confusa/index.php");
		$this->assertText("WARNING: SSL is OFF.");

	}
	function testBaseContent()
	{
		$this->get("http://localhost/confusa/index.php");
		$this->assertLink("Log out");
		$this->assertLink("Confusa");
	}

	function testAdmin()
	{
		$this->get("http://localhost/confusa/admin.php");
		$this->assertMime(array('text/plain', 'text/html'));
		$this->assertText("Add/delete Confusa administrators");
	}

	/* test for ticket #320 */
	function testXFrameOption()
	{
		$this->get("http://localhost/confusa/admin.php");
		$this->assertHeader('X-Frame-Options', 'DENY');
	}

	function test_response_all()
	{
		$end=".php";
		$f = scandir(dirname(dirname(__FILE__)) . "/www");
		foreach ($f as $key => $file) {
			if (strncmp(substr($file, -strlen($end)), $end, strlen($end)) == 0) {
				$this->get("http://localhost/confusa/$file");
				$this->assertResponse(200, "Failed getting 200 response from $file");
			}
		}

	}
}