<?php 
include_once('framework.php');
include_once('cert_manager.php'); /* for handling of the key */
include_once('mail_manager.php'); /* for sending emails */
include_once('file_upload.php'); /* FileUpload */
include_once('sql_lib.php');
include_once('pw.php');
include_once('csr_lib.php');
include_once('logger.php');

$fw = new Framework('keyhandle');
$fw->force_login();		/* this page should *never* be open to the public */
$fw->render_page();

$person = null;


/* this function contains the main-flow in the program.
 */
function keyhandle($pers) 
{
  global $person;
  $person = $pers;
  if ($person->is_auth())
    {
         /* CAUTION: by using the csr_debug, you can download a file directly
          * through the browser, but the system has *no* way of remembering
          * where this file i stored! */
         /* csr_debug(); */

         /* send script or show link for sending script */
         send_script();

         /* process uploaded csr's (or show the upload form) */
         process_file_csr();

         /* look in the database and see if there's any waiting csrs
          * (automatically uploaded). */
         process_db_csr();

         /* look in the database to see if there's any CSR awaiting retrieval
          * */
         process_db_cert();
    }
  else {
	  echo "You will have to <A HREF=\"index.php?start_login=yes\">login</A> to use this page<BR>\n";
  }
} /* end keyhandle() */

/* process_file_csr()
 *
 * Take a CSR (stored in memory, *not* file) and sign&ship it. CM will do some
 * additional checks. A common wrapper for the two ways we have for getting a
 * key signed - automatic upload or manual.
 */
function process_file_csr()
{
	global $person;
        global $confusa_config;
	/* process_key($person, 'user_csr'); */
	if(isset($_FILES['user_csr']['name'])) {
		$fu = new FileUpload('user_csr', true, 'test_content');
		if ($fu->file_ok()) {
			$cm = new CertManager($fu->get_content(), $person);
			/* CertManager will test content of CSR before sending it off for signing
                         *
                         * As we upload the key manually, the user-script won't
                         * be called for creating a auth-token. We therefore
                         * create a random string containing the correct amount
                         * of characters. It will contain more letters than the
                         * user-script (which uses sha1sum of some random text).
                         */
			if (!$cm->sign_key(create_pw($confusa_config['auth_length'])))
                             ;

                }
        }
      else {
	      include('upload_form.html');
      }
}
/* process_db_csr()
 *
 * This function shall look at all the csr's in the csr_cache, and present the
 * CSR belonging to the user, to the user.
 * The user can then 'approve' a CSR for signing by sending back the id of the
 * given CSR. This will then be put through a challenge-response cycle.
 */
function process_db_csr()
{
	global $person;
	if (isset($_GET['delete_csr'])) {
             delete_csr(htmlentities($_GET['delete_csr']));
	}
        if (isset($_GET['auth_token']))
             approve_csr(htmlentities($_GET['auth_token']));

	else if (isset($_GET['inspect_csr'])) {
             inspect_csr(htmlentities($_GET['inspect_csr']));
	}
        show_db_csr();
}

function process_db_cert()
{
     global $person;

     if(isset($_GET['delete_cert'])) {
          delete_cert(htmlentities($_GET['delete_cert']));
     }
     if (isset($_GET['email_cert'])) {
          send_cert(htmlentities($_GET['email_cert']));
     }
     else if (isset($_GET['inspect_cert'])) {
          inspect_cert(htmlentities($_GET['inspect_cert']));
     }
     show_db_cert();
} /* end process_db_cert */


function send_script()
{
  global $person;
  global $confusa_config;
  if (isset($_GET['send_script'])) {
	  include_once('create_keyscript.php');
	  $keyscript = new KeyScript($person);
	  $eol = "\r\n";
	  $body = "";
	  $body .= "Attached is a custom-designed script for creating keys" . $eol;
	  $body .= "Save script to computer, set executable (chmod u+x create_key.sh) and run" . $eol;
	  $body .= "The script will prompt for a passphrase for the key. Read the instructions carefully!" . $eol;
	  $subject = 'Script for creating key and certificate request for ARC';
	  $mail = new MailManager($person,
				  $confusa_config['sys_from_address'],
				  $subject,
				  $body);
	  $mail->add_attachment($keyscript->create_script(), "create_cert.sh");
	  $mail->send_mail();
          echo "<BR><BR><BR><BR><BR>\n";
	  /* echo "New and updated script sent to " . $person->get_email() . "<BR>\n"; */
  }
  else {
       echo "<BR>\n";
       echo "<FONT COLOR=\"RED\"><B>Note:</B></FONT> To use this service effectively, you should use the automated script that takes ";
       echo "care of all the nitty-gritty details. The script is avaiable ";
       echo "<A HREF=\"" . $_SERVER['PHP_SELF'] . "?send_script\"> -here- </A> and will be sent to <A HREF=\"mailto:".$person->get_email()."\">".$person->get_email()."</A><BR><BR><BR>\n";
  }
}

/* approve_csr()
 *
 * This function approves a CSR for signing. It uses the auth-token as a
 * paramenter to find the CSR in the databse.
 */
