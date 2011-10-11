<?php

require_once 'Person.php';
require_once 'CA.php';
require_once 'key_sign.php';
require_once 'MDB2Wrapper.php';
require_once 'db_query.php';
require_once 'pw.php';
require_once 'cert_lib.php';
require_once 'CGE_KeyRevokeException.php';
/*
 * CA_Standalone Standalone-CA extension for CA.
 *
 * Class for signing CSRs with locally available CA certificates and storing, retrieving
 * and listing the issued certificates.
 *
 * PHP version 5
 * @author: Henrik Austad <henrik.austad@uninett.no>
 * @author: Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class CA_Standalone extends CA
{
	/**
	 * Verify if the subject DN matches the received sets of attributes.
	 * Sign a key using the local CA-key.
	 * Store the public key of the request in the database.
	 *
	 * @throws: KeySignException
	 */
	public function signKey($csr)
	{
		if (!$this->person->getSubscriber()->isSubscribed()) {
			throw new KeySignException("Subscriber not subscribed, cannot create certificate!");
		}
		$auth_key = $csr->getAuthToken();
		if ($this->verifyCSR($csr->getPEMContent())) {
			$cert_file_name	= tempnam("/tmp/", "REV_CERT_");
			$cert_file = fopen($cert_file_name, "w");
			fclose($cert_file);

			$path = dirname(dirname(dirname(__FILE__))) . "/cert_handle/sign_key.sh";
			if (!file_exists($path)) {
				throw new KeySignException("sign_key.sh does not exist!");
			}

			$cmd = "$path $auth_key $cert_file_name " . ConfusaConstants::$OPENSSL_SERIAL_FILE . " " .
			       $this->validityDays;
			$res = shell_exec($cmd);
			$val = explode("\n", $res);

			/* FIXME: add better logic here.
			 */
			switch((int)$val[0]) {
			case 0:
				break;
			default:
				throw new KeySignException("Unable to sign certificate (" . $val[1] . ")");
			}

			if (!file_exists($cert_file_name)) {
				$errorCode = PW::create(8);
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
							  $this->person->getSubscriber()->getIdPName()));
			} catch (DBStatementException $dbse) {
				$error_key = PW::create(8);
				Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ .
						  " Error in query-syntax. Make sure the query matches the db-schema. ($error_key)");
				throw new KeySignException("Cannot insert certificate into database.<BR />error-reference: $error_key");
			} catch (DBQueryException $dbqe) {
				$error_key = PW::create(8);
				Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ .
						  " Error with values passed to the query. Check for constraint-violations");
				throw new KeySignException("Cannot insert certificate into database.<BR />error-reference: $error_key");
			}

			$timezone = new DateTimeZone($this->person->getTimezone());
			$dt = new DateTime("now", $timezone);

			CA::sendMailNotification($auth_key,
			                         $dt->format('Y-m-d H:i T'),
			                         $_SERVER['REMOTE_ADDR'],
			                         $this->person,
			                         $this->getFullDN());
			Logger::log_event(LOG_INFO, "Certificate successfully signed for ".
					  stripslashes($this->person->getX509ValidCN()) .
					  " Contacting us from ".
					  $_SERVER['REMOTE_ADDR']);
		} else {
			Logger::log_event(LOG_INFO, "Will not sign invalid CSR for user ".
					  stripslashes($this->person->getX509ValidCN()) .
					  " from ip ".$_SERVER['REMOTE_ADDR']);
			throw new KeySignException("CSR subject verification failed!");
		}
	} /* end signKey */

    /**
     * Retrieve a list of the certificates associated with the managed person
     * from the database
     *
     * @throws DBQueryException
     */
    public function getCertList()
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
    } /* end getCertList */


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
    public function getCertListForPersons($common_name, $org) {
	    $cn		= $common_name;
	    $query	= "SELECT * FROM cert_cache WHERE valid_untill > current_timestamp() AND cert_owner LIKE :cn AND organization = :org";
	    $params	= array('text', 'text');
	    $data	= array('cn' => $cn, 'org' => $org);
	    try {
		    $res = MDB2Wrapper::execute($query, $params, $data);
	    } catch (DBStatementException $dbse) {
		    Logger::log_event(LOG_NOTICE, "Could not get list of certificates, error with query-syntax in " . __FILE__ . ":" . __LINE__);
		    return null;
	    } catch (DBQueryExceptin $dbqe) {
		    Logger::log_event(LOG_NOTICE, "Could not get list of certificates, error with parameters in query at " . __FILE__ . ":" . __LINE__);
		    return null;
	    }
	    return $res;
    } /* end getCertListForPersons */

	/**
	 * @see CA::getCertListForEPPN
	 */
	public function getCertListForEPPN($eppn, $org)
	{
		return getCertListForPersons($eppn, $org);
	}

	/* we do not authN users for standalone (debug only) and server handles everything.
	 */
	function verifyCredentials($username, $password)
	{
		return true;
	}

    /**
     * Return true if processing of the certificate is finished and false
     * otherwise.
     *
     * @param $key The auth_key or order number of the certificate for which is
     * polled
     */
    public function pollCertStatus($key)
    {
	    /* FIXME, should be immediate result anyway */
	    try  {
		    $res = MDB2Wrapper::execute("SELECT * FROM cert_cache WHERE auth_key=? and cert_owner = ?",
						array('text', 'text'),
						array($key, $this->person->getEPPN()));
		    if (count($res) == 1) {
			    return true;
		    }
	    } catch (Exception $e) {
		    Framework::error_output(htmlentities($e->getMessage()));
		    return false;
	    }
	    return false;
    } /* end pollCertStatus */

    /*
     * Get the certificate bound to key $key from the database
     *
     * @throws ConfusaGenException
     */
    public function getCert($key)
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
            $cn = stripslashes($this->person->getX509ValidCN());
            $msg .= "Queried for key $key and CN $cn\n";
            throw new DBQueryException($msg);
        }
    } /* end getCert */

	/**
	 * Get the owner DN and the organization name for the certificate associated
	 * with key $key.
	 *
	 * @param $key mixed The key for which the certificate is to be retrieved
	 * @return array containing owner DN and organization name
	 *
	 * @throws DBQueryException If something went wrong with the query
	 * @throws DBStatementException If the SQL configuration is wrong
	 */
    public function getCertInformation($key)
    {
		$query = "SELECT cert_owner, organization FROM cert_cache WHERE auth_key=?";

		$res = MDB2Wrapper::execute($query,
									array('text'),
									array($key));

		if (count($res) == 1) {
			return $res[0];
		}
	} /* end getCertInformation */

    public function getCertDeploymentScript($key, $browser)
    {
	/* TODO: I am feeling all stubby */
	return "<script type=\"text/javascript\">var g_ccc=\"\"</script>";
    } /* end getCertDeploymentScript */

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
		    $msg .=	"<BR />Server said: " . htmlentities($dbse->getMessage());
		    Framework::error_output($msg);

		    /* Even though we fail, the certificate was
		     * successfully revoked, thus the operation was
		     * semi-successful. But, true should indicate that
		     * *everything* went well */
		    return false;
	    } catch (DBQueryException $dbqe) {
		    $msg  = __FILE__ . ":" . __LINE__ . " Query-error. Constraint violoation in query?";
		    Logger::log_event(LOG_NOTICE, $msg);
		    $msg .= "<BR />Server said: " . htmlentities($dbqe->getMessage());
		    Framework::error_output($msg);
		    return false;
	    }
	    return true;
    } /* end deleteCertFromDB */

    /*
     * Revoke the certificate identified by key
     * Key is an auth_var
     */
    public function revokeCert($key, $reason)
    {
	    /* TODO: method stub
	     *
	     * At a first glance there seems to be no revoke function in php-openssl.
	     * shell_exec('openssl ca -revoke...') would be possible but... eew...
	     * Generously leaving this decision to Henrik ;-)
	     *
	     */
	    $cmd = "./../cert_handle/revoke_cert.sh $key " . ConfusaConstants::$OPENSSL_CRL_FILE;
	    $res = exec($cmd, $output, $return);
	    foreach ($output as $line) {
		    $msg .= $line . "<BR />\n";
	    }
	    if ((int)$return != 0) {
		    Logger::log_event(LOG_NOTICE, "Problems revoking certificate for " .
				      $this->getFullDN() . "($key)");
		    throw new CGE_KeyRevokeException($msg);
	    }
	    Logger::log_event(LOG_NOTICE, "Revoked certificate $key for user " .
			      $this->getFullDN());

	    if (!$this->deleteCertFromDB($key)) {
		    Logger::log_event(LOG_NOTICE, "Could not delete certificate ($key) from database, revocation only partially completed.");
	    }

	    return true;
    } /* end revokeCert() */


    /**
     * verifyCSR()
     *
     * This function will test the CSR against several fields.
     * It will test the subject against the person-attributes (which in turn are
     * gathered from simplesamlphp-attributes (Feide, surfnet etc).
     *
     * @param String The CSR in base64 PEM format
     * @return Boolean True if valid CSR
     */
  private function verifyCSR($csr)
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
	       else if (!match_dn($subject, $this->getFullDN())) {
		       $msg = "";
		       $msg .= "Error in subject! <BR/>\n";
		       $msg .= "The fields in your CSR was not set properly.<BR>\n";
		       $msg .= "To try again, please download a new version of the script, ";
		       $msg .= "generate a new key and upload again.<BR>\n";
		       Framework::error_output($msg);
		       return false;
               }
	       return true;
    } /* end verifyCSR */


} /* end class CA_Standalone */
?>
