<?php 
include_once('framework.php');
include_once('cert_manager.php'); /* for handling of the key */
include_once('file_upload.php'); /* FileUpload */
include_once('pw.php');
include_once('csr_lib.php');
include_once('mdb2_wrapper.php');
include_once('logger.php');

$person = null;
$fw = new Framework('keyhandle');

/* test for downloading of certificates */
if (send_cert()) {
     exit(0);
}

$fw->force_login();
$fw->render_page();
/* this function contains the main-flow in the program.
 */
function keyhandle($pers) 
{
  global $person;
  $person = $pers;
  if ($person->is_auth())
    {
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
			if (!$cm->sign_key(create_pw(Config::get_config('auth_length'))))
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
     else if (isset($_GET['inspect_cert'])) {
          inspect_cert(htmlentities($_GET['inspect_cert']));
     }
     show_db_cert();
} /* end process_db_cert */

/* approve_csr()
 *
 * This function approves a CSR for signing. It uses the auth-token as a
 * paramenter to find the CSR in the databse.
 */
function approve_csr($auth_token)
{
     global $person;
     $at = htmlentities($auth_token);
     $csr_res = MDB2Wrapper::execute("SELECT csr, csr_id FROM csr_cache WHERE auth_key=? AND common_name=?",
                                     array('text', 'text'),
                                     array($at, $person->get_common_name()));
     if (count($csr_res) == 1) {
          $csr = $csr_res[0]['csr'];
          $cm = new CertManager($csr, $person);
          if (!$cm->sign_key($at)) {
               echo __FILE__ .":".__LINE__." Error signing key<BR>\n";
               return;
          }
          else {
               MDB2Wrapper::update("DELETE FROM csr_cache WHERE csr_id=?",
                                   array('integer'),
                                   array($csr_res[0]['csr_id']));
          }
     }
     else {
          echo __FILE__ .":".__LINE__." error getting CSR from database<BR>\n";
     }
} /* end approve_csr_remote() */

function send_cert()
{
     global $person;
     global $fw;
     $person = $fw->authenticate();
     $send_res = false;

     if (isset($_GET['email_cert']))
          $loc_id = sanitize_id(htmlentities($_GET['email_cert']));
     else if (isset($_GET['file_cert']))
          $loc_id = sanitize_id(htmlentities($_GET['file_cert']));

     $res = MDB2Wrapper::execute("SELECT cert FROM cert_cache WHERE cert_id=? AND cert_owner=?",
                                 array('integer', 'text'),
                                 array($loc_id, $person->get_common_name()));
     if (count($res)==1) {
          if (isset($_GET['email_cert'])) {
               $mm = new MailManager($person,
                                     Config::get_config('sys_from_address'),
                                     "Here is your newly signed certificate", 
                                     "Attached is your new certificate. Remember to store this in $HOME/.globus/usercert.pem for ARC to use");
               $mm->add_attachment($res[0]['cert'], 'usercert.pem');
               if (!$mm->send_mail()) {
                    echo "Could not send mail properly!<BR>\n";
               }
               echo "Sent certificate via email!<br>\n";
          }
          else if (isset($_GET['file_cert'])) {
               require_once('file_download.php');
               download_file($res[0]['cert'], 'usercert.pem');
               $send_res = true;
          }
     }
     return $send_res;
} /* end send_cert */

function show_db_csr()
{
     global $person;
     $res = MDB2Wrapper::execute("SELECT csr_id, uploaded_date, from_ip, common_name, auth_key FROM csr_cache WHERE common_name=? ORDER BY uploaded_date DESC",
                                 array('text'),
                                 array($person->get_common_name()));
     echo "<B>Certificate Signing Requests (CSRs)</B><BR>\n";
     echo "<table class=\"small\">\n";

     if (count($res) > 0) {
          echo "<tr><th>AuthToken</th><th>Uploaded</th><th>From IP</th><th>Owner</th></tr>\n";
          $counter = 0;
          while($counter < count($res)) {
               $row = $res[$counter];
               $counter++;
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
     echo "<BR><BR><BR>\n";
}
function show_db_cert() 
{
     global $person;
     $res = MDB2Wrapper::execute("SELECT cert_id, auth_key, cert_owner, valid_untill FROM cert_cache WHERE cert_owner=?",
                                 array('text'),
                                 array($person->get_common_name()));
     echo "<B>Certificates:</B><BR>\n";
     echo "<table class=\"small\">\n"; 
     if (count($res) > 0) {
          echo "<tr><th>AuthToken</th><th>owner</th></tr>\n";
          $counter = 0;
          while($counter < count($res)) {
               $row = $res[$counter];
               $counter++;
               echo "<tr>\n";
               echo "<td>".$row['auth_key']."</td>\n";
               echo "<td>".$row['cert_owner']."</td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?delete_cert=".$row['cert_id']."\">Delete</A></td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?email_cert=".$row['cert_id']."\">Email cert</A></td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?file_cert=".$row['cert_id']."\">Download cert</A></td>\n";
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
     echo "<pre>./create_cert.sh -get ".htmlentities("<AuthToken>")."</pre>\n";
     echo "create_cert.sh will remember the last used AuthToken. If the script fails, try to pass \n";
     echo "the auth-token from the list above as a parameter to the script as showed above.<BR>\n";

     echo "<BR><BR><BR>\n";
} /* end show_db_cert() */

function inspect_csr($csr_id) {
	global $person;
	$loc_id=sanitize_id($csr_id);
        $res = MDB2Wrapper::execute("SELECT * FROM csr_cache WHERE csr_id=? AND common_name=?",
                                    array('integer', 'text'),
                                    array($loc_id, $person->get_common_name()));
	if(count($res) == 1) {
             $csr = $res[0]['csr'];
             echo "<BR>Showing CSR with auth-token " .$res[0]['auth_key'] . " from database:<BR>\n";
             echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?delete_csr=".$loc_id."\">Delete from Database</A> ]\n";
             echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?auth_token=".$csr_array['auth_key']."\">Approve for signing</A> ]\n";

             /* print subject */
             $subj = openssl_csr_get_subject($csr, false);
             echo "<table>\n";
             foreach ($subj as $key => $value)
                  echo "<tr><td>$key</td><td>$value</td></tr>\n";
             echo "</table>\n";
	}
} /* end inspect_csr() */

function inspect_cert($cert_id)
{
	global $person;
	$loc_id=sanitize_id($cert_id);
        $res = MDB2Wrapper::execute("SELECT * FROM cert_cache WHERE cert_id=? AND cert_owner=?",
                                    array('integer', 'text'),
                                    array($loc_id, $person->get_common_name()));
	if(count($res) == 1) {
             $csr_test = openssl_x509_read($res[0]['cert']);
             if (openssl_x509_export($csr_test, $text, false)) {
                  echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?delete_cert=$cert_id\">Delete from Database</A> ]\n";
                  echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?email_cert=$cert_id\">Send by email</A> ]\n";
                  echo "[ <A HREF=\"".$_SERVER['PHP_SELF']."?file_cert=$cert_id\">Download</A> ]\n";
                  echo "<PRE>$text</PRE>\n";
             }
	}
}

function delete_csr($csr_id) {
	global $person;
	$loc_id=sanitize_id($csr_id);
        $res = MDB2Wrapper::execute("SELECT * FROM csr_cache WHERE csr_id=? AND common_name= ?",
                                    array('integer', 'text'),
                                    array($loc_id, $person->get_common_name()));
        $hits = count($res);
	if ($hits== 1) {
             MDB2Wrapper::update("DELETE FROM csr_cache WHERE csr_id=? AND common_name= ?",
                                 array('integer', 'text'),
                                 array($loc_id, $person->get_common_name()));
             Logger::log_event(LOG_NOTICE, "Dropping CSR with hash ".pubkey_hash($hits['csr'])." belonging to ".$person->get_common_name()." originating from ".$_SERVER['REMOTE_ADDR']."");
	}
	else {
		if ($hits==0) {
			echo "No matching CSR found.<BR>\n";
			Logger::log_event(LOG_NOTICE, "Could not delete given CSR with id ".$loc_id." from ip ".$_SERVER['REMOTE_ADDR'] . " : " . $person->get_common_name() . " Reason: not found");
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
        $res = MDB2Wrapper::execute("SELECT * FROM cert_cache WHERE cert_id=? AND cert_owner=?",
                                    array('integer', 'text'),
                                    array($loc_id, $person->get_common_name()));
	$hits=count($res);
	if ($hits== 1) {
             MDB2Wrapper::update("DELETE FROM cert_cache WHERE cert_id=? AND cert_owner=?",
                                 array('integer', 'text'),
                                 array($loc_id, $person->get_common_name()));
             Logger::log_event(LOG_NOTICE, "Dropping CERT with ID ".$loc_id." belonging to ".$person->get_common_name());
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

