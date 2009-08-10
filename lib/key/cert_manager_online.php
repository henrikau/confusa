<?php
declare(encoding = 'utf-8');
require_once 'person.php';
require_once 'cert_manager.php';
require_once 'key_sign.php';
require_once 'db_query.php';
require_once 'mdb2_wrapper.php';
require_once 'remote_api.php';

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
    /* order number for communication with the remote API */
    private $order_number;

    /* constants for the test-mode. These will go into the certificate subject */
    private $TEST_CN_PREFIX;
    private $TEST_DC = "TEST CERTIFICATE";
    private $TEST_O_PREFIX;

    /* login-name and password for the remote-signing CA */
    private $login_name;
    private $login_pw;


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

    /*
     * Get username and password for the remote-CA account of the
     * (institution of) the managed person.
     */
    private function _get_account_information() {
        $login_cred_query = "SELECT a.account_login_name, a.account_password, a.account_ivector " .
              "FROM nren_account_map_view a, nren_subscriber_view s " .
              "WHERE s.subscriber = ? AND s.nren = a.nren";

        $org = $this->person->getSubscriberOrgName();
        Logger::log_event(LOG_INFO, "Getting the remote-CA login " .
                          "credentials for organization " .
                          $this->person->getSubscriberOrgName()
                );
        $res = MDB2Wrapper::execute($login_cred_query, array('text'),
                                    array($org)
        );

        if (count($res) != 1) {
		Logger::log_event(LOG_NOTICE, "Could not extract the suitable remote CA credentials for organization $org!");
		throw new DBQueryException("Could not extract the suitable " .
					   "remote CA credentials for organization $org!"
			);
        }

        $this->login_name = $res[0]['account_login_name'];
        $encrypted_pw = base64_decode($res[0]['account_password']);
        $ivector = base64_decode($res[0]['account_ivector']);
        $encryption_key = Config::get_config('capi_enc_pw');
        $this->login_pw = trim(base64_decode(mcrypt_decrypt(
                                MCRYPT_RIJNDAEL_256, $encryption_key,
                                $encrypted_pw, MCRYPT_MODE_CFB,
                                $ivector)));
    }

    /**
     * Sign the CSR identified by auth_key using the Online-CA's remote API
     * @throws ConfusaGenException
    */
    public function sign_key($auth_key, $csr)
    {
        if (!isset($this->login_name) || !isset($this->login_pw)) {
            $this->_get_account_information();
        }

        $this->_capi_upload_CSR($auth_key, $csr);
        $this->_capi_authorize_CSR();

	/* FIXME: conflict, not sure how to resolve, do we need both? */
        Logger::log_event(LOG_INFO, "Signed CSR for user with auth_key $auth_key");
	/* FIXME: <END> */
    }

    /**
     * Return an array with all the certificates obtained by the person managed by this
     * CertManager.
     * TODO: Retrieve that list once per session and cache it
     * @throws RemoteAPIException
     */
    public function get_cert_list()
    {
        $common_name = $this->person->getX509ValidCN();
        $params = $this->_capi_get_cert_list($common_name);
        $res=array();

        /* transfer the orders from the string representation in the response
         * to the array representation we use internally */
        for ($i = 1; $i <= $params['noOfResults']; $i = $i+1) {

            /* for simplicity, format the time just as an SQL server would return it */
            $valid_untill = $params[$i . '_1_notAfter'];

            /* don't fetch expired certificates, but include pending certificates */
            if (!empty($valid_untill) && ($valid_untill < time())) {
                continue;
            } else if (!empty($valid_untill)) {
                $valid_untill = date('Y-m-d H:i:s', $valid_untill);
                $res[$i-1]['valid_untill'] = $valid_untill;
            }

            $res[$i-1]['order_number'] = $params[$i . '_orderNumber'];
            $res[$i-1]['cert_owner'] = $this->person->getX509ValidCN();
        }

        return $res;
    }

    /*
     * Search for the certificates of a person with a given common_name.
     * Common_name may include wildcard characters.
     *
     * Restrict the result set to organization $org.
     *
     * @param $common_name The common_name to search for
     * @param $org The organization to restrict the search to
     */
    public function get_cert_list_for_persons($common_name, $org)
    {
        $params = $this->_capi_get_cert_list($common_name);
        $res = array();
        for ($i = 1; $i <= $params['noOfResults']; $i++) {
            /* Note that this field will not get exported if the order is not yet authorized */
            $valid_untill = $params[$i . '_1_notAfter'];

            /* don't consider expired or pending certificates */
            if ($valid_untill < time()) {
                continue;
            }

            $subject = $params[$i . '_1_subjectDN'];
            $dn_components = explode(',', $subject);

            if ($org != NULL) {
                $organization = "O=" . $this->TEST_O_PREFIX . $org;

                /* don't return order number and the owner subject
                 * if the organization is not present in the DN
                 */
                if (array_search($organization, $dn_components) === FALSE) {
                    continue;
                }
            }

            if (!empty($valid_untill)) {
                $valid_untill = date('Y-m-d H:i:s', $valid_untill);
                $res[$i-1]['valid_untill'] = $valid_untill;
            }

            $res[$i-1]['auth_key'] = $params[$i . '_orderNumber'];
            $res[$i-1]['cert_owner'] = $subject;
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

        if (!isset($this->login_name) || !isset($this->login_pw)) {
          $this->_get_account_information();
        }

        Logger::log_event(LOG_NOTICE, "Trying to retrieve certificate with order number " .
                                      $key .
                                      " from the Comodo collect API. Sending to user with ip " .
                                      $_SERVER['REMOTE_ADDR']);

        $collect_endpoint = Config::get_config('capi_collect_endpoint') .
                            "?loginName=" . $this->login_name .
                            "&loginPassword=" . $this->login_pw .
                            "&orderNumber=" . $key .
                            "&queryType=2" .
                            "&responseMimeType=application/x-x509-user-cert";

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
            Framework::message_output("The certificate is being processed and is not yet available");
		    return null;
        default:
            /* extract the error status code which is longer than one character */
            $pos = stripos($data, "\n");

            /* potential error: no newline in response */
            if ($pos === FALSE) {
                $msg = "Received an unexpected response from the remote API!\n" .
                       "Maybe Confusa is not properly configured?<br />\n";
                throw new RemoteAPIException($msg);
            }

            $status = substr($data,0,$pos);
            /* potential error: response does not contain status code */
            if(is_numeric($status)) {
              throw new RemoteAPIException("Received error message $data\n");
            } else {
              $msg = "Received an unexpected response from the remote API!n" .
                     "Maybe Confusa is not properly configured?\n";
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

        if (!isset($this->login_name) || !isset($this->login_pw)) {
          $this->_get_account_information();
        }

        Logger::log_event(LOG_NOTICE, "Trying to revoke certificate with order number " .
                                      $key .
                                      " using Comodo's auto-revoke-API. Sending to user with ip " .
                                      $_SERVER['REMOTE_ADDR']);

        $revoke_endpoint = Config::get_config('capi_revoke_endpoint');
        $postfields_revoke = array();
        $postfields_revoke["loginName"] = $this->login_name;
        $postfields_revoke["loginPassword"] = $this->login_pw;
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
                                        "Check Confusa configuration!\n"
            );
        } else {
            $pos = stripos($data, "\n");

            if ($pos == FALSE) {
                throw new RemoteAPIException("Response from RevokeAPI unexpected! " .
                                             "Check Confusa configuration\n."
                );
            } else {
                $STATUS_OK = "0";

                $status = substr($data, 0, $pos);

                switch($status) {
                case $STATUS_OK:
                    Framework::message_output("Certificate with " .
                                "order number $key successfully revoked!<br />\n");

                              Logger::log_event(LOG_NOTICE, "Revoked certificate with " .
                                                "order number $key using Comodo's AutoRevoke " .
                                                "API. User contacted us from " .
                                                $_SERVER['REMOTE_ADDR']);
                              break;
                default:
                    throw new RemoteAPIException("Received error message $data");
                    Logger::log_event(LOG_ERROR, "Revocation of certificate with " .
                                     "order_number $key failed! User contacted us from " .
                                     $_SERVER['REMOTE_ADDR']);
                    break;
                }
            }
        }
    }

    /*
     * Query the remote API for the list of certificates belonging to
     * common_name $common_name. Filter out all the expired certificates.
     *
     * @param $common_name The common-name for which the list is retrieved
     */
    private function _capi_get_cert_list($common_name)
    {
        if (!isset($this->login_name) || !isset($this->login_pw)) {
          $this->_get_account_information();
        }

        Logger::log_event(LOG_DEBUG, "Trying to get the list with the certificates " .
                                    "for person $common_name");

        $list_endpoint = Config::get_config('capi_listing_endpoint');
        $postfields_list["loginName"]		= $this->login_name;
        $postfields_list["loginPassword"]	= $this->login_pw;
        $postfields_list["commonName"]		= $this->TEST_CN_PREFIX . $common_name;
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
		$msg  = "Unexpected response from remote endpoint. ";
		$msg .= "Perhaps some configuration-switch is not properly set.";
		$msg .= "Server gave no error-code.";
		throw new RemoteAPIException($msg);
        }

        if ($params['errorCode'] == "0") {
            return $params;
        } else {
            throw new RemoteAPIException("Received error when trying to list " .
                                         "certificates from the remote-API: " .
                                         $params['errorMessage']
            );
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

        $postfields_sign_req=array();

        /* clutter TEST all over it and reduce validity period
         * if the certs are part of a testing process
         */
        if (Config::get_config('capi_test')) {
          $postfields_sign_req["subject_domainComponent_7"] = $this->TEST_DC;
          $days = '14';
        } else {
          $days = '395';
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
            $this->person->getX509ValidCN();
        $postfields_sign_req["subject_organizationName_2"] = $this->TEST_O_PREFIX .
            $this->person->getSubscriberOrgName();
        $postfields_sign_req["subject_countryName_3"] = $this->person->getCountry();
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
                $params['errorMessage'] . "\n";
            throw new RemoteAPIException($msg);
        }

        else {

            if (!isset($params['orderNumber']) || !isset($params['collectionCode'])) {
                $msg = "Response looks malformed. Maybe there is a configuration " .
                       "error in Confusa's online-CA configuration!";
                throw new RemoteAPIException($msg);
            }

            $this->order_number = $params['orderNumber'];

            Logger::log_event(LOG_INFO, "Uploaded CSR to remote CA. Received " .
                                        "order number " .
                                        $this->order_number .
                                        " for user " .
                                        $this->person->getX509ValidCN() .
                                        " Person contacted us from " .
                                        $_SERVER['REMOTE_ADDR']);

          $sql_command= "INSERT INTO order_store(auth_key, owner, " .
                        "order_number, order_date, authorized)" .
                        "VALUES(?, ?, ?, now(),'unauthorized')";

          MDB2Wrapper::update($sql_command,
                            array('text', 'text', 'text'),
                            array($auth_key, $this->person->getX509ValidCN(),
                            $this->order_number));
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
          // TODO: Replace getEPPN with get_eppn or whatever...
          $res = MDB2Wrapper::execute("SELECT order_number FROM order_store WHERE auth_key=? AND owner=?",
                                  array('text', 'text'),
                                  array($auth_key, $this->person->getEPPN()));

          if (count($res) < 1) {
            throw new DBQueryException("Could not find order number for $auth_key " .
                                           "and " . $this->person->getEPPN() .
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

        $ch = curl_init($authorize_endpoint);
        $postfields_auth = array();
        $postfields_auth["loginName"] = $this->login_name;
        $postfields_auth["loginPassword"] = $this->login_pw;
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
          MDB2Wrapper::update("UPDATE order_store SET authorized='authorized' WHERE order_number=?",
                              array('text', 'text'),
                              array($this->order_number, $this->collection_code));
          Logger::log_event(LOG_NOTICE, "Authorized remote certificate for person ".
                                        $this->person->getX509ValidCN().
                                        " with order number " .
                                        $this->order_number .
                                        " Person contacted us from ".
                                        $_SERVER['REMOTE_ADDR']);
        } else {
            $msg = "Received an error when authorizing the CSR with orderNumber " .
                   $this->order_number . $data . "\n";
            throw new RemoteAPIException($msg);
        }

    } /* end _capi_authorize_csr */

} /* end class OnlineCAManager */
?>
