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
if (process_csr_flags_set() || process_cert_flags_set()){
	$fw->force_login();
}

$fw->render_page();
echo "humm?";
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
	    if (!process_db())
		    /* process uploaded csr's (or show the upload form) */
		    process_file_csr();
    }
  else {
	  include('unclassified_intro.php');
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
			if ($cm->sign_key(pubkey_hash($fu->get_content(), true)))
				return true;
                }
                echo "<FONT COLOR=\"RED\"><B>\n";
                echo "There were errors encountered when processing the file.<BR>\n";
                echo "Please create a new keypair and upload a new CSR.<BR>\n";
                echo "</B></FONT>\n";
        }
        include('upload_form.html');
        return false;
}

function process_db()
{
	/* look in the database and see if there's any waiting csrs
	 * (automatically uploaded). */
	$res = process_db_csr();

	/* look in the database to see if there's any CSR awaiting retrieval
	 * */
	if (!$res)
		$res = process_db_cert();

	return $res;
}

/* process_[csr|cert]_flags_set()
 *
 * If any of the flags used for processing either CSR's or certificates are set,
 * the user must authenticate. This gives a wrapper to simplify tests.
 */
function process_csr_flags_set()
{
	return isset($_GET['delete_csr']) || isset($_GET['auth_token']) || isset($_GET['inspect_csr']);
}
function process_cert_flags_set()
{
	return isset($_GET['delete_cert']) || isset($_GET['inspect_cert']);
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
	$res = false;
	if (isset($_GET['delete_csr'])) {
             $res = delete_csr(htmlentities($_GET['delete_csr']));
	}
        if (isset($_GET['auth_token'])){
             $res = approve_csr(htmlentities($_GET['auth_token']));
	}
	else if (isset($_GET['inspect_csr'])) {
             $res = inspect_csr(htmlentities($_GET['inspect_csr']));
	}

	if (!$res) {
		require_once('send_element.php');
		set_value($name='inspect_csr', 'index.php', 'Inspect CSR', 'GET');
	}
	return $res;
}

function process_db_cert()
{
     global $person;
     $res = false;
     if(isset($_GET['delete_cert'])) {
          $res = delete_cert(htmlentities($_GET['delete_cert']));
     }
     else if (isset($_GET['inspect_cert'])) {
          $res = inspect_cert(htmlentities($_GET['inspect_cert']));
     }
     if (!$res) {
	     show_db_cert();
     }
     return $res;
} /* end process_db_cert */

/* approve_csr()
 *
 * This function approves a CSR for signing. It uses the auth-token as a
 * paramenter to find the CSR in the database.
 */