function approve_csr($auth_token)
{
     global $person;
     $at = htmlentities($auth_token);
     $sql = get_sql_conn();
     $query = "SELECT csr, csr_id FROM csr_cache WHERE auth_key='".$at."' AND common_name='" . $person->get_common_name() . "'";
     $csr_res = $sql->execute($query);
     if (mysql_num_rows($csr_res) == 1) {
          $db_array = mysql_fetch_assoc($csr_res);
          $csr = $db_array['csr'];
          $cm = new CertManager($csr, $person);
          if (!$cm->sign_key($at)) {
               echo __FILE__ .":".__LINE__." Error signing key<BR>\n";
               return;
          }
          else {
               $update = "DELETE FROM csr_cache WHERE csr_id='".$db_array['csr_id']."'";
               $sql->update($update);
          }
     }
     else {
          echo __FILE__ .":".__LINE__." error getting CSR from database<BR>\n";
     }
     mysql_free_result($csr_res);
} /* end approve_csr_remote() */

function send_cert($cert_id)
{
     global $person;
     global $confusa_config;
     $loc_id = sanitize_id($cert_id);
     $sql = get_sql_conn();
     $query = "SELECT cert FROM cert_cache WHERE cert_id='".$loc_id."' AND cert_owner='".$person->get_common_name()."'";
     $res = $sql->execute($query);
     if (mysql_num_rows($res)==1) {
          $cert_array = mysql_fetch_assoc($res);

          /* Public function __construct($pers, $sender, $subject,  $body) */
          $mm = new MailManager($person,
                            $confusa_config['sys_from_address'], 
                            "Here is your newly signed certificate", 
                            "Attached is your new certificate. Remember to store this in $HOME/.globus/usercert.pem for ARC to use");
          $mm->add_attachment($cert_array['cert'], 'usercert.pem');
          if (!$mm->send_mail()) {
               echo "Could not send mail properly!<BR>\n";
               return false;
          }
     }
     mysql_free_result($res);
}

function show_db_csr()
{
     global $person;
     $sql = get_sql_conn();
     $query = "SELECT csr_id, uploaded_date, from_ip, common_name, auth_key FROM csr_cache WHERE common_name='" . $person->get_common_name() . "' ORDER BY uploaded_date DESC";
     $res = $sql->execute($query);

     echo "<B>Certificate Signing Requests (CSRs)</B><BR>\n";
     echo "<table class=\"small\">\n";

     if (mysql_num_rows($res) > 0) {
          /* TODO: fix id to be a counter, not the id in the database */
          echo "<tr><th>AuthToken</th><th>Uploaded</th><th>From IP</th><th>Owner</th></tr>\n";
          while($row=mysql_fetch_assoc($res)) {
               echo "<tr>\n";
               echo "<td>".$row['auth_key']."</td>\n";
               echo "<td>".$row['uploaded_date']."</td>\n";
               echo "<td>".$row['from_ip']."</td>\n";
               echo "<td>".$row['common_name']."</td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?auth_token=".$row['auth_key']."\">Sign</A></TD>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?inspect_csr=".$row['csr_id']."\">Inspect</A></TD>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?delete_csr=".$row['csr_id']."\">Delete</A></TD>\n";
               echo "</tr>\n";
          }
     }
     else {
          echo "<tr><td>No CSRs in database awaits you</td></tr>\n";
     }

     echo "</table>\n";
     mysql_free_result($res);
     echo "<BR><BR><BR>\n";
}
function show_db_cert() 
{
     global $person;
     $sql = get_sql_conn();
     $query = "SELECT cert_id, auth_key, cert_owner, valid_untill FROM cert_cache WHERE cert_owner='".$person->get_common_name()."' AND valid_untill > current_timestamp()";
     $res = $sql->execute($query);
     /* echo __FILE__ . ":" . __LINE__ . "<br>\n" . $query . "<br><br>\n"; */
     echo "<B>Certificates:</B><BR>\n";
     echo "<table class=\"small\">\n"; 
     if (mysql_num_rows($res) > 0) {
          echo "<tr><th>AuthToken</th><th>owner</th></tr>\n";
          while($row=mysql_fetch_assoc($res)) {
               echo "<tr>\n";
               /* echo "<td>".$row['cert_id']."</td>\n"; */
               echo "<td>".$row['auth_key']."</td>\n";
               echo "<td>".$row['cert_owner']."</td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?delete_cert=".$row['cert_id']."\">Delete</A></td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?email_cert=".$row['cert_id']."\">Email cert</A></td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?inspect_cert=".$row['cert_id']."\">Inspect</A></td>\n";
               echo "</tr>\n";
          }
     }
     else {
          echo "<tr><td>No Certificates</td></tr>\n";
     }
     echo "</table>\n";
     echo "<br>\n";
     echo "To download the certificate with the script, use the following command at your local workstation:<BR>\n";
     echo "<pre>./create_key.sh -get ".htmlentities("<AuthToken>")."</pre>\n";
     echo "create_key.sh will remember the last used AuthToken. If the script fails, try to pass \n";
     echo "the auth-token from the list above as a parameter to the script as showed above.<BR>\n";

     mysql_free_result($res);

     echo "<BR><BR><BR>\n";
} /* end show_db_cert() */

