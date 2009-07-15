<?php
require_once 'confusa_include.php';
include_once 'framework.php';
include_once 'mail_manager.php';
include_once 'logger.php';

class Tools extends FW_Content_Page
{
	public function __construct()
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
		if (isset($_GET['send_file'])) {
			include_once 'file_download.php';
			include_once 'create_keyscript.php';
			$keyscript = new KeyScript($person);
			download_file($keyscript->create_script(), "create_cert.sh");
			Logger::log_event(LOG_NOTICE, "Sending script via file to ". $person->get_common_name());
			exit(0);
		}
		return false;
	}
	public function process($person)
	{
		echo "<H3>Certificate Revocation Area</H3>\n";

		include 'tools.html';

		if (isset($_GET['send_email']))
			$this->send_email();
	}

	public function post_render($person)
	{
		return;
	}
	private function send_email()
	{
		include_once 'create_keyscript.php';
		$keyscript = new KeyScript($this->person);
		$eol = "\r\n";
		$body = "";
		$body .= "Attached is a custom-designed script for creating keys" . $eol;
		$body .= "Save the script to your computer, preferably in ~/bin/ , " . $eol;
		$body .= "and set executable (chmod u+x create_cert.sh)." . $eol . $eol;
		$body .= "The script will prompt for a passphrase for the key. " . $eol;
		$body .= "Remember to read the instructions carefully!" . $eol;
		$subject = 'Custom-tailored script for creating key and certificate request for ARC';
		$mail = new MailManager($this->person,
					Config::get_config('sys_from_address'),
					$subject,
					$body);
		$mail->add_attachment($keyscript->create_script(), "create_cert.sh");
		$mail->send_mail();
		echo "<I>Mail sent to " . $this->person->get_email() . " with new version of create_cert.sh</I><BR />\n";

	}
}
$fw = new Framework(new Tools());
$fw->start();

?>
