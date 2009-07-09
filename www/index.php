<?php 
include_once('framework.php');
include_once('cert_manager.php'); /* for handling of the key */
include_once('file_upload.php'); /* FileUpload */
include_once('csr_lib.php');
include_once('mdb2_wrapper.php');
include_once('logger.php');
include_once('confusa_gen.php');
require_once("output.php");
require_once("pw.php");

$person = null;
$fw = new Framework('keyhandle');

/* test for downloading of certificates */
if (send_cert()) {
     exit(0);
}

/* Test to see if any of the flags that require AuthN are set */
if (process_csr_flags_set() || process_cert_flags_set()){
	$fw->force_login();
}

$fw->render_page();
/* The rest of this file si functions used in the preceding section. */



/**
 * keyhandle - main control function for handling CSRs and certificates
 *
 * It will make sure all CSRs and Certificates stored in the database will be
 * processed and displayed to the user properly.
 *
 * @pers : the person-object associated with this instance. If the person is
 *	   non-AuthN, a unclassified version will be displayed.
 */
function keyhandle($pers) 
{
  global $person;
  $person = $pers;
  if ($person->is_auth()) {
	  switch($person->get_mode()) {
	  case NORMAL_MODE:
		  echo "Showing normal-mode splash<BR>\n";
		  break;
	  case ADMIN_MODE:
		  echo "Showing admin-mode splash<BR>\n";
		  break;
	  default:
		  $code = create_pw(8);
		  error_output("Unknown mode, contact the administrator with this error code " . $code);
		  $msg  = $code . " ";
		  $msg .= "User " . $person->get_common_name() . " was given mode " . $person->get_mode();
		  $msg .= ". This is not a valid mode. Verify content in admins-table";
		  Logger::log_event(LOG_WARNING, $msg);
	  }

	  /* Process awaiting CSR operations (including signing new
	   * certificates) */
	  $csr_process = process_db_csr();

	  /* Certt */
	  process_db_cert();
	  show_db_cert();

	  /* CSR */
	  if (!$csr_process) {
		  require_once('send_element.php');
		  set_value($name='inspect_csr', 'index.php', 'Inspect CSR', 'GET');
	  }
  } else {
	  include('unclassified_intro.php');
  }
} /* end keyhandle() */


/**
 * process_file_csr - walk an uploaded CSR through the steps towards a certificate
 *
 * If a new CSR has been uploaded via FILE, this will retrieve it, store it in
 * the database and pass control over to CertManager to process it. 
 */
function process_file_csr()
{
    global $fw;
    $cm = $fw->get_cert_manager();
	/* process_key($person, 'user_csr'); */
	if(isset($_FILES['user_csr']['name'])) {
		$fu = new FileUpload('user_csr', true, 'test_content');
		if ($fu->file_ok()) {
			/* CertManager will test content of CSR before sending it off for signing
                         *
                         * As we upload the key manually, the user-script won't
                         * be called for creating a auth-token. We therefore
                         * create a random string containing the correct amount
                         * of characters. It will contain more letters than the
                         * user-script (which uses sha1sum of some random text).
                         */
            try {
                $cm->sign_key(pubkey_hash($fu->get_content(), true), $fu->get_content());
            } catch (ConfusaGenException $e) {
                echo $e->getMessage() . "<br />\n";
            }
        } else {
			error_output("There were errors encountered when processing the file.");
			error_output("Please create a new keypair and upload a new CSR.");
        }
    }
        include('upload_form.html');
        return false;
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

function process_db_cert()
{
     $res = false;
     if(isset($_GET['delete_cert'])) {
          $res = delete_cert(htmlentities($_GET['delete_cert']));
     }
     else if (isset($_GET['inspect_cert'])) {
          $res = inspect_cert(htmlentities($_GET['inspect_cert']));
     }
     return $res;
} /* end process_db_cert */

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
     else
          return $send_res;

     try {
      $cm = $fw->get_cert_manager();
      $cert = $cm->get_cert($auth_key);

      if (isset($cert)) {
          if (isset($_GET['email_cert'])) {
               $mm = new MailManager($person,
                                     Config::get_config('sys_from_address'),
                                     "Here is your newly signed certificate", 
                                     "Attached is your new certificate. Remember to store this in $HOME/.globus/usercert.pem for ARC to use");
               $mm->add_attachment($cert, 'usercert.pem');
               if (!$mm->send_mail()) {
                    echo "Could not send mail properly!<BR>\n";
               }
               echo "Sent certificate via email!<br>\n";
          }
          else if (isset($_GET['file_cert'])) {
               require_once('file_download.php');
               download_file($cert, 'usercert.pem');
               $send_res = true;
          }
      }
     } catch (ConfusaGenException $e) {
        echo $e->getMessage();
     }
     return $send_res;
} /* end send_cert */

/* show_db_cert
 *
 * Retrieve certificates from the database and show them to the user
 */
function show_db_cert()
{
	global $person;
    global $fw;
    $cm = $fw->get_cert_manager();
    try {
        $res = $cm->get_cert_list();
    } catch (ConfusaGenException $e) {
        echo $e->getMessage();
    }

	$num_received = count($res);
	if ($num_received > 0) {
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
        echo "<td>".$row['auth_key']."</td>\n";
      } else {
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?email_cert=".$row['order_number']."\">Email</A> ]</td>\n";
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?file_cert=".$row['order_number']."\">Download</A> ]</td>\n";
        echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?inspect_cert=".$row['order_number']."\">Inspect</A> ]</td>\n";
        /* deletion of a certificate won't make sense with the remote API. When we implement the remote-revocation-API we can provide a revoke link here. */
        echo "<td></td>\n";
        echo "<td>".$row['order_number']."</td>\n";
      }
			echo "<td>".$row['cert_owner']."</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
	echo "<br>\n";
} /* end show_db_cert() */

function list_remote_certs()
{
  global $person;
  $list_endpoint = Config::get_config('capi_listing_endpoint');
  $postfields_list["loginName"] = Config::get_config('capi_login_name');
  $postfields_list["loginPassword"] = Config::get_config('capi_login_pw');

  $test_prefix = "";
  if (Config::get_config('capi_test')) {
    /* TODO: this should go into a constant. However, I don't want to put it into confusa_config, since people shouldn't directly fiddle with it */
    $test_prefix = "TEST PERSON ";
  }

  $postfields_list["commonName"] = $test_prefix . $person->get_valid_cn();
  $ch = curl_init($list_endpoint);
  curl_setopt($ch, CURLOPT_HEADER,0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_POST,1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields_list);
  $data=curl_exec($ch);
  curl_close($ch);

  $params=array();
  $res = array();
  parse_str($data, $params);

  if ($params["errorCode"] == "0") {
    for ($i = 1; $i <= $params['noOfResults']; $i = $i+1) {
      $res[$i-1]['order_number'] = $params[$i . "_orderNumber"];
      $res[$i-1]['cert_owner'] = $person->get_valid_cn();
    }
  } else {
    echo "Errors occured when listing user certificates: " . $params["errorMessage"];
  }

  return $res;

} /* end list_remote_certs() */

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
    global $fw;
	$status = false;

    try {
        $cm = $fw->get_cert_manager();
        $cert = $cm->get_cert($auth_key);
        if (isset($cert)) {
            echo "<BR>\n";
            echo "<BR>\n";
            $csr_test = openssl_x509_read($cert);
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
                print_r ($cert);
                echo "</PRE>\n";
            }
        }
    } catch (ConfusaGenException $e) {
        echo $e->getMessage();
    }

	return $status;
}


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