function inspect_csr($csr_id) {
	global $person;
	$loc_id=sanitize_id($csr_id);
	$sql=get_sql_conn();
	$query  = "SELECT * FROM csr_cache WHERE csr_id='" . $loc_id;
	$query .= "' AND common_name='" . $person->get_common_name() . "'";
	$res=$sql->execute($query);
	if(mysql_num_rows($res) == 1) {
		$csr_array = mysql_fetch_assoc($res);
		echo "<BR>Showing CSR#" . $loc_id . " from database:<BR>\n";
		echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?delete_csr=".$loc_id."\">Delete from Database</A> ]\n";
		echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?auth_token=".$csr_array['auth_key']."\">Approve for signing</A> ]\n";
		$cmd = "exec echo \"".$csr_array['csr']."\" | openssl req -noout -text";
		echo "<PRE>".shell_exec($cmd)."</PRE>\n";
	}
	mysql_free_result($res);
}
function inspect_cert($cert_id)
{
	global $person;

	$loc_id=sanitize_id($cert_id);
	$sql=get_sql_conn();
	$query  = "SELECT * FROM cert_cache WHERE cert_id='" . $loc_id;
	$query .= "' AND cert_owner='" . $person->get_common_name() . "'";
        /* echo $query . "<br>\n"; */
	$res=$sql->execute($query);
	if(mysql_num_rows($res) == 1) {
		$csr_array = mysql_fetch_assoc($res);
		$cmd = "exec echo \"".$csr_array['cert']."\" | openssl x509 -noout -text";

		echo "<BR>Showing CERT#" . $loc_id . " from database:<BR>\n";
		echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?delete_cert=".$loc_id."\">Delete from Database</A> ]\n";
		echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?email_cert=".$loc_id."\">Send by email</A> ]\n";
		echo "<PRE>".shell_exec($cmd)."</PRE>\n";
	}
	mysql_free_result($res);
     
}

function delete_csr($csr_id) {
	global $person;
	$loc_id=sanitize_id($csr_id);
	$query = "SELECT * FROM csr_cache WHERE csr_id='".$loc_id."' AND common_name='".$person->get_common_name()."'";
	$sql = get_sql_conn();
	$res = $sql->execute($query);
	$hits=mysql_num_rows($res);
	if ($hits== 1) {
		Logger::log_event(LOG_NOTICE, "Dropping CSR with hash ".pubkey_hash($hits['csr'])." belonging to ".$person->get_common_name()." originating from ".$_SERVER['REMOTE_ADDR']."");
		$update="DELETE FROM csr_cache WHERE csr_id=".$loc_id." AND common_name='".$person->get_common_name()."'";
		$sql->update($update);
	}
	else {
		if ($hits==0) {
			echo "No matching CSR found.<BR>\n";
			Logger::log_event(LOG_NOTICE, "Could not delete given CSR with id ".$loc_id." from ip ".$_SERVER['REMOTE_ADDR'] . " : " . $person->get_common_name());
		}
		else {
			echo "Too many hits (".$hits.") in database<BR>\n";
			Logger::log_event(LOG_WARNING, "Error in deleting CSR, got several matches on query (".$hits.") with id ".$loc_id."(" . $person->get_common_name() .") Ran query " . $update);
		}
	}
} /* end delete_csr() */

function delete_cert($cert_id)
{
	global $person;
	$loc_id=sanitize_id($cert_id);
	$query = "SELECT * FROM cert_cache WHERE cert_id='".$loc_id."' AND cert_owner='".$person->get_common_name()."'";
	$sql = get_sql_conn();
	$res = $sql->execute($query);
	$hits=mysql_num_rows($res);
	if ($hits== 1) {
		Logger::log_event(LOG_NOTICE, "Dropping CERT with ID ".$loc_id." belonging to ".$person->get_common_name());
		$update="DELETE FROM cert_cache WHERE cert_id=".$loc_id." AND cert_owner='".$person->get_common_name()."'";
		$sql->update($update);
	}
	else {
		if ($hits==0) {
			echo "No matching Certificate found.<BR>\n";
			Logger::log_event(LOG_NOTICE, "Could not delete given CSR with id ".$loc_id." from ip ".$_SERVER['REMOTE_ADDR']);
		}
		else {
			echo "Too many hits (".$hits.") in database<BR>\n";
			Logger::log_event(LOG_WARNING, "Error in deleting Certificate, got several matches on query (".$hits.") with id ".$loc_id." ");
		}
        }
}
function sanitize_id($id) {
	/* ================================================================= *
	 * WARNING: Possible sql-injection exploit entry-point!
	 * This is where we add an id to the database, even though it's been
	 * sanitized with htmlentities
	 *
	 * TODO: Do sanitize of $id here!
	 * ================================================================= */
     return (int) $id;
}
?>

