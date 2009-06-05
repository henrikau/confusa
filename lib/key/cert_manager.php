<?php
  /* Certmanager
   *
   * Class for signing certificates, verifying CSRs and storing it in the database
   *
   * Author: Henrik Austad <henrik.austad@uninett.no>
   */
require_once('mdb2_wrapper.php');
require_once('logger.php');
require_once('csr_lib.php');
require_once('config.php');

class CertManager
{
  private $person;
  private $user_csr;
  private $pubkey_checksum;
  private $valid_csr;
  private $user_cert;
  /* if used with the remote API, an order-number and collection code will be
   * stored instead of a user-cert
   */
  private $order_number;
  private $collection_code;

  /* 
   * Should register all values so that when a sign_key request is issued,
   * all values are in place.
   *
   * @param csr : the Certificate Signing Request
   * @param pers: object describing the person and his/hers attributes.
   */
  function __construct($csr, $pers)
    {
	    if (!isset($pers) || !($pers instanceof Person)) {
		    echo __FILE__ . " Cannot function without a person!<BR>\n";
		    exit(0);
	    }
	    $this->person = $pers;
	    $this->user_csr = $csr;
      
	    /* read public key and create sum */
	    $this->pubkey_checksum=pubkey_hash($this->user_csr, true);

    } /* end __construct */

  /* this function is quite critical, as it must remove residual information
   * about the certificate and it's owner from the server
   */
  function __destruct()
    {
      unset($this->person);
      unset($this->valid_csr);
      unset($this->user_cert);
      unset($this->order_number);
      unset($this->collection_code);
    } /* end destructor */
    

  /* sign_key()
   *
   * This is the signing routine of the system. In this release, it will use PHP
   * for signing, using a local CA-key.
   *
   * In the future, it will sign the CSR and ship it to the CA, receive the
   * response and notify the user
   */
  function sign_key($auth_key)
    {
      $success = false;
     /* Standalone mode, use php and local certificate/key to
      * sign for user */
     if (Config::get_config('standalone')) {
       if ($this->verify_csr()) {
		      $cert_path = 'file://'.dirname(WEB_DIR) . Config::get_config('ca_cert_path') . Config::get_config('ca_cert_name');
		      $ca_priv_path = 'file://'.dirname(WEB_DIR) . Config::get_config('ca_key_path') . Config::get_config('ca_key_name');

          $sign_days = 11;
          $tmp_cert = openssl_csr_sign($this->user_csr, $cert_path, $ca_priv_path, $sign_days , array('digest_alg' => 'sha1'));
          openssl_x509_export($tmp_cert, $this->user_cert, true);
          /* echo __FILE__ .":".__LINE__ ." Certificate successfully signed. <BR>\n"; */


          MDB2Wrapper::update("INSERT INTO cert_cache (cert, auth_key, cert_owner, valid_untill) VALUES(?, ?, ?, addtime(current_timestamp(), ?))",
                              array('text', 'text', 'text', 'text'),
                              array($this->user_cert, $auth_key, $this->person->get_valid_cn(), Config::get_config('cert_default_timeout')));
          Logger::log_event(LOG_INFO, "Certificate successfully signed for ".
                                $this->person->get_valid_cn().
                                " Contacting us from ".
                                $_SERVER['REMOTE_ADDR']);

          $success = true;
        } else {
          Logger::log_event(LOG_INFO, "Will not sign invalid CSR for user ".
                       $this->person->get_valid_cn().
                       " from ip ".$_SERVER['REMOTE_ADDR']);
          $success = false;
        }
     }
      else {
           /* external CA, send the CSR as signed */
           Logger::log_event(LOG_DEBUG, "Signing key using remote CA");
           echo "uploading csr...<br />\n";
           if ($this->capi_upload_csr($auth_key)) {
             echo "authorizing csr...<br />\n";
             $success = $this->capi_authorize_csr();
           } else {
             $success = false;
           }
      }

      /* add to database (the hash of the pubkey) if signing the certificate was successful */
      if ($success) {
        MDB2Wrapper::update("INSERT INTO pubkeys (pubkey_hash, uploaded_nr) VALUES(?, 0)",
                                    array('text'),
                                    array($this->pubkey_checksum));
      }

      return $success;
    }  /*end sign_key() */ 

