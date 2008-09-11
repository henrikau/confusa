<?php
include_once('framework.php');
include_once('mail_manager.php');
include_once('logger.php');
include_once('confusa_include.php');

$fw = new Framework('tools');
$fw->force_login();

/* test to see ifthe user wants to receive tools via direct file-download or email */
test_send_tools();
$fw->render_page();
function tools($person)
{
     include('tools.html');
} /* end tools */

function test_send_tools()
{
     global $person;
     global $fw;
     if (!( isset($person) && $person->is_auth()  ))
          $person = $fw->authenticate();

     if (isset($_GET['send_email'])) {
          include_once('create_keyscript.php');
          $keyscript = new KeyScript($person);
          $eol = "\r\n";
          $body = "";
          $body .= "Attached is a custom-designed script for creating keys" . $eol;
          $body .= "Save script to computer, set executable (chmod u+x create_cert.sh) and run" . $eol;
          $body .= "The script will prompt for a passphrase for the key. Read the instructions carefully!" . $eol;
          $subject = 'Script for creating key and certificate request for ARC';
          $mail = new MailManager($person,
                                  Config::get_config('sys_from_address'),
                                  $subject,
                                  $body);
          $mail->add_attachment($keyscript->create_script(), "create_cert.sh");
          $mail->send_mail();
          echo "<i><b><center>Mail sent to " . $person->get_email() . " with new version of create_cert.sh</center></b></i><br>\n";
     }
     if (isset($_GET['send_file'])) {
          include_once('file_download.php');
          include_once('create_keyscript.php');
          $keyscript = new KeyScript($person);
          download_file($keyscript->create_script(), "create_cert.sh");
          Logger::log_event(LOG_NOTICE, "Sending script via file to ". $person->get_common_name());
          exit(1);
     }

} /* end test_send_tools */
?>
