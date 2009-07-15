<?php
require_once 'confusa_include.php';
include_once 'framework.php';
class Logout extends FW_Content_Page
{
	public function __construct()
	{
		parent::__construct("Logout", false);
	}
	public function pre_process($person)
	{
		$this->setPerson($person);
	}
	public function process($person)
	{
		if (isset($_GET['edu_name'])) {
			require_once 'confusa_auth.php';
			deauthenticate_user($this->person);
		}
		if (!$this->person || !$this->person->is_auth()) {
			echo "<H2>You have been logged out of Confusa</H2>\n";
			echo "Return to <A HREF=\"index.php\">start</A><BR>\n";
		}
	}
	public function post_render($person)
	{
		;
	}
}

$fw = new Framework(new Logout());
$fw->start();

?>
