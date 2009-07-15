<?php
require_once('confusa_include.php');
require_once 'framework.php';
require_once 'person.php';

class RevokeCertificate extends ContentPage
{
	function __construct()
	{
		parent::__construct("Revoke Certificate(s)", true);
	}

	function __destruct()
	{
		parent::__destruct();
	}
	public function pre_process($person)
	{
		$this->setPerson($person);
		$this->setManager();
		return false;
	}
	public function process($person)
	{
		echo "<H3>Certificate Revocation Area</H3>\n";
	}
	public function post_render($person)
	{
		return;
	}

}

$fw = new Framework(new RevokeCertificate());
$fw->start();

?>