  /*
   * Upload the CSR to the remote API and authorize the signing request
   * Store the order number and the collection code in the DB, for bookkeeping purposes.
   * It is recommended to have this information backed up and stored permanently to keep track
   * of Comodo-issued certificates.
   */ 
  private function capi_upload_csr($auth_key) 
    {
    $sign_endpoint = Config::get_config('capi_apply_endpoint');
    $ap = Config::get_config('capi_ap_name');
    $ca_cert_id = Config::get_config('capi_escience_id');
    /* Leave this hardcoded since the TCS profile states that MICS profiles are to be valid for exactly 13 months */
    $days = 395;
    
    $cn_prefix = "";
    $o_prefix = "";
    
    $postfields_sign_req=array();
    
    /* clutter TEST all over it if the certs are part of a testing process :) */
    if (Config::get_config('capi_test')) {
      $cn_prefix = "TEST PERSON ";
      $o_prefix = "TEST UNIVERSITY ";
      $postfields_sign_req["subject_domainComponent_7"] = "TEST CERTIFICATE";
    }
   
    /* set all the required post parameters for upload */
    $postfields_sign_req["ap"] = $ap;
    $postfields_sign_req["csr"] = $this->user_csr;
    $postfields_sign_req["days"] = $days;
    $postfields_sign_req["successURL"] = "none";
    $postfields_sign_req["errorURL"] = "none";
    $postfields_sign_req["caCertificateId"] = $ca_cert_id;
    /* manually compose the subject. Necessary, because we want to have Terena domainComponents */
    $postfields_sign_req["subject_commonName_1"] = $cn_prefix . $this->person->get_valid_cn();
    $postfields_sign_req["subject_organizationName_2"] = $o_prefix . $this->person->get_orgname();
    $postfields_sign_req["subject_countryName_3"] = $this->person->get_country();
    $postfields_sign_req["subject_domainComponent_4"] = "tcs";
    $postfields_sign_req["subject_domainComponent_5"] = "terena";
    $postfields_sign_req["subject_domainComponent_6"] = "org";
    
    $ch = curl_init($sign_endpoint);
    curl_setopt($ch, CURLOPT_HEADER,0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$postfields_sign_req);
    $data=curl_exec($ch);
    curl_close($ch);
    
    $params=array();
    parse_str($data, $params);  
    /*
     * If something has failed, an errorCode parameter will be set in the return message
     */
    if (isset($params['errorCode'])) {  
      echo "Received an error when uploading the CSR to the remote CA: " . $params['errorMessage'] . "<br />\n";
      return false;
    } else {
      $this->order_number = $params['orderNumber'];
      $this->collection_code = $params['collectionCode'];
      
      Logger::log_event(LOG_INFO, "Uploaded CSR to remote CA. Received order number " .
                                  $this->order_number .
                                  " and collection code " .
                                  $this->collection_code .
                                  " for user " .
                                  $this->person->get_valid_cn() .
                                  " Person contacted us from " .
                                  $_SERVER['REMOTE_ADDR']);
      
      MDB2Wrapper::update("INSERT INTO order_store(auth_key, common_name, order_number, collection_code, order_date, authorized) VALUES(?, ?, ?, ?, now(),false)",
                        array('text', 'text', 'text', 'text'),
                        array($auth_key, $this->person->get_valid_cn(), $this->order_number, $this->collection_code));
      
      return true;
    }
  }
  
  /*
   * After the CSR has been uploaded to the Comodo certificate apply API, it must be authorized by the user.
   * Call the authorize endpoint and update the respective DB entry.
   */ 
  private function capi_authorize_csr() 
    {
    $authorize_endpoint = Config::get_config('capi_auth_endpoint');
    $login_name = Config::get_config('capi_login_name');
    $login_pw = Config::get_config('capi_login_pw');
    
    $ch = curl_init($authorize_endpoint);
    $postfields_auth = array();
    $postfields_auth["loginName"] = $login_name;
    $postfields_auth["loginPassword"] = $login_pw;
    $postfields_auth["orderNumber"] = $this->order_number;
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields_auth);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    
    if ((int)$data < 0) {
      echo "Received an error when authorizing the CSR with orderNumber $this-order_number: $data <br />\n";
      return false;
    } else {
      /* update the database-entry to reflect the autorization-state */
      MDB2Wrapper::update("UPDATE order_store SET authorized=true WHERE order_number=? AND collection_code=?",
                          array('text', 'text'),
                          array($this->order_number, $this->collection_code));
      Logger::log_event(LOG_NOTICE, "Authorized remote certificate for person ".
                                    $this->person->get_valid_cn().
                                    " with order number " .
                                    $this->order_number .
                                    " Person contacted us from ".
                                    $_SERVER['REMOTE_ADDR']);

      return true;
    }
  } /* end upload_csr */

  /* verify_csr()
   *
   * This function will test the CSR against several fields.
   * It will test the subject against the person-attributes (which in turn are
   * gathered from simplesamlphp-attributes (Feide, surfnet etc).
   */
  private function verify_csr()
  {
       /* by default, the CSR is valid, we then try to prove that it's invalid
        *
        * A better approach could be to distrust all CSRs and try to prove that
        * they are OK, however this leads to messy code (as the tests becomes
        * somewhat more involved) and I'm not convinced that it will be any safer.
        */
	  $this->valid_csr = true;
	  if (!isset($this->user_csr)) {
               echo __FILE__ . ":" . __LINE__ . " CSR not set in cert-manager<BR>\n";
		  $this->valid_csr = false;
	  }
	  else {
               $subject= openssl_csr_get_subject($this->user_csr);
               /* check fields of CSR to predefined values and user-specific values
                * Make sure that the emailAddress is not set, as this is
                * non-compatible with ARC.
                */
               if (isset($subject['emailAddress'])) {
                    echo "will not accept email in DN of certificate. Download latest version of script<br>\n";
                    $this->valid_csr = false;
               }
	       else if (!$this->match_dn($subject)) {
                    echo "Error in subject! <BR/>\n";
                    echo "The fields in your CSR was not set properly.<BR>\n";
                    echo "To try again, please download a new version of the script, ";
                    echo "generate a new key and upload again.<BR>\n";
                    $this->valid_csr = false;
               }
               else {
                    /* match hash of pubkey to db */
                    if (known_pubkey($this->user_csr)) {
                         echo "Cannot sign a public key that's previously signed. Please create a new key with corresponding CSR and try again<BR>\n";
                         $this->valid_csr = false;
                    }
               }
          }
          return $this->valid_csr;
    } /* end verify_csr */

  /* match_dn
   *
   * This will match the associative array $subject with the constructed DN from person->get_complete_dn()
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
	  return $this->person->get_complete_dn() === $composed_dn;
  }
} /* end class CertManager */

?>
