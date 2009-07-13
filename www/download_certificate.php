<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';

$fw = new Framework('download_cert');
$fw->force_login();
$fw->render_page();

function download_cert($person)
{
	echo "<H3>Certificate Download Area</H3>\n";
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


?>