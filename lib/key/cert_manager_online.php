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
	public static $TEST_DC_PREFIX = "TEST CERTIFICATE";
	public static $TEST_O_PREFIX = "TEST UNIVERSITY ";
	public static $TEST_CN_PREFIX = "TEST PERSON ";

    private $cnPrefix;
    private $oPrefix;

    /* login-name and password for the remote-signing CA */
    private $login_name;
    private $login_pw;
    /* alliance-partner name for the remote-signing CA */
    private $ap_name;


    function __construct($pers)
    {
        if (Config::get_config('capi_test')) {
            $this->cnPrefix = CertManager_Online::$TEST_CN_PREFIX;
            $this->oPrefix = CertManager_Online::$TEST_O_PREFIX;
        } else {
            $this->cnPrefix = "";
            $this->oPrefix = "";
        }

        parent::__construct($pers);
        $this->getAccountInformation();
    }

    /**
     * Get username and password for the remote-CA account of the
     * (NREN of) the managed person.
     */
    private function getAccountInformation() {

		/* can only get the account if we have NREN information */
		if (is_null($this->person->getNREN())) {
			return;
		}

        $login_cred_query = "SELECT a.account_login_name, a.account_password, a.account_ivector, a.ap_name " .
              "FROM nren_account_map_view a WHERE a.nren=?";

        $nren = $this->person->getNREN();
        Logger::log_event(LOG_INFO, "Getting the remote-CA login " .
                          "credentials for NREN " .
                          $this->person->getNREN()
                );
        $res = MDB2Wrapper::execute($login_cred_query, array('text'),
                                    array($nren)
        );

        if (count($res) != 1) {
            Logger::log_event(LOG_NOTICE, "Could not extract the suitable remote CA credentials for NREN $nren!");
            throw new DBQueryException("Could not extract the suitable " .
                           "remote CA credentials for NREN $nren!"
                );
        }

        $this->login_name = $res[0]['account_login_name'];
        $this->ap_name = $res[0]['ap_name'];

        $encrypted_pw = base64_decode($res[0]['account_password']);
        $ivector = base64_decode($res[0]['account_ivector']);
        $encryption_key = Config::get_config('capi_enc_pw');
        $this->login_pw = trim(base64_decode(mcrypt_decrypt(
                                MCRYPT_RIJNDAEL_256, $encryption_key,
                                $encrypted_pw, MCRYPT_MODE_CFB,
                                $ivector)));
    }

    /**
     * Lookup a list of user certificates from cache
     * The cache is tied to a user simplesamlphp session
     *
     * @return the list of unprocessed certificates as they were received from
     *         the online CA
     */
    private function cacheLookupList()
    {
        $session = $this->person->getSession();

        if (isset($session)) {
            $raw_list = $session->getData('array', 'rawCertList');
            return $raw_list;
        } else {
            return NULL;
        }
    }

    /**
     * Insert a list of user certificates into the cache
     *
     * @param $raw_list the (unprocessed) array of certificates as they were
     *        received
     */
    private function cacheInsertList($raw_list)
    {
        $session = $this->person->getSession();
        /* session can be null, e.g. when in auth_bypass mode */
        if (isset($session)) {
            $session->setData('array',
                              'rawCertList',
                              $raw_list,
                              SimpleSAML_Session::DATA_TIMEOUT_LOGOUT);
        }
    }

    /**
     * Delete the certificate list from cache. Useful if there were changes
     * (Revocation, insertion)
     */
    private function cacheInvalidate()
    {
        $session = $this->person->getSession();

        if (isset($session)) {
            $session->deleteData('array', 'rawCertList');
        }
    }

    /**
     * Sign the CSR identified by auth_key using the Online-CA's remote API
     * @throws ConfusaGenException
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

        $this->_capi_upload_CSR($auth_key, $csr);
        $this->_capi_authorize_CSR();

        $this->cacheInvalidate();
        $this->sendMailNotification($auth_key, date('Y-m-d H:i'), $_SERVER['REMOTE_ADDR']);
	/* FIXME: conflict, not sure how to resolve, do we need both? */
        Logger::log_event(LOG_INFO, "Signed CSR for user with auth_key $auth_key");
	/* FIXME: <END> */
    }

    /**
     * Sign a browser generated CSR, generated by the specified browser
     *
     * The handling only differs in the format, which is PKCS10/CSR for IE,
     * SPKAC for keygen-enabled browsers and CMRF for Firefox.
     *
     * @param $csr the CSR
     * @param $browser the browser with which it was generated
     *
     * @return the order_number of the certificate, so its status can be
     * polled from the graphical interface
     */
    public function signBrowserCSR($csr, $browser)
    {
		/* Are the required attributes present? */
		$testAttrs = $this->verifyAttributes();
		if ($testAttrs != null) {
		    $msg  = "Error(s) with attributes:<br />\n";
		    $msg .= "<ul>$testAttrs</ul>\n";
		    $msg .= "<br />\n";
		    $msg .= "This means that you do <b>not</b> qualify for certificates at this point in time.<br />\n";
		    $msg .= "Please contact your local IT-support to resolve this issue.<br />\n";
		    throw new KeySignException($msg);
	    }

        /* use the last 64-characters of the CRMF as an auth_key */
		$auth_key = substr($csr, strlen($csr)-65, strlen($csr)-1);
        /* FIXME: Recognize IE format, that is PKCS10 */

        switch($browser) {
        case "firefox":
            $this->_capi_upload_CSR($auth_key, $csr, 'crmf');
            break;

        case "msie_post_vista":
            $this->_capi_upload_CSR($auth_key, $csr, 'csr');
            break;

        case "msie_pre_vista":
            $this->_capi_upload_CSR($auth_key, $csr, 'csr');
            break;

        case "keygen":
            $this->_capi_upload_CSR($auth_key, $csr, 'spkac');
            break;

        default:
            throw new ConfusaGenException("Browser $browser is unsupported!");
            break;
        }

        $this->_capi_authorize_CSR();
        $this->cacheInvalidate();
        $this->sendMailNotification($auth_key, date('Y-m-d H:i'), $_SERVER['REMOTE_ADDR']);
        Logger::log_event(LOG_INFO, "Signed CSR for user with auth_key $auth_key");
        return $this->order_number;
    }

    /**
     * Return an array with all the certificates obtained by the person managed by this
     * CertManager.
     *
     * Don't include expired, revoked and rejected certificates in the list
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

            $status = $params[$i . "_1_status"];
            $orderStatus = $params[$i . "_orderStatus"];

            /* don't include expired certificates */
            if (($status == "Expired") ||
                ($orderStatus == "Rejected")) {
                    continue;
            }

            /* for simplicity, format the time just as an SQL server would return it */
            $valid_untill = $params[$i . '_1_notAfter'];

            if (!empty($valid_untill)) {
                $valid_untill = date('Y-m-d H:i:s', $valid_untill);
                $res[$i-1]['valid_untill'] = $valid_untill;
            }

            if ($status == "Revoked") {
                $res[$i-1]['revoked'] = true;
            }

            $res[$i-1]['order_number'] = $params[$i . '_orderNumber'];
            $res[$i-1]['cert_owner'] = $this->person->getX509ValidCN();
        }

        return $res;
    }
    /* delete a certificate from the DB (Deprecated)
     *
     * May come in handy when we have the cache for online-certificates though.
     */
    public function deleteCertFromDB($key)
    {
	    Framework::error_output(__FILE__ . ":" . __LINE__ . " This function (deleteCertFromDB) should not be called in online-mode!");
	    return false;
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
		$this->cacheInvalidate();
        $params = $this->_capi_get_cert_list($common_name);
        $res = array();
        for ($i = 1; $i <= $params['noOfResults']; $i++) {
            /* Note that this field will not get exported if the order is not yet authorized */
            $valid_untill = $params[$i . '_1_notAfter'];
            $status = $params[$i . '_1_status'];

            /* don't consider expired, revoked or pending certificates */
            if ($status != "Valid") {
                continue;
            }

            $subject = $params[$i . '_1_subjectDN'];
            $dn_components = explode(',', $subject);

            if ($org != NULL) {
                $organization = "O=" . $this->oPrefix . $org;

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
     * Return true if processing of the certificate is finished and false
     * otherwise.
     *
     * @param $key The auth_key or order number of the certificate for which is
     * polled
     */
    public function pollCertStatus($key)
    {
        $key = $this->_transform_to_order_number($key);

        $polling_endpoint = Config::get_config('capi_collect_endpoint') .
                        "?loginName=" . $this->login_name .
                        "&loginPassword=" . $this->login_pw .
                        "&orderNumber=" . $key .
                        "&queryType=0";

		$data = $this->curlContact($polling_endpoint);

        if ($data == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve a certificate from a remote endpoint (e.g. Comodo).
     * TODO cache the certs locally for 30 minutes, in order
     * not to have to make remote calls all the time.
     *
     * @params key either an order-number that can be used to retrieve a certificate
     * directly or an auth-key with which we can retrieve the order-number
     *
     * @param $key The order-number or an auth_key that can be transformed to order_number
     * @throws ConfusaGenException
     */
    public function get_cert($key)
    {
        $key = $this->_transform_to_order_number($key);

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

		$data = $this->curlContact($collect_endpoint);

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
            $error_parts = explode("\n", $data, 2);

            /* potential error: response does not contain status code */
            if(is_numeric($error_parts[0])) {
				$msg = $this->capiErrorMessage($error_parts[0], $error_parts[1]);
				throw new RemoteAPIException("Received error message $data $msg\n");
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
			$error_parts = explode("\n", $data, 2);
			$STATUS_OK = "0";

			if (!is_numeric($error_parts[0])) {
				throw new RemoteAPIException("Received an unexpected response from " .
											"the remote API. Probably Confusa is " .
											"misconfigured! Please contact an " .
											"administrator!");
			}

			switch($error_parts[0]) {
			case $STATUS_OK:
				$this->cacheInvalidate();
				Logger::log_event(LOG_NOTICE, "Revoked certificate with " .
								  "order number $key using Comodo's AutoRevoke " .
								  "API. User contacted us from " .
								  $_SERVER['REMOTE_ADDR']);
				return true;
				break;
			default:
				$msg = $this->capiErrorMessage($error_parts[0], $error_parts[1]);
				Logger::log_event(LOG_ERROR, "Revocation of certificate with " .
				 "order_number $key failed! User contacted us from " .
				 $_SERVER['REMOTE_ADDR']);
				throw new RemoteAPIException("Received error message $data. $msg");
				break;
			}
		}
	}

	/**
	 * Poll for information about the certificate associated with key $key
	 *
	 * @param $key mixed Order number, auth_key or another certificate identifier
	 * @return array cert_owner and organization in an array
	 */
	public function getCertInformation($key)
    {
		$key = $this->_transform_to_order_number($key);

		$list_endpoint = Config::get_config('capi_listing_endpoint');
		$postfields_list = array();
        $postfields_list["loginName"]		= $this->login_name;
        $postfields_list["loginPassword"]	= $this->login_pw;
        $postfields_list["orderNumber"] = $key;
        $data = $this->curlContact($list_endpoint, "post", $postfields_list);
		$params = array();
		parse_str($data, $params);

		if (!isset($params['errorCode'])) {
			throw new RemoteAPIException("Response from Comodo API looks malformed! " .
							"Maybe the Confusa instance is misconfigured? Please " .
							"contact an administrator!");
		}

        if ($params['errorCode'] != 0) {
			$msg = $this->capiErrorMessage($data['errorCode'], $data['errorMessage']);
			throw new RemoteAPIException("Could not query information about " .
				"certificate with key $key. Error was " . $data['errorMessage'] .
				"\n\n$msg");
		}

		$info = array();
		$cn_substr = strstr($params['1_1_subjectDN'], 'CN=');
		$cn = substr($cn_substr, 3, (strpos($cn_substr, ',') - 3));
		$info['cert_owner'] = $cn;

		/* Unfortunately, the Comodo API can not return the organization in the
		 * certificate from the API - that's why we'll have to parse it from the
		 * subject-DN */
		$org_substr = strstr($params['1_1_subjectDN'], 'O=');
		$orgname = substr($org_substr, 2, (strpos($org_substr, ',') - 2));
		$info['organization'] = $orgname;

		return $info;
	} /* end getCertInformation */

    /**
     * Get the certificate with key $key in a deployable from for the specified
     * browser.
     *
     * Usually this means some kind of JavaScript to install it to the keystore,
     * but sometimes it suffices to send the certificate with the right MIME-type
     * to the browser.
     *
     * @param $key The order-number/auth-key for the certificate
     * @param $browser The browser for which the certificate should be returned
     *      Current legal values for that:
     *          firefox: return full chain as CMMF in JavaScript
     *          msie_post_vista: return full chain as PKCS7 in JavaScript
     *          msie_pre_vista: return full chain as PKCS7 in JavaScript
     *          keygen: return certificate only as string enclosed base64-encoded PKCS7
     */
    public function getCertDeploymentScript($key, $browser)
    {

        $key = $this->_transform_to_order_number($key);

        switch ($browser) {
        case "firefox":
            /* if the generating software of the request was firefox, export the
             * certificate in CMMF format embedded in JavaScript */
            $collect_endpoint = Config::get_config('capi_collect_endpoint') .
                                "?loginName=" . $this->login_name .
                                "&loginPassword=" . $this->login_pw .
                                "&orderNumber=" . $key .
                                "&queryType=1" .
                                "&responseType=4" . /* CMMF */
                                "&responseEncoding=2" . /* encode in Javascript */
                                "&responseMimeType=text/javascript" .
                                /* call that function after the JS variable-declarations */
                                "&callbackFunctionName=installCertificate";

			$data = $this->curlContact($collect_endpoint);
            return "<script type=\"text/javascript\">$data</script>";
            break;

        case "msie_post_vista":
            $collect_endpoint = Config::get_config('capi_collect_endpoint') .
                                   "?loginName=" . $this->login_name .
                                "&loginPassword=" . $this->login_pw .
                                "&orderNumber=" . $key .
                                "&queryType=1" .
                                "&responseType=2" . /* PKCS#7 */
                                "&responseEncoding=2" . /* encode in Javascript */
                                "&responseMimeType=text/javascript" .
                                /* call that function after the JS variable-declarations */
                                "&callbackFunctionName=installCertificate";

			$data = $this->curlContact($collect_endpoint);
            return "<script type=\"text/javascript\">$data</script>";
            break;

        case "msie_pre_vista":
            $collect_endpoint = Config::get_config('capi_collect_endpoint') .
                                   "?loginName=" . $this->login_name .
                                "&loginPassword=" . $this->login_pw .
                                "&orderNumber=" . $key .
                                "&queryType=1" .
                                "&responseType=2" . /* PKCS#7 */
                                "&responseEncoding=2" . /* encode in Javascript */
                                "&responseMimeType=text/javascript" .
                                /* call that function after the JS variable-declarations */
                                "&callbackFunctionName=installCertificate";

			$data = $this->curlContact($collect_endpoint);
            return "<script type=\"text/javascript\">$data</script>";
            break;

        case "keygen":
            $collect_endpoint = Config::get_config('capi_collect_endpoint') .
                                   "?loginName=" . $this->login_name .
                                    "&loginPassword=" . $this->login_pw .
                                    "&orderNumber=" . $key .
                                    "&queryType=2" .
                                    "&responseType=3" . /* PKCS#7 */
                                    "&responseEncoding=0"; /* encode base-64 */

			$data = $this->curlContact($collect_endpoint);
            return trim(substr($data,2));
            break;

        default:
            throw new ConfusaGenException("Deployment in browser $browser not supported");
            break;
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
        $raw_list = $this->cacheLookupList();

        if (!is_null($raw_list)) {
            return $raw_list;
        }

        Logger::log_event(LOG_DEBUG, "Trying to get the list with the certificates " .
                                    "for person $common_name");

        $list_endpoint = Config::get_config('capi_listing_endpoint');
        $postfields_list["loginName"]		= $this->login_name;
        $postfields_list["loginPassword"]	= $this->login_pw;
        $postfields_list["commonName"]		= $this->TEST_CN_PREFIX . $common_name;

        $data = $this->curlContact($list_endpoint, "post", $postfields_list);
        $params=array();
        parse_str($data, $params);

        if (!isset($params['errorCode'])) {
			$msg  = "Unexpected response from remote endpoint. ";
			$msg .= "Perhaps some configuration-switch is not properly set.";
			$msg .= "Server gave no error-code.";
			throw new RemoteAPIException($msg);
        }

        if ($params['errorCode'] == "0") {
            $this->cacheInsertList($params);
            return $params;
        } else {
			$msg = $this->capiErrorMessage($params['errorCode'], $params['errorMessage']);
            throw new RemoteAPIException("Received error when trying to list " .
                                         "certificates from the remote-API: " .
                                         $params['errorMessage'] . $msg
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
     * @param $auth_key Identifier for the cert. Usually a sha1sum over the public key.
     * @param $csr The certificate signing request
     * @param $csr_format Exactly one of "csr" (PKCS10), "crmf" or "spkac"
     *
     * @throws ConfusaGenException
    */
    private function _capi_upload_CSR($auth_key, $csr, $csr_format = "csr")
    {
        $sign_endpoint = Config::get_config('capi_apply_endpoint');
        $ca_cert_id = Config::get_config('capi_escience_id');

        $postfields_sign_req=array();

        /* clutter TEST all over it and reduce validity period
         * if the certs are part of a testing process
         */
        if (Config::get_config('capi_test')) {
          $postfields_sign_req["subject_domainComponent_7"] = CertManager_Online::$TEST_DC_PREFIX;
          $days = '14';
        } else {
          $days = '395';
        }

        /* set all the required post parameters for upload */
        $postfields_sign_req["ap"] = $this->ap_name;
        $postfields_sign_req[$csr_format] = $csr;
        $postfields_sign_req["days"] = $days;
        $postfields_sign_req["successURL"] = "none";
        $postfields_sign_req["errorURL"] = "none";
        $postfields_sign_req["caCertificateId"] = $ca_cert_id;
        /* manually compose the subject. Necessary, because we want to have
         * Terena domainComponents */
        $postfields_sign_req["subject_commonName_1"] = $this->cnPrefix .
            $this->person->getX509ValidCN();
        $postfields_sign_req["subject_organizationName_2"] = $this->oPrefix .
            $this->person->getSubscriberOrgName();
        $postfields_sign_req["subject_countryName_3"] = $this->person->getCountry();
        $postfields_sign_req["subject_domainComponent_4"] = "tcs";
        $postfields_sign_req["subject_domainComponent_5"] = "terena";
        $postfields_sign_req["subject_domainComponent_6"] = "org";

		$data = $this->curlContact($sign_endpoint, "post", $postfields_sign_req);

        $params=array();
        parse_str($data, $params);
        /*
         * If something has failed, an errorCode parameter will be set in
         * the return message
         */
        if (isset($params['errorCode'])) {
            $msg = "Received an error when uploading the CSR to the remote CA: " .
                $params['errorMessage'] . " " . $params['errorItem'] .
				$this->capiErrorMessage($params['errorCode'], $params['errorMessage']);
            throw new RemoteAPIException($msg);
        }

        else {

            if (!isset($params['orderNumber'])) {
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
	 * Send a POST message containing $postData to the endpoint in $url
	 *
	 * @param $url string the endpoint to which the POST message should be sent
	 * @param $method string whether GET or POST should be used to conact the
	 * 				remote site
	 * @param $postData array the POST variables that are to be send
	 *
	 * @return string the result of the communication
	 */
	private function curlContact($url, $method="get", $postData=null)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		if ($method == "post") {
			curl_setopt($ch, CURLOPT_POST,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		}

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
		$status = curl_errno($ch);
        curl_close($ch);

		if ($status != 0) {
			throw new RemoteAPIException("Could not connect properly to remote API " .
										"endpoint $url! Maybe the Confusa instance is misconfigured? " .
										"Please contact an administrator!");
		}

		return $data;
	}

	/**
	 * Return a textual and user-understandable message for common remote-API
	 * errors.
	 *
	 * @param $errorCode int a usually 2-3 digits long error code returned by the Comodo API
	 * @return string a verbose message corresponding to the error code
	 */
	private function capiErrorMessage($errorCode, $errorMessage)
	{
		$msg = "";

		switch($errorCode) {
		case "-3":
		case "-4":
			if (strpos($errorMessage, "loginPassword") !== FALSE ||
				strpos($errorMessage, "loginName") !== FALSE ||
				strpos($errorMessage, "ap") !== FALSE) {
					$msg .= "<br /><br />Probably this error message means that something is wrong ";
					$msg .= "with the credentials with which Confusa connects to the remote CA.";
					$msg .= " The credentials are defined per NREN, ";
					$msg .= "in your case for " . $this->person->getNREN() . ".";
					$msg .= " Please ask an administrator to configure this properly.";
			}
			break;
		case "-16":
			$msg .= "<br /><br />Probably this error message means that something is wrong ";
			$msg .= "with the credentials with which Confusa connects to the remote CA.";
			$msg .= " The credentials are defined per NREN, ";
			$msg .= "in your case for " . $this->person->getNREN() . ".";
			$msg .= " Please ask an administrator to configure this properly.";
			break;
		case "-13":
			$msg .= "<br /><br />You created a certificate with a non-standard keysize! Please ";
			$msg .= "create your certificate requests with a keysize of " . Config::get_config('key_length');
			$msg .= " bits!";
			break;
		case "-20":
			$msg .= "<br /><br />Your certificate request has been rejected, either by mistake ";
			$msg .= "or because you are not entitled to get certificates. Please contact an ";
			$msg .= "administrator.";
			break;
		case "-21":
			$msg .= "<br /><br />The certificate has been revoked, either by yourself or an ";
			$msg .= "administrator. You can not use it anymore and you should not download it ";
			$msg .= "anymore!";
			break;
		}

		return $msg;
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

        $postfields_auth = array();
        $postfields_auth["loginName"] = $this->login_name;
        $postfields_auth["loginPassword"] = $this->login_pw;
        $postfields_auth["orderNumber"] = $this->order_number;
		$data = $this->curlContact($authorize_endpoint, "post", $postfields_auth);

        /* the only formal restriction we have is if the API returns 0 for the query */
        if (substr($data,0,1) == "0") {
          /* update the database-entry to reflect the autorization-state */
          MDB2Wrapper::update("UPDATE order_store SET authorized='authorized' WHERE order_number=?",
                              array('text'),
                              array($this->order_number));
          Logger::log_event(LOG_NOTICE, "Authorized remote certificate for person ".
                                        $this->person->getX509ValidCN().
                                        " with order number " .
                                        $this->order_number .
                                        " Person contacted us from ".
                                        $_SERVER['REMOTE_ADDR']);
        } else {
            $msg = "Received an error when authorizing the CSR with orderNumber " .
                   $this->order_number . $data . "\n";
				   $error_parts = explode("\n", $data, 2);
			$msg .= $this->capiErrorMessage($error_parts[0], $error_parts[1]);
            throw new RemoteAPIException($msg);
        }

    } /* end _capi_authorize_csr */

} /* end class OnlineCAManager */
?>
