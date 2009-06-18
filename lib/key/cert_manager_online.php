<?php
declare(encoding = 'utf-8');
require_once('person.php');
require_once('cert_manager.php');
require_once('key_sign.php');
require_once('db_query.php');
require_once('mdb2_wrapper.php');
require_once('remote_api.php');

/**
 * CertManager_Online. Remote extension for CertManager.
 *
 * Class for connecting to a remote CA (e.g. Comodo), uploading CSRs,
 * ordering certificates, listing certificates etc.
 *
 * PHP version 5
 * @author: Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class CertManager_Online extends CertManager
{
    /* order number and collection code for communication with the remote API */
    private $order_number;
    private $collection_code;

    /* constants for the test-mode. These will go into the certificate subject */
    private $TEST_CN_PREFIX;
    private $TEST_DC = "TEST CERTIFICATE";
    private $TEST_O_PREFIX;


    function __construct($pers)
    {
        if (Config::get_config('capi_test')) {
            $this->TEST_CN_PREFIX = "TEST PERSON ";
            $this->TEST_O_PREFIX = "TEST UNIVERSITY ";
        } else {
            $this->TEST_CN_PREFIX = "";
            $this->TEST_O_PREFIX = "";
        }

        parent::__construct($pers);
    }

    /**
     * Sign the CSR identified by auth_key using the Online-CA's remote API
     * @throws ConfusaGenException
    */
    public function sign_key($auth_key, $csr)
    {
        $this->_capi_upload_CSR($auth_key, $csr);
        $this->_capi_authorize_CSR();
         /* read public key and create sum */
	    $pubkey_checksum=pubkey_hash($csr, true);
        MDB2Wrapper::update("INSERT INTO pubkeys (pubkey_hash, uploaded_nr) VALUES(?, 0)",
                            array('text'),
                            array($pubkey_checksum));
    }

    /**
     * Return an array with all the certificates obtained by the person managed by this
     * CertManager.
     * TODO: Retrieve that list once per session and cache it
     * @throws RemoteAPIException
     */
    public function get_cert_list()
    {
        $list_endpoint = Config::get_config('capi_listing_endpoint');
        $postfields_list["loginName"] = Config::get_config('capi_login_name');
        $postfields_list["loginPassword"] = Config::get_config('capi_login_pw');

        $postfields_list["commonName"] = $this->TEST_CN_PREFIX . $this->person->get_valid_cn();
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
        parse_str($data, $params);

        if (!isset($params['errorCode'])) {
            throw new RemoteAPIException("Unexpected response from " .
                "remote endpoint! Maybe Confusa is improperly configured?"
            );
        }

        /* this is the "correct case" */
        if ($params['errorCode'] == "0") {
            $res = array();
            for ($i = 1; $i <= $params['noOfResults']; $i = $i+1) {
                /* transfer the orders from the string representation in the response
                 * to the array representation we use internally */
                $res[$i-1]['order_number'] = $params[$i . '_orderNumber'];
                $res[$i-1]['cert_owner'] = $this->person->get_valid_cn();
            }
        } else {
            throw new RemoteAPIException("Errors occured when listing " .
                "user certificates: " . $params['errorMessage']
            );
        }

        return $res;
    }

    /**
     * Retrieve a certificate from a remote endpoint (e.g. Comodo).
     * TODO cache the certs locally for 30 minutes, in order
     * not to have to make remote calls all the time.
     *
     * @params key either an order-number that can be used to retrieve a certificate
     * directly or an auth-key with which we can retrieve the order-number
     * @throws ConfusaGenException
     */
    public function get_cert($key)
    {
        $key = $this->_transform_to_order_number($key);

        $return_res = NULL;
        Logger::log_event(LOG_NOTICE, "Trying to retrieve certificate with order number " .
                                      $key .
                                      " from the Comodo collect API. Sending to user with ip " .
                                      $_SERVER['REMOTE_ADDR']);

        $login_name = Config::get_config('capi_login_name');
        $login_pw = Config::get_config('capi_login_pw');
        $collect_endpoint = Config::get_config('capi_collect_endpoint') .
                            "?loginName=" . $login_name .
                            "&loginPassword=" . $login_pw .
                            "&orderNumber=" . $key . "&queryType=2";
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

        $STATUS_PEND="0";
        $STATUS_OK="2";
        /* Parse the status response from the remote API
         */

        $status=substr($data,0,1);
        switch($status) {
          case $STATUS_OK:
              $return_res = substr($data,2);
              break;
          case $STATUS_PEND:
              echo "The certificate is being processed and is not yet available<br />\n";
              break;
          default:
              /* extract the error status code which is longer than one character */
              $pos = stripos($data, "\n");

              /* potential error: no newline in response */
              if ($pos === FALSE) {
                $msg = "Received an unexpected response from the remote API!<br />\n" .
                       "Maybe Confusa is improperly configured?<br />\n";
                throw new RemoteAPIException($msg);
              }

              $status = substr($data,0,$pos);
              /* potential error: response does not contain status code */
              if(is_numeric($status)) {
                throw new RemoteAPIException("Received error message $data <br />\n");
              } else {
                $msg = "Received an unexpected response from the remote API!<br />\n" .
                       "Maybe Confusa is improperly configured?<br />\n";
                throw new RemoteAPIException($msg);
              }
        }

        return $return_res;
    }

    /*
     * Revoke certificate identified by key using Comodo's Online AutoRevoke
     * API
     *
     * @param key The key identifying the certificate
     * @param reason A reason for revocation, as specified in RFC 5280
     *
     * @throws RemoteAPIException if revocation fails
     */
    public function revoke_cert($key, $reason)
    {
        $key = $this->_transform_to_order_number($key);

        $return_res = NULL;
        Logger::log_event(LOG_NOTICE, "Trying to revoke certificate with order number " .
                                      $key .
                                      " using Comodo's auto-revoke-API. Sending to user with ip " .
                                      $_SERVER['REMOTE_ADDR']);

        $revoke_endpoint = Config::get_config('capi_revoke_endpoint');
        $login_name = Config::get_config('capi_login_name');
        $login_pw = Config::get_config('capi_login_pw');
        $postfields_revoke = array();
        $postfields_revoke["loginName"] = $login_name;
        $postfields_revoke["loginPassword"] = $login_pw;
        $postfields_revoke["revocationReason"] = $reason;
        $postfields_revoke["orderNumber"] = $key;
        $postfields_revoke["includeInCRL"] = 'Y';

        /* will not revoke test certificates? */
        if (Config::get_config('capi_test')) {
            $postfields_revoke["test"] = 'Y';
        }

        $ch = curl_init($revoke_endpoint);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields_revoke);
        $data = curl_exec($ch);
        curl_close($ch);

        /* try to catch all kinds of errors that can happen when connecting */
        if ($data === FALSE) {
            throw new RemoteAPIException("Could not connect to revoke-API! " .
                                        "Check Confusa configuration!<br />\n"
            );
        } else {
            $pos = stripos($data, "\n");

            if ($pos == FALSE) {
                throw new RemoteAPIException("Response from RevokeAPI unexpected! " .
                                             "Check Confusa configuration<br />\n."
                );
            } else {
                $STATUS_OK = "0";

                $status = substr($data, 0, $pos);

                switch($status) {
                    case $STATUS_OK:  echo "Certificate successfully revoked!<br />\n";
                                      break;
                    default: throw new RemoteAPIException("Received error message " .
                                                          $data .
                                                          "<br />\n"
                            );
                             break;
                }
            }
        }
    }

    /**
     * Upload the CSR to the remote API and authorize the signing request
     * Store the order number and the collection code in the DB,
     * for bookkeeping purposes.
     * It is recommended to have this information backed up and
     * stored permanently to keep track of Comodo-issued certificates.
     *
     * @throws ConfusaGenException
    */
    private function _capi_upload_CSR($auth_key, $csr)
    {
        $sign_endpoint = Config::get_config('capi_apply_endpoint');
        $ap = Config::get_config('capi_ap_name');
        $ca_cert_id = Config::get_config('capi_escience_id');
        /* Leave this hardcoded since the TCS profile states that MICS profiles
         * are to be valid for exactly 13 months */
        $days = '14';

        $postfields_sign_req=array();

        /* clutter TEST all over it if the certs are part of a testing process
         */
        if (Config::get_config('capi_test')) {
          $postfields_sign_req["subject_domainComponent_7"] = $this->TEST_DC;
        }

        /* set all the required post parameters for upload */
        $postfields_sign_req["ap"] = $ap;
        $postfields_sign_req["csr"] = $csr;
        $postfields_sign_req["days"] = $days;
        $postfields_sign_req["successURL"] = "none";
        $postfields_sign_req["errorURL"] = "none";
        $postfields_sign_req["caCertificateId"] = $ca_cert_id;
        /* manually compose the subject. Necessary, because we want to have
         * Terena domainComponents */
        $postfields_sign_req["subject_commonName_1"] = $this->TEST_CN_PREFIX .
            $this->person->get_valid_cn();
        $postfields_sign_req["subject_organizationName_2"] = $this->TEST_O_PREFIX .
            $this->person->get_orgname();
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
         * If something has failed, an errorCode parameter will be set in
         * the return message
         */
        if (isset($params['errorCode'])) {
            $msg = "Received an error when uploading the CSR to the remote CA: " .
                $params['errorMessage'] . "<br />\n";
            throw new KeySignException($msg);
        }

        else {
            if (!isset($params['orderNumber']) || !isset($params['collectionCode'])) {
                $msg = "Response looks malformed. Maybe there is a configuration " .
                       "error in Confusa's online-CA configuration!";
                throw new RemoteAPIException($msg);
            }

            $this->order_number = $params['orderNumber'];
            $this->collection_code = $params['collectionCode'];

            Logger::log_event(LOG_INFO, "Uploaded CSR to remote CA. Received " .
                                        "order number " .
                                        $this->order_number .
                                        " and collection code " .
                                        $this->collection_code .
                                        " for user " .
                                        $this->person->get_valid_cn() .
                                        " Person contacted us from " .
                                        $_SERVER['REMOTE_ADDR']);

          $sql_command= "INSERT INTO order_store(auth_key, common_name, " .
                        "order_number, collection_code, order_date, authorized)" .
                        "VALUES(?, ?, ?, ?, now(),false)";

          MDB2Wrapper::update($sql_command,
                            array('text', 'text', 'text', 'text'),
                            array($auth_key, $this->person->get_valid_cn(),
                            $this->order_number, $this->collection_code));
        } /* end _capi_upload_csr */
    }

    /**
     * Check if key $auth_key is an order-number or an authvar.
     * If it is an authvar, retrieve the associated order-number from the DB.
     *
     * @throws ConfusaGenException
     */
    private function _transform_to_order_number($auth_key)
    {
      /* first check if it is an order number already */
      if (is_numeric($auth_key)) {
        /* if it is numeric, chances are quite high that we have an order number.
         * at the same time this is the only formal restriction we have for an order number.
        */
        return $auth_key;
      } else if(strlen($auth_key) == Config::get_config('auth_length')) {
          $res = MDB2Wrapper::execute("SELECT order_number FROM order_store WHERE auth_key=? AND common_name=?",
                                  array('text', 'text'),
                                  array($auth_key, $this->person->get_valid_cn()));

          if (count($res) < 1) {
            throw new DBQueryException("Could not find order number for $auth_key " .
                                           "and " . $this->person->get_valid_cn() .
                                           " in order_store"
            );
          }

          return $res[0]['order_number'];
      } else {
            throw new ConfusaGenException("Auth_var format not recognized!");
      }
    }

    /**
     *After the CSR has been uploaded to the Comodo certificate apply API, it
     * must be authorized by the user.
     * Call the authorize endpoint in the API and update the respective DB entry.
     */
    private function _capi_authorize_CSR()
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

        /* the only formal restriction we have is if the API returns 0 for the query */
        if (substr($data,0,1) == "0") {
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
        } else {
            $msg = "Received an error when authorizing the CSR with orderNumber " .
                   $this->order_number . " <br />\n";
            throw new RemoteAPIException($msg);
        }

    } /* end _capi_authorize_csr */


} /* end class OnlineCAManager */
?>
