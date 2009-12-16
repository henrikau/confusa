<?php
require_once 'confusa_include.php';
include_once 'framework.php';
include_once 'mail_manager.php';
include_once 'logger.php';
require_once 'pw.php';

class CP_Tools extends Content_Page
{
	public function __construct()
	{
		parent::__construct("Tools of the trade", true, "tools.php");
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
		} else if (isset($_GET['xml_client_file'])) {
			if ($this->person->isAdmin()) {
				require_once 'file_download.php';
				$xml_client = file_get_contents(Config::get_config('install_path')
								. "/extlibs/XML_Client/XML_Client.py");
				$confusa_parser = file_get_contents(Config::get_config('install_path')
								    . "/extlibs/XML_Client/Confusa_Parser.py");
				$readme		= file_get_contents(Config::get_config('install_path') .
								    "/extlibs/XML_Client/README");
				$init = file_get_contents(Config::get_config('install_path') . "/extlibs/XML_Client/__init__.py");


				$zip = new ZipArchive();
				$name = tempnam("/tmp/", 'meh');
				$zip->open($name, ZipArchive::OVERWRITE);
				$zip->addFromString("XML_Client/XML_Client.py",		$xml_client);
				$zip->addFromString("XML_Client/Confusa_Parser.py",	$confusa_parser);
				$zip->addFromString("XML_Client/README",		$readme);
				$zip->addFromString("XML_Client/__init__.py",		$init);
				if ($zip->numFiles != 4) {
					echo "Could not add all files, aborting<br />\n";
					return;
				}
				if ($zip->close()) {
					$contents = file_get_contents($name);
					download_zip($contents, "XML_Client.zip");
				}
				unlink($name);
				Logger::log_event(LOG_NOTICE, "Sending XML_Client.zip to " . $this->person->getEPPN());
				exit(0);
			}
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
					Config::get_config('system_name'),
					Config::get_config('sys_header_from_address'));
		$mail->setSubject($subject);
		$mail->setBody($body);
		$mail->addAttachment($keyscript->create_script(), "create_cert.sh");
		if ($mail->sendMail()) {
			Framework::message_output("Mail sent to " . htmlentities($this->person->getEmail()) .
			                          " with new version of create_cert.sh");
		} else {
			$code = create_pw(8);
			Logger::log_event(LOG_NOTICE, "Could not send email to user, check mail-logs at this time. Session-error-code: $code");
			Framework::error_output("Could not send mail to " . htmlentities($this->person->getEmail()) .
						"<BR />Check the server-logs for details. Log-code $code");
		}

	}
}
$fw = new Framework(new CP_Tools());
$fw->start();

?>
