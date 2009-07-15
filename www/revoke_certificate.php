<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';

class RevokeCertificate extends FW_Content_Page
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
		if ($this->person->get_mode() == ADMIN_MODE)
			$this->admin_revoke();
		else
			$this->normal_revoke();
	}
	public function post_process($person)
	{
		return;
	}

	private function admin_revoke()
	{
		echo "Admin revoke<BR />\n";
	}

	private function normal_revoke()
	{
		echo "Normal revoke<BR />\n";
	}

}

$fw = new Framework(new RevokeCertificate());
$fw->start();

?>