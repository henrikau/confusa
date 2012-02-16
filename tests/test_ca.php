<?php
$webdir = dirname(__FILE__);
$path = ini_get('include_path');
$path .= PATH_SEPARATOR . $webdir;
$path .= PATH_SEPARATOR . $webdir . '/mocks';
$path .= PATH_SEPARATOR . dirname($webdir) . '/extlibs/simpletest';
$path = $path . PATH_SEPARATOR;
ini_set('include_path', $path);

define("PRD_ESCIENCE", 0);
define("PRD_PERSONAL", 1);

require_once "../lib/ca/CA.php";
require_once "Person.php";		/* in mocks */

require_once('autorun.php');

$_SERVER['REMOTE_ADDR'] = "127.0.0.1";

class ShallowCA extends CA
{
	function signKey($csr) {;}
	function getCertDeploymentScript($key, $browser) {;}
	function getCertList() {;}
	function pollCertStatus($key) {;}
	function getCert($key) {;}
	function getCertInformation($key) {;}
	function deleteCertFromDB($key) {;}
	function revokeCert($key, $reason) {;}
	function getCertListForPersons($common_name, $org) {;}
	function getCertListForEPPN($eppn, $org) {;}
	function verifyCredentials($username, $password) {;}
}

class TestCA extends UnitTestCase
{
	function testGetBrowserDN()
	{
		$n = new NREN("some nren", "EN");
		$s = new Subscriber("idp", "foobar");

		$p = new Person("Doe, John", $s, $n);
		$ca = new ShallowCA($p, 14);
		$this->assertEqual($ca->getBrowserFriendlyDN(false),
						   "C=EN, O=foobar, CN=Doe, John");
		$this->assertEqual($ca->getBrowserFriendlyDN(true),
						   "C=EN, O=foobar, CN=Doe John");

		$p = new Person("John Doe", $s, $n);
		$ca = new ShallowCA($p, 14);
		$stru = $ca->getBrowserFriendlyDN();
		$strf = $ca->getBrowserFriendlyDN(true);
		$this->assertEqual($ca->getBrowserFriendlyDN(false),
						   "C=EN, O=foobar, CN=John Doe");
		$this->assertEqual($ca->getBrowserFriendlyDN(true),
						   "C=EN, O=foobar, CN=John Doe");
	}
}
?>