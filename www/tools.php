<?php
require_once 'confusa_include.php';
include_once 'framework.php';
include_once 'mail_manager.php';
include_once 'logger.php';
require_once 'pw.php';

class Tools extends FW_Content_Page
{
	public function __construct()
	{
		parent::__construct("Revoke Certificate(s)", true, "tools.php");
	}

	function __destruct()
	{
		parent::__destruct();
	}
	public function pre_process($person)
	{
		parent::pre_process($person);
		if (isset($_GET['send_file'])) {
			include_once 'file_download.php';
			include_once 'create_keyscript.php';
			$keyscript = new KeyScript($person);
			download_file($keyscript->create_script(), "create_cert.sh");
			Logger::log_event(LOG_NOTICE, "Sending script via file to ". $this->person->getEPPN());
			exit(0);
		}
		parent::pre_process($person);
		return false;
	}
	public function process()
	{
		if (isset($_GET['send_email']))
			$this->send_email();
		$this->tpl->assign('content', $this->tpl->fetch('tools.tpl'));

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
		if ($mail->send_mail()) {
			Framework::message_output("Mail sent to " . $this->person->getEmail() . " with new version of create_cert.sh");
		} else {
			$code = create_pw(8);
			Logger::log_event(LOG_NOTICE, "Could not send email to user, check mail-logs at this time. Session-error-code: $code");
			Framework::error_output("Could not send mail to " . $this->person->getEmail() .
						"<BR />Check the server-logs for details. Log-code $code");
		}

	}
}
$fw = new Framework(new Tools());
$fw->start();

?>