function approve_csr($auth_token)
{
     global $person;
     $status = false;
     $csr_res = MDB2Wrapper::execute("SELECT csr FROM csr_cache WHERE auth_key=? AND common_name=?",
                                     array('text', 'text'),
                                     array($auth_token, $person->get_valid_cn()));
     if (count($csr_res) == 1) {
          $csr = $csr_res[0]['csr'];
          $cm = new CertManager($csr, $person);
          if (!$cm->sign_key($auth_token)) {
               echo __FILE__ .":".__LINE__." Error signing key<BR>\n";
               return false;
          }
	  MDB2Wrapper::update("DELETE FROM csr_cache WHERE auth_key=? AND common_name=?",
			      array('text', 'text'),
			      array($auth_token, $person->get_valid_cn()));
	  return true;
     }
     echo __FILE__ .":".__LINE__." error getting CSR from database<BR>\n";
     return false;
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
     $auth_key = "";
     if (isset($_GET['email_cert']))
          $auth_key = htmlentities($_GET['email_cert']);
     else if (isset($_GET['file_cert']))
          $auth_key = htmlentities($_GET['file_cert']);

    if (Config::get_config('standalone')) {
      $res = MDB2Wrapper::execute("SELECT cert FROM cert_cache WHERE auth_key=? AND cert_owner=?",
                                     array('integer', 'text'),
                                     array($auth_key, $person->get_valid_cn()));
    } else {
      $res = get_remote_cert($auth_key);
    }

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

/*
 * Get a certificate from a remote endpoint (e.g. Comodo) to send it to the user. Maybe we can think about caching the certs locally for some time, in order
 * not to have to make remote calls all the time.
 */
function get_remote_cert($auth_key) {
   global $person;
   $return_res = array();
   $res = MDB2Wrapper::execute("SELECT collection_code, order_number FROM order_store WHERE auth_key=? AND common_name=?",
                                  array('text', 'text'),
                                  array($auth_key, $person->get_valid_cn()));

    //  echo "orderNumber " . $res[0]['order_number'] . " collectionCode " . $res[0]['collection_code'] . " count " . count($res) . "<br />\n";

      if (count($res) >= 1) {

        Logger::log_event(LOG_NOTICE, "Looked up $authvar in the order-store. Trying to retrieve certificate with order number " .
                                      $res[0]['order_number'] .
                                      " from the Comodo collect API. Sending to user with ip " .
                                      $_SERVER['REMOTE_ADDR']);

        $collectionCode = $res[0]['collection_code'];
        $collect_endpoint = Config::get_config('capi_collect_endpoint') . "?collectionCode=" . $collectionCode . "&queryType=2";
        $postfields_download["responseType"]="2";
        $postfields_download["responseEncoding"]="0";
        $postfields_download["responseMimeType"]="application/x-x509-user-cert";

        $ch = curl_init($collect_endpoint);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $data=curl_exec($ch);
        curl_close($ch);

        /* The first character of the return message is always a status code
         */
        $status=(int)$data;

        if ($status == 0) {
          echo "The certificate is being processed and is not yet available<br />\n";
        } else if ($status == 2) {
          $return_res[0]['cert'] = substr($data,2);
        } else {
          echo "Received error message $data <br />\n";
        }
      }

    return $return_res;
}

/* show_db_cert
 *
 * Retrieve certificates from the database and show them to the user
 */
function show_db_cert()
{
	global $person;
  if (Config::get_config('standalone')) {
    $res = MDB2Wrapper::execute("SELECT auth_key, cert_owner, valid_untill FROM cert_cache WHERE cert_owner=? AND valid_untill > current_timestamp()",
              array('text'),
              array($person->get_valid_cn()));
  } else {
    $res = MDB2Wrapper::execute("SELECT auth_key, common_name AS 'cert_owner' FROM order_store WHERE common_name=?",
              array('text'),
              array($person->get_valid_cn()));
  }
	$num_received = count($res);
	if ($num_received > 0 && isset($res[0]['auth_key'])) {
		$counter = 0;
		echo "<table class=\"small\">\n";
		echo "<tr>";
		echo "<th></th>\n";
		echo "<th></th>\n";
		echo "<th></th>\n";
		echo "<th></th>\n";
		echo "<th>AuthToken</th>";
		echo "<th>Owner</th>";
		echo "</tr>\n";
		while($counter < $num_received) {
			$row = $res[$counter];
			$counter++;
			echo "<tr>\n";
      if (Config::get_config('standalone')) {
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?email_cert=".$row['auth_key']."\">Email</A> ]</td>\n";
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?file_cert=".$row['auth_key']."\">Download</A> ]</td>\n";
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?inspect_cert=".$row['auth_key']."\">Inspect</A> ]</td>\n";
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?delete_cert=".$row['auth_key']."\">Delete</A> ]</td>\n";
      } else {
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?email_cert=".$row['auth_key']."\">Email</A> ]</td>\n";
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?file_cert=".$row['auth_key']."\">Download</A> ]</td>\n";
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?inspect_cert=".$row['auth_key']."\">Inspect</A> ]</td>\n";
        /* deletion of a certificate won't make sense with the remote API. When we implement the remote-revocation-API we can provide a revoke link here. */
        echo "<td></td>\n";
      }
			echo "<td>".$row['auth_key']."</td>\n";
			echo "<td>".$row['cert_owner']."</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
	echo "<br>\n";
} /* end show_db_cert() */

/* inspect_csr
 *
 * Let the user view detailed information about a CSR (belonging to the user) to
 * help decide whether or not it should be signed.
 */
function inspect_csr($auth_token) {
	global $person;
	$status = false;
        $res = MDB2Wrapper::execute("SELECT * FROM csr_cache WHERE auth_key=? AND common_name=?",
                                    array('text', 'text'),
                                    array($auth_token, $person->get_valid_cn()));
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
	     $status = true;
	} else {
		echo "<BR><FONT COLOR=\"RED\"><B>\n";
		echo "Error with auth-token. Not found. Please verify that you have entered the correct auth-url and try again<BR>\n";
		echo "If this problem persists, try to download a new version of the tool and try again<BR>\n";
		echo "<BR>\n";
		echo "</FONT></B>\n";

	}
	return $status;
} /* end inspect_csr() */


/* inspect_cert
 *
 * This function will 'verbosify' a certificate with given cert_id.
 * Basically it will print it in human-readable form and let the user verify it.
 */
function inspect_cert($auth_key)
{
	global $person;
	$status = false;
  if (Config::get_config('standalone')) {
        $res = mdb2wrapper::execute("select * from cert_cache where auth_key=? and cert_owner=?",
                                    array('text', 'text'),
                                    array($auth_key, $person->get_valid_cn()));
  } else {
    $res = get_remote_cert($auth_key);
  }

	if(count($res) == 1) {
		echo "<BR>\n";
		echo "<BR>\n";
		$csr_test = openssl_x509_read($res[0]['cert']);
		if (openssl_x509_export($csr_test, $text, false)) {
			echo "[ <a href=\"".$_server['php_self']."?email_cert=$auth_key\">Email</a> ]\n";
			echo "[ <a href=\"".$_server['php_self']."?file_cert=$auth_key\">Download</a> ]\n";
			echo "[ <B>Inspect</B> ]\n";
			if (Config::get_config('standalone')) {
			  echo "[ <a href=\"".$_server['php_self']."?delete_cert=$auth_key\">Delete</a> ]\n";
      }
			echo "<pre>$text</pre>\n";
			$status = true;
		} else {
			/* not able to show it properly, dump content to screen */
			echo "There were errors encountered when formatting the certificate. Here is a raw-dump.<BR>\n";
			echo "<PRE>\n";
			print_r ($res);
			echo "</PRE>\n";
		}
	}
	return $status;
}

/* delete_csr
 *
 * Remove the csr with given id from the database.
 * It will check that the CSR belongs to the user in question.
 */
function delete_csr($auth_token) {
	global $person;
	$status = false;
        $res = mdb2wrapper::execute("select * from csr_cache where auth_key=? and common_name= ?",
                                    array('text', 'text'),
                                    array($auth_token, $person->get_valid_cn()));
        $hits = count($res);
	if ($hits== 1) {
             mdb2wrapper::update("delete from csr_cache where auth_key=? and common_name= ?",
                                 array('text', 'text'),
                                 array($auth_token, $person->get_valid_cn()));
             logger::log_event(LOG_NOTICE, "dropping csr with hash ".pubkey_hash($res[0]['csr'], true)." belonging to ".$person->get_valid_cn()." originating from ".$_SERVER['REMOTE_ADDR']."");
	     $status = true;
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
	return $status;
} /* end delete_csr() */

/* delete_cert
 *
 * Delete certificate belonging to user with given id from db.
 */
function delete_cert($auth_key)
{
	global $person;
	$status = false;
        $res = MDB2Wrapper::execute("SELECT * FROM cert_cache WHERE auth_key=? AND cert_owner=?",
                                    array('text', 'text'),
                                    array($auth_key, $person->get_valid_cn()));
	$hits=count($res);
        if ($hits==0) {
             echo "No matching Certificate found.<BR>\n";
             Logger::log_event(LOG_NOTICE, "Could not delete given CSR with id ".$auth_key." from ip ".$_SERVER['REMOTE_ADDR']);
        }
	else {
             MDB2Wrapper::update("DELETE FROM cert_cache WHERE auth_key=? AND cert_owner=?",
                                 array('text', 'text'),
                                 array($auth_key, $person->get_valid_cn()));
             Logger::log_event(LOG_NOTICE, "Dropping CERT with ID ".$auth_key." belonging to ".$person->get_valid_cn());
	     $status = true;
	}
	return $status;
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

