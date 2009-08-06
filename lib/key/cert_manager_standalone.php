<?php

require_once 'person.php';
require_once 'cert_manager.php';
require_once 'key_sign.php';
require_once 'mdb2_wrapper.php';
require_once 'db_query.php';
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
        if ($this->verify_csr($csr)) {
            $cert_path = 'file://'.dirname(WEB_DIR) . Config::get_config('ca_cert_path') . Config::get_config('ca_cert_name');
            $ca_priv_path = 'file://'.dirname(WEB_DIR) . Config::get_config('ca_key_path') . Config::get_config('ca_key_name');

            $cert = null;
            $sign_days = 11;
            $tmp_cert = openssl_csr_sign($csr, $cert_path, $ca_priv_path, $sign_days , array('digest_alg' => 'sha1'));
            openssl_x509_export($tmp_cert, $cert, true);

            $timeout = Config::get_config('cert_default_timeout');

	    try {
		    $insert  = "INSERT INTO cert_cache (cert, auth_key, cert_owner, organization, valid_untill) ";
		    $insert .= "VALUES(?, ?, ?, ?, timestampadd($timeout[1], $timeout[0],current_timestamp()))";
		    MDB2Wrapper::update($insert,
					array('text', 'text', 'text', 'text'),
					array($cert,
					      $auth_key,
					      $this->person->getX509ValidCN(),
					      $this->person->get_orgname()));

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
        $res = MDB2Wrapper::execute("SELECT auth_key, cert_owner, valid_untill FROM cert_cache WHERE ".
				    "cert_owner=? AND valid_untill > current_timestamp()",
				    array('text'),
				    array($this->person->getX509ValidCN()));
        $num_received = count($res);
        if ($num_received > 0 && !(isset($res[0]['auth_key']))) {
            $msg = "Received an unexpected response from the database for user " .
                     $this->person->get_common_name();
            throw new DBQueryException($msg);
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
	    Framework::error_output("Revocation for standalone configuration is to be implemented!");
	    $path		= Config::get_config('install_path') . Config::get_config('ca_cert_base_path');
	    $ca_config_file	= $path . Config::get_config('ca_conf_name');
	    $ca_key		= $path . Config::get_config('ca_key_path')  . Config::get_config('ca_key_name');
	    $ca_cert		= $path . Config::get_config('ca_cert_path') . Config::get_config('ca_cert_name');
	    $crl_file		= $path . Config::get_config('ca_crl_name');
	    /*
	     * Get cert from DB and store in temp-file
	     */
	    try {
		    echo "getting certificate for user " . $this->person->getX509ValidCN() . "<BR />\n";
		    $query = "SELECT cert FROM cert_cache WHERE auth_key=? AND cert_owner=?";
		    $res = MDB2Wrapper::execute($query,
						array('text', 'text'),
						array($key, $this->person->getX509ValidCN()));
		    $cert = $res[0]['cert'];
		    if (!isset($cert) || $cert === "") {
			    Framework::error_output("Could not retrieve certificate from database!");
			    return false;
		    }
		    $cert_file_name	= tempnam("/tmp/", "REV_CERT");
		    $cert_file		= fopen($cert_file_name, "w");
		    $numbytes		= fwrite($cert_file, $cert);
		    $revoke_cmd		= "cd $path ; openssl ca -config $ca_config_file -revoke $cert_file_name -keyfile $ca_key -cert $ca_cert";
		    $crl_cmd		= "cd $path ; openssl ca -config $ca_config_file -gencrl -keyfile $ca_key -cert $ca_cert -out $crl_file";

		    exec($revoke_cmd, $revoke_output,$revoke_res);
		    if (!$revoke_res) {
			    Framework::error_output("Could not revoke certificate! $revoke_output");
		    }
		    else {
			    exec($crl_cmd, $crl_output, $crl_res);
			    if (!$crl_res) {
				    Framework::error_output("Could not add revoked cert to CRL! $crl_output");
			    }
		    }
	    } catch (Exception $e) {
		    Framework::error_output($e->getMessage());
		    return false;
	    }

	    try {
		    fclose($cert_file);
		    unlink($cert_file_name);
	    } catch (Exception $e) {
		    ;
	    }

	    return true;
    }

  /* verify_csr()
   *
   * This function will test the CSR against several fields.
   * It will test the subject against the person-attributes (which in turn are
   * gathered from simplesamlphp-attributes (Feide, surfnet etc).
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
	       else if (!$this->match_dn($subject)) {
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

  /* match_dn
   *
   * This will match the associative array $subject with the constructed DN from person->getX509SubjectDN()
   *
   * The best would be to use something like what openssl supports:
   *	openssl x509 -in usercert.pem -subject -noout
   * which returns the subject string as we construct it below. However,
   * php5_openssl has no obvious way of doing that.
   *
   * Eventually, we have to add severeal extra fields to handle all different
   * cases, but for now, this will do.
   */
  private function match_dn($subject)
  {
	  /* Compose the DN in the 'correct' order, only use the fields set in
	   * the subject */
	  $composed_dn = "";
	  if (isset($subject['C']))
		  $composed_dn .= "/C=".$subject['C'];
	  if (isset($subject['O']))
		  $composed_dn .= "/O=".$subject['O'];
	  if (isset($subject['OU']))
		  $composed_dn .= "/OU=".$subject['OU'];
	  if (isset($subject['C']))
		  $composed_dn .= "/CN=".$subject['CN'];
	  $res = $this->person->getX509SubjectDN() === $composed_dn;
	  if (Config::get_config('debug') && !$res) {
		  Framework::error_output("Supplied (".$composed_dn.") and required subject (".$this->person->getX509SubjectDN() .") differs!");
	  }
	  return $res;
  }

} /* end class CertManager_Standalone */
?>
