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

/* $fw->force_login(); */
$fw->render_page();
/* this function contains the main-flow in the program.
 *
 * It will make sure all CSRs and Certificates stored in the database will be
 * displayed to the user.
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
	  include('unclassified_intro.php');
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
 * paramenter to find the CSR in the database.
 */
function approve_csr($auth_token)
{
     global $person;
     $csr_res = MDB2Wrapper::execute("SELECT csr FROM csr_cache WHERE auth_key=? AND common_name=?",
                                     array('text', 'text'),
                                     array($auth_token, $person->get_valid_cn()));
     if (count($csr_res) == 1) {
          $csr = $csr_res[0]['csr'];
          $cm = new CertManager($csr, $person);
          if (!$cm->sign_key($auth_token)) {
               echo __FILE__ .":".__LINE__." Error signing key<BR>\n";
               return;
          }
          else {
               MDB2Wrapper::update("DELETE FROM csr_cache WHERE auth_key=? AND common_name=?",
                                   array('text', 'text'),
                                   array($auth_token, $person->get_valid_cn()));
          }
     }
     else {
          echo __FILE__ .":".__LINE__." error getting CSR from database<BR>\n";
     }
} /* end approve_csr_remote() */

/* send_cert
 *
 * The user can receive a certificate in 2 ways. Either via email or direct download. 
 */
function send_cert()
{
     global $person;
     global $fw;
     $person = $fw->authenticate();
     $send_res = false;

     if (isset($_GET['email_cert']))
          $auth_key = htmlentities($_GET['email_cert']);
     else if (isset($_GET['file_cert']))
          $auth_key = htmlentities($_GET['file_cert']);

     $res = MDB2Wrapper::execute("SELECT cert FROM cert_cache WHERE auth_key=? AND cert_owner=?",
                                 array('integer', 'text'),
                                 array($auth_key, $person->get_valid_cn()));
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

/* show_db_csr
 *
 * Retrieve all CSRs from the database and list them for the user.
 */
function show_db_csr()
{
     global $person;
     $res = MDB2Wrapper::execute("SELECT uploaded_date, from_ip, common_name, auth_key FROM csr_cache WHERE common_name=? ORDER BY uploaded_date DESC",
                                 array('text'),
                                 array($person->get_valid_cn()));
     echo "<B>Certificate Signing Requests (CSRs)</B><BR>\n";
     echo "<table class=\"small\">\n";

     if (count($res) > 0) {
          echo "<tr>";
          echo "<th>AuthToken</th>";
          echo "<th>Owner</th>";
          /* echo "<th>Uploaded</th>"; */
          /* echo "<th>From IP</th>"; */
          echo "</tr>\n";
          $counter = 0;
          while($counter < count($res)) {
               $row = $res[$counter];
               $counter++;
               echo "<tr>\n";
               echo "<td>".$row['auth_key']."</td>\n";
               echo "<td>".$row['common_name']."</td>\n";
               /* echo "<td>".$row['uploaded_date']."</td>\n"; */
               /* echo "<td>".$row['from_ip']."</td>\n"; */
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?auth_token=".$row['auth_key']."\">Sign</A></TD>\n";
              echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?inspect_csr=".$row['auth_key']."\">Inspect</A></TD>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?delete_csr=".$row['auth_key']."\">Delete</A></TD>\n";
               echo "</tr>\n";
          }
     }
     else {
          echo "<tr><td>No CSRs in the database.<BR>Use create_cert.sh to create and upload new CSRs</td></tr>\n";
     }

     echo "</table>\n";
     echo "<br>\n";
     echo "To create and upload a new CSR, download a create_cert.sh script from the Tools-section and then, on your local machine:\n";
     echo "<PRE>";
     echo "chmod u+x create_cert.sh\n";
     echo "./create_cert.sh -new\n";
     echo "</PRE>";
     echo "This will print an auth-url to stdout. Copy and paste this into your browser and follow the steps (you will probably be redirected for authentication) before it is signed";
     echo "<BR><BR><BR>\n";
}

/* show_db_cert
 *
 * Retrieve certificates from the database and show them to the user
 */
function show_db_cert() 
{
     global $person;
     $res = MDB2Wrapper::execute("SELECT auth_key, cert_owner, valid_untill FROM cert_cache WHERE cert_owner=? AND valid_untill > current_timestamp()",
                                 array('text'),
                                 array($person->get_valid_cn()));
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
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?email_cert=".$row['auth_key']."\">Email cert</A></td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?file_cert=".$row['auth_key']."\">Download cert</A></td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?inspect_cert=".$row['auth_key']."\">Inspect</A></td>\n";
               echo "<td><A HREF=\"".$_SERVER['PHP_SELF']."?delete_cert=".$row['auth_key']."\">Delete</A></td>\n";
               echo "</tr>\n";
          }
     }
     else {
          echo "<tr><td>No Certificates in the database<BR>Use create_cert.sh to create and upload a new CSR and use this webform to approve for signing.</td></tr>\n";
     }
     echo "</table>\n";
     echo "<br>\n";
     echo "To download the certificate with the script, use the following command at your local workstation:<BR>\n";
     echo "<pre>./create_cert.sh -get ".htmlentities("<AuthToken>")."</pre>\n";
     echo "create_cert.sh will remember the last used AuthToken. If the script fails, try to pass \n";
     echo "the auth-token from the list above as a parameter to the script as showed above.<BR>\n";

     echo "<BR><BR><BR>\n";
} /* end show_db_cert() */

