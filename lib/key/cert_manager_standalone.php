<?php

require_once 'person.php';
require_once 'cert_manager.php';
require_once 'key_sign.php';
require_once 'mdb2_wrapper.php';
require_once 'db_query.php';
require_once 'pw.php';
require_once 'cert_lib.php';
require_once 'CGE_KeyRevokeException.php';
/*
 * CertManager_Standalone Standalone-CA extension for CertManager.
 *
 * Class for signing CSRs with locally available CA certificates and storing, retrieving
 * and listing the issued certificates.
 *
 * PHP version 5
 * @author: Henrik Austad <henrik.austad@uninett.no>
 * @author: Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class CertManager_Standalone extends CertManager
{
	/**
	 * Verify if the subject DN matches the received sets of attributes.
	 * Sign a key using the local CA-key.
	 * Store the public key of the request in the database.
	 *
	 * @throws: KeySignException
	 */
	public function sign_key($auth_key, $csr)
	{
		/* Is the requried attributes present? */
		$testAttrs = $this->verifyAttributes();
		if ($testAttrs != null) {
			$msg  = "Error(s) with attributes:<br />\n";
			$msg .= "<ul>$testAttrs</ul>\n";
			$msg .= "<br />\n";
			$msg .= "This means that you do <b>not</b> qualify for certificates at this point in time.<br />\n";
			$msg .= "Please contact your local IT-support to resolve this issue.<br />\n";
			throw new KeySignException($msg);
		}

		if ($this->verify_csr($csr)) {
			$cert_file_name	= tempnam("/tmp/", "REV_CERT_");
			$cert_file = fopen($cert_file_name, "w");
			fclose($cert_file);

			$path = dirname(dirname(dirname(__FILE__))) . "/cert_handle/sign_key.sh";
			if (!file_exists($path)) {
				throw new KeySignException("sign_key.sh does not exist!");
			}
			$cmd = "$path $auth_key $cert_file_name";
			$res = shell_exec($cmd);
			$val = split("\n", $res);

			/* FIXME: add better logic here.
			 */
			switch((int)$val[0]) {
			case 0:
				break;
			default:
				throw new KeySignException("Unable to sign certificate (" . $val[1] . ")");
			}

			if (!file_exists($cert_file_name)) {
				$errorCode = create_pw(8);
				$msg     = "Cannot find temporar certificate file. Please forward the following ";
				$msg    .= "error-code to the aministrators: [$errorCode]";
				$logMsg	 = "Temporary certificate file vanished before it could be read. ";
				$logMsg .= "Please investigate.";
				Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ . "[errorCode] $logMsg");
				throw new FileNotFoundException($msg);
			}
			$cert = file_get_contents($cert_file_name);
			unlink($cert_file_name);

			if ($cert == null || $cert == "") {
				$msg  = "Unable to sign certificate using backend scripts.<br />\n";
				$msg .= "The certificate was not found in local file ($cert_file_name) where it was expected to be.<br />\n";
				throw new KeySignException($msg);
			}
			$cert_array = openssl_x509_parse($cert);
			$diff = (int)$cert_array['validTo_time_t'] - (int)$cert_array['validFrom_time_t'];
			$timeout = array($diff, 'SECOND');

			try {
				$insert  = "INSERT INTO cert_cache (cert, auth_key, cert_owner, organization, valid_untill) ";
				$insert .= "VALUES(?, ?, ?, ?, timestampadd($timeout[1], $timeout[0],current_timestamp()))";
				MDB2Wrapper::update($insert,
						    array('text', 'text', 'text', 'text'),
						    array($cert,
							  $auth_key,
							  $this->person->getX509ValidCN(),
							  $this->person->getSubscriberOrgName()));
			} catch (DBStatementException $dbse) {
				$error_key = create_pw(8);
				Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ .
						  " Error in query-syntax. Make sure the query matches the db-schema. ($error_key)");
				throw new KeySignException("Cannot insert certificate into database.<BR />error-reference: $error_key");
			} catch (DBQueryException $dbqe) {
				$error_key = create_pw(8);
				Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ .
						  " Error with values passed to the query. Check for constraint-violations");
				throw new KeySignException("Cannot insert certificate into database.<BR />error-reference: $error_key");
			}
		
			$this->sendMailNotification($auth_key, date('Y-m-d H:i'), $_SERVER['REMOTE_ADDR']);
			Logger::log_event(LOG_INFO, "Certificate successfully signed for ".
					  $this->person->getX509ValidCN() .
					  " Contacting us from ".
					  $_SERVER['REMOTE_ADDR']);
		} else {
			Logger::log_event(LOG_INFO, "Will not sign invalid CSR for user ".
					  $this->person->getX509ValidCN() .
					  " from ip ".$_SERVER['REMOTE_ADDR']);
			throw new KeySignException("CSR subject verification failed!");
		}
	} /* end sign-key */

    /**
     * Retrieve a list of the certificates associated with the managed person
     * from the database
     *
     * @throws DBQueryException
     */
    public function get_cert_list()
    {
        $res = MDB2Wrapper::execute("SELECT cert, auth_key, cert_owner, valid_untill FROM cert_cache WHERE ".
				    "cert_owner=? AND valid_untill > current_timestamp()",
				    array('text'),
				    array($this->person->getX509ValidCN()));
        $num_received = count($res);
        if ($num_received > 0 && !(isset($res[0]['auth_key']))) {
            $msg = "Received an unexpected response from the database for user " .
                     $this->person->getEPPN();
            throw new DBQueryException($msg);
        }
	foreach ($res as $key => $cert) {
		$tmp = openssl_x509_serial($cert['cert']);
		$res[$key]['serial'] = $tmp;
	}
        return $res;
    } /* end get_cert_list */


    /**
     * Get a list of certificates for all the persons matched by the $common_name,
     * which may include one or more '%' wildcard characters.
     *
     * @param string $common_name Query for certificate owners with a certain
     *        common name, possibly including one or more '%' wildspace
     *        characters
     * @param string $org The organization to which the search is restricted
     *
     * @return Array with results with entries of the form
     *          array('cert_owner','auth_key')
     */
    public function get_cert_list_for_persons($common_name, $org) {
        $query = "SELECT auth_key, cert_owner, valid_untill FROM cert_cache WHERE " .
                 "cert_owner LIKE ? AND organization = ?";
        $res = MDB2Wrapper::execute($query, array('text','text'),
                                            array($common_name, $org)
        );

        return $res;
    }

    public function signBrowserCSR($csr, $browser)
    {
	/* I am feeling all stubby */
    }

    public function pollCertStatus($key)
    {
	/* I am feeling all stubby */
    }

    /*
     * Get the certificate bound to key $key from the database
     *
     * @throws ConfusaGenException
     */
    public function get_cert($key)
    {
        $res = MDB2Wrapper::execute("SELECT cert FROM cert_cache WHERE auth_key=? AND cert_owner=? AND valid_untill > current_timestamp()",
                                      array('text', 'text'),
                                      array($key, $this->person->getX509ValidCN()));

        if (count($res) == 1) {
		$msg  = "Sending certificate with hash " . pubkey_hash($res[0]['cert'], false) . " ";
		$msg .= " and auth-token $key to user from ip " . $_SERVER['REMOTE_ADDR'];
		Logger::log_event(LOG_NOTICE, $msg);
		return $res[0]['cert'];
        }
        else {
            $msg = "Error in getting certificate, got " . count($res) . " results\n";
            $cn = $this->person->getX509ValidCN();
            $msg .= "Queried for key $key and CN $cn\n";
            throw new DBQueryException($msg);
        }
    }

    public function getCertDeploymentScript($key, $browser)
    {
	/* TODO: I am feeling all stubby */
	return "<script type=\"text/javascript\">var g_ccc=\"\"</script>";
    }

    /**
     * deleteCertFromDB - delete a certificate from the database.
     */
    public function deleteCertFromDB($key)
    {
	    if (!isset($key) || $key == "")
		    return;

	    /* remove the certificate from the database */
	    try {
		    MDB2Wrapper::update("DELETE FROM cert_cache WHERE auth_key=?", array('text'), array($key));
		    Logger::log_event(LOG_NOTICE, "Removed the certificate ($key) from the database ");

	    } catch (DBStatementException $dbse) {
		    $msg  = __FILE__ . ":" . __LINE__ . " Error in query syntax.";
		    Logger::log_event(LOG_NOTICE, $msg);
		    $msg .= "<BR />Could not delete the certificate with hash: $key.<br />Try to do a manual deletion.";
		    $msg .=	"<BR />Server said: " . $dbse->getMessage();
		    Framework::error_output($msg);

		    /* Even though we fail, the certificate was
		     * successfully revoked, thus the operation was
		     * semi-successful. But, true should indicate that
		     * *everything* went well */
		    return false;
	    } catch (DBQueryException $dbqe) {
		    $msg  = __FILE__ . ":" . __LINE__ . " Query-error. Constraint violoation in query?";
		    Logger::log_event(LOG_NOTICE, $msg);
		    $msg .= "<BR />Server said: " . $dbqe->getMessage();
		    Framework::error_output($msg);
		    return false;
	    }
	    return true;
    }

    /*
     * Revoke the certificate identified by key
     * Key is an auth_var
     */
    public function revoke_cert($key, $reason)
    {
	    /* TODO: method stub
	     *
	     * At a first glance there seems to be no revoke function in php-openssl.
	     * shell_exec('openssl ca -revoke...') would be possible but... eew...
	     * Generously leaving this decision to Henrik ;-)
	     *
	     */
	    $cmd = "./../cert_handle/revoke_cert.sh $key";
	    $res = exec($cmd, $output, $return);
	    foreach ($output as $line) {
		    $msg .= $line . "<BR />\n";
	    }
	    if ((int)$return != 0) {
		    Logger::log_event(LOG_NOTICE, "Problems revoking certificate for " .
				      $this->person->getX509SubjectDN() . "($key)");
		    throw new CGE_KeyRevokeException($msg);
	    }
	    Logger::log_event(LOG_NOTICE, "Revoked certificate $key for user " .
			      $this->person->getX509SubjectDN());

	    if (!$this->deleteCertFromDB($key)) {
		    Logger::log_event(LOG_NOTICE, "Could not delete certificate ($key) from database, revocation only partially completed.");
	    }

	    /* Publish the updated CRL */
	    $crlFile	= Config::get_config('install_path') . Config::get_config('ca_cert_base_path') . Config::get_config('ca_crl_name');
	    $pubCADir	= Config::get_config('install_path') . "www/ca";
	    $pubCrlFile	= $pubCADir . Config::get_config('ca_crl_name');
	    if (file_exists($crlFile)) {
		    if (!is_dir($pubCADir)) {
			    if (!is_writable(dirname($pubCADir))) {
				    $msg  = "CA-dir does not exist, and the webserver does not have write-access to web-dir.<BR />";
				    $msg .= "Please contact a site-administrator and notify about this problem.";
				    $msg .= "We are unable to publish the CRL's at this time.<BR />";
				    throw new ConfusaGenException($msg);
			    }
			    /* Should work, but in case it fails, return */
			    if (!mkdir($pubCADir, 0755, true)) {
				    Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ .
						      " tried to create www/ca and thought the permissions where in order, but alas! They weren't.");
				    return false;
			    }
		    }

		    /* First time we publish, public CRL does not exist. */
		    if (!file_exists($pubCrlFile)) {
			    if (is_writable($pubCADir)) {
				    if (!copy($crlFile, $pubCrlFile)) {
					    Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ . " " .
							      "Could not write to $crlFile, not allowed to create file. " .
							      "CRLs will not be published until this is fixed.");
					    return false;
				    }
			    } else {
				    Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ . " " .
						      "Want to publish CRL but not allowed to create new file in $pubCADir. " .
						      "Need this to public CRLs. Please create file $pubCrlFile writable for the webserver.");
				    return false;
			    }
		    } else if (filemtime($crlFile) > filemtime($pubCrlFile)) {
			    /* Need to update CRL (will be true in most cases) */

			    if (!is_writable($pubCrlFile)) {
				    Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ . " "  .
						      "Want to publish CRL but not allowed to write to $pubCrlFile. " .
						      "Please make this file writable for the webserver.");
				    /* This could be returned as false,
				     * however, the certificate has been
				     * revoked, we just cannot publish it. */
				    return true;
			    }
			    if (!copy($crlFile, $pubCrlFile)) {
				    Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ . " "  .
						      "Could not write to $crlFile, not allowed to create file. "  .
						      "CRLs will not be published until this is fixed.");
				    return false;
			    }
		    }
	    } else {
		    Logger::log_event(LOG_ALERT, "Expected to find $crlFile, but it is nowhere to be found!");
		    return false;
	    }
	    Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ . " "  .
			      "Successfully updated $pubCrlFile.");
	    return true;
    } /* end revoke_cert() */


    /**
     * verify_csr()
     *
     * This function will test the CSR against several fields.
     * It will test the subject against the person-attributes (which in turn are
     * gathered from simplesamlphp-attributes (Feide, surfnet etc).
     *
     * @param String The CSR in base64 PEM format
     * @return Boolean True if valid CSR
     */
  private function verify_csr($csr)
  {
       /* by default, the CSR is valid, we then try to prove that it's invalid
        *
        * A better approach could be to distrust all CSRs and try to prove that
        * they are OK, however this leads to messy code (as the tests becomes
        * somewhat more involved) and I'm not convinced that it will be any safer.
        */
	  if (!isset($csr)) {
		  Framework::error_output( __FILE__ . ":" . __LINE__ . " CSR not provided by caller1");
		  return false;
	  }

	  $subject= openssl_csr_get_subject($csr);
               /* check fields of CSR to predefined values and user-specific values
                * Make sure that the emailAddress is not set, as this is
                * non-compatible with ARC.
                */
               if (isset($subject['emailAddress'])) {
		       Framework::error_output("will not accept email in DN of certificate. Download latest version of script.");
		    return false;
               }
	       else if (!match_dn($subject, $this->person)) {
		       $msg = "";
		       $msg .= "Error in subject! <BR/>\n";
		       $msg .= "The fields in your CSR was not set properly.<BR>\n";
		       $msg .= "To try again, please download a new version of the script, ";
		       $msg .= "generate a new key and upload again.<BR>\n";
		       Framework::error_output($msg);
		       return false;
               }
	       return true;
    } /* end verify_csr */


} /* end class CertManager_Standalone */
?>