/* inspect_csr
 *
 * Let the user view detailed information about a CSR (belonging to the user) to
 * help decide whether or not it should be signed.
 */
function inspect_csr($auth_token) {
	global $person;
        $res = MDB2Wrapper::execute("SELECT * FROM csr_cache WHERE auth_key=? AND common_name=?",
                                    array('text', 'text'),
                                    array($auth_token, $person->get_valid_cn()));
        echo "count: " . count($res) . "<br>\n";
	if(count($res) == 1) {
             $csr = $res[0]['csr'];
             /* print subject */
             $subj = openssl_csr_get_subject($csr, false);
             echo "Details in your CSR:\n";
             echo "<table class=\"small\">\n";
             echo "<tr><td>AuthToken</td><td>".$res[0]['auth_key']."</td></tr>\n";
             foreach ($subj as $key => $value)
                  echo "<tr><td>$key</td><td>$value</td></tr>\n";
             echo "<tr><td>Length:</td><td>".csr_pubkey_length($res[0]['csr']) . " bits</td></tr>\n";
             echo "<tr><td>Uploaded </td><td>".$res[0]['uploaded_date'] . "</td></tr>\n";
             echo "<tr><td>From IP: </td><td>".$res[0]['from_ip'] . "</td></tr>\n";
             echo "<tr><td></td><td></td></tr>\n";
             echo "<tr><td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?delete_csr=".$auth_token."\">Delete from Database</A> ]</td>\n";
             echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?auth_token=".$auth_token."\">Approve for signing</A> ]</td></tr>\n";
             echo "</table>\n";
             echo "<BR>\n";
	}
} /* end inspect_csr() */


/* inspect_cert
 *
 * This function will 'verbosify' a certificate with given cert_id.
 * Basically it will print it in human-readable form and let the user verify it.
 */
function inspect_cert($auth_key)
{
	global $person;
        $res = mdb2wrapper::execute("select * from cert_cache where auth_key=? and cert_owner=?",
                                    array('text', 'text'),
                                    array($auth_key, $person->get_valid_cn()));
	if(count($res) == 1) {
             $csr_test = openssl_x509_read($res[0]['cert']);
             if (openssl_x509_export($csr_test, $text, false)) {
                  echo "[ <a href=\"".$_server['php_self']."?delete_cert=$auth_key\">delete from database</a> ]\n";
                  echo "[ <a href=\"".$_server['php_self']."?email_cert=$auth_key\">send by email</a> ]\n";
                  echo "[ <a href=\"".$_server['php_self']."?file_cert=$auth_key\">download</a> ]\n";
                  echo "<pre>$text</pre>\n";
             }
	}
}

/* delete_csr
 *
 * Remove the csr with given id from the database.
 * It will check that the CSR belongs to the user in question.
 */
function delete_csr($auth_token) {
	global $person;
        $res = mdb2wrapper::execute("select * from csr_cache where auth_key=? and common_name= ?",
                                    array('text', 'text'),
                                    array($auth_token, $person->get_valid_cn()));
        $hits = count($res);
	if ($hits== 1) {
             mdb2wrapper::update("delete from csr_cache where auth_key=? and common_name= ?",
                                 array('text', 'text'),
                                 array($auth_token, $person->get_valid_cn()));
             logger::log_event(LOG_NOTICE, "dropping csr with hash ".pubkey_hash($res[0]['csr'], true)." belonging to ".$person->get_valid_cn()." originating from ".$_SERVER['REMOTE_ADDR']."");
	}
	else {
		if ($hits==0) {
			echo "No matching CSR found.<BR>\n";
			Logger::log_event(LOG_NOTICE, "Could not delete given CSR from ip ".$_SERVER['REMOTE_ADDR'] . " : " . $person->get_valid_cn() . " Reason: not found");
		}
		else {
			echo "Too many hits (".$hits.") in database<BR>\n";
			Logger::log_event(LOG_WARNING, "Error in deleting CSR, got several matches on query (".$hits.") with id ".$loc_id."(" . $person->get_valid_cn() .") Ran query " . $update);
		}
	}
} /* end delete_csr() */

/* delete_cert
 *
 * Delete certificate belonging to user with given id from db.
 */
function delete_cert($auth_key)
{
	global $person;
        $res = MDB2Wrapper::execute("SELECT * FROM cert_cache WHERE auth_key=? AND cert_owner=?",
                                    array('text', 'text'),
                                    array($auth_key, $person->get_valid_cn()));
	$hits=count($res);
	if ($hits== 1) {
             MDB2Wrapper::update("DELETE FROM cert_cache WHERE auth_key=? AND cert_owner=?",
                                 array('text', 'text'),
                                 array($auth_key, $person->get_valid_cn()));
             Logger::log_event(LOG_NOTICE, "Dropping CERT with ID ".$auth_key." belonging to ".$person->get_valid_cn());
	}
	else {
		if ($hits==0) {
			echo "No matching Certificate found.<BR>\n";
			Logger::log_event(LOG_NOTICE, "Could not delete given CSR with id ".$auth_key." from ip ".$_SERVER['REMOTE_ADDR']);
		}
		else {
			echo "Too many hits (".$hits.") in database<BR>\n";
			Logger::log_event(LOG_WARNING, "Error in deleting Certificate, got several matches on query (".$hits.") with id ".$auth_key." ");
		}
        }
}

/* sanitize_id
 *
 * Make sure that the id is an id an nothing more.
 */
function sanitize_id($id) {
     /* as PHP will fail to convert characters to an integer (will result in
      * '0'), this is a 'safe' test */
     return (int) htmlentities($id);
}
?>

