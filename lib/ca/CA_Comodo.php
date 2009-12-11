<?php
declare(encoding = 'utf-8');
require_once 'person.php';
require_once 'CA.php';
require_once 'key_sign.php';
require_once 'db_query.php';
require_once 'mdb2_wrapper.php';
require_once 'CGE_ComodoAPIException.php';
require_once 'confusa_constants.php';
require_once 'CGE_ComodoCredentialException.php';
require_once 'curlwrapper.php';

/**
 * CA_Comodo. Comodo signing extension for CA.
 *
 * Class for connecting to Comodo, uploading CSRs,
 * ordering certificates, listing certificates etc.
 *
 * PHP version 5
 * @author: Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class CA_Comodo extends CA
{
    /* order number for communication with the remote API */
    private $order_number;

    /* login-name and password for the remote-signing CA */
    private $login_name;
    private $login_pw;
    /* alliance-partner name for the remote-signing CA */
    private $ap_name;


    function __construct($pers)
    {
        parent::__construct($pers);
        $this->getAccountInformation();
    }

    /**
     * Get username and password for the remote-CA account of the
     * (NREN of) the managed person.
     */
    private function getAccountInformation() {

		$nren = $this->person->getNREN();

		/* can only get the account if we have NREN information */
		if (empty($nren)) {
			return;
		}

        $login_cred_query = "SELECT a.account_login_name, a.account_password, a.account_ivector, a.ap_name " .
              "FROM nren_account_map_view a WHERE a.nren=?";

        $nren = $this->person->getNREN();
	if (Config::get_config('debug')) {
		Logger::log_event(LOG_INFO, __CLASS__ . "::" . __FUNCTION__ .
				  " Getting the remote-CA login " .
				  "credentials for NREN " .
				  $nren
			);
	}
	try {
		$errorCode = create_pw(8);
		$errorMsg = "[$errorCode] " . __FILE__ . ":" . __LINE__ . " ";

		$res = MDB2Wrapper::execute($login_cred_query, array('text'),
					    array($nren));
	} catch (DBStatementException $dbse) {
		Logger::log_event(LOG_ALERT, "$errorMsg missing columns in account_map/nren_account_map_view");
		$errorMsg .= "<br />The table does not have all required columns. Please contact operational support.";
		throw new CGE_ComodoCredentialException($errorMsg);
	} catch (DBQueryException $dbqe) {
		if (is_null($nren) || $nren =="") {
			Logger::log_event(LOG_NOTICE, "$errorMsg - Look for subscriber-map problems.");
			$errorMsg .= "<br />NREN-name not properly set, cannot extract account-credentials.";
			throw new CGE_ComodoCredentialException($errorMsg);
		} else {
			$errorMsg .= " unknown query-inconsistency";
			Logger::log_event(LOG_NOTICE, $errorMsg);
			throw new CGE_ComodoCredentialException($errorMsg);
		}
	}
        if (count($res) != 1) {
            Logger::log_event(LOG_NOTICE, "Could not extract the suitable remote CA credentials for NREN $nren!");
            throw new CGE_ComodoCredentialException("Could not extract the suitable " .
                           "remote CA credentials for NREN " . $this->person->getNREN() . "!<br />\n");
        }

        $this->login_name = $res[0]['account_login_name'];
        $this->ap_name = $res[0]['ap_name'];

		if (!isset($this->login_name) || !isset($this->ap_name)) {
			return;
		}

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
    } /* end cacheInsertList */

	/**
	 * Set an expiry date on the cache based on the time of the latest
	 * certificate order. The idea is that the more recently a certificate has
	 * been ordered, the higher the likelihood that something about the
	 * certificate's status will change 'soon'. Thus the cache expiration
	 * interval is small, if the time interval since the last certificate order
	 * is small.
	 *
	 * Currently the interval is:      up to 2 minutes since order: 30 seconds
	 *                                   2 - 6 minutes since order: 1 minute
	 *                                  6 - 15 minutes since order: 2 minutes
	 *                                 15 - 30 minutes since order: 5 minutes
	 *                            more than 30 minutes since order: 10 minutes
	 *
	 * @param $timeSinceCertificateOrder integer The time passed since the
	 * 								certificate order
	 */
	private function cacheSetExpiryDate($timeSinceCertificateOrder)
	{
		$timeSinceCertificateOrder = floor($timeSinceCertificateOrder / 60);
		$session = $this->person->getSession();

		if (!isset($session)) {
			return;
		}

		switch($timeSinceCertificateOrder) {
		case 0:
		case 1:
			$session->setData('integer', 'confusaCacheTimeout', time() + 30);
			break;
		case 2:
		case 3:
		case 4:
		case 5:
			$session->setData('integer', 'confusaCacheTimeout', time() + 60);
			break;
		case 6:
		case 7:
		case 8:
		case 9:
		case 10:
		case 11:
		case 12:
		case 13:
		case 14:
		case 15:
			$session->setData('integer', 'confusaCacheTimeout', time() + 120);
			break;
		case 16:
		case 17:
		case 18:
		case 19:
		case 20:
		case 21:
		case 22:
		case 23:
		case 24:
		case 25:
		case 26:
		case 27:
		case 28:
		case 29:
		case 30:
			$session->setData('integer', 'confusaCacheTimeout', time() + 300);
			break;
		default:
			$session->setData('integer', 'confusaCacheTimeout', time() + 600);
			break;
		}
	}

	/**
	 * Return true if the confusa certificate cache data has expired. Expiry
	 * is mainly set by cacheSetExpiryDate
	 *
	 * @return boolean true if cache data has expired, false otherwise
	 */
	private function cacheHasExpired()
	{
		$session = $this->person->getSession();

		if (isset($session)) {
			$cacheTimeout = $session->getData('integer', 'confusaCacheTimeout');

			if (empty($cacheTimeout)) {
				return true;
			} else {
				return $cacheTimeout < time();
			}
		} else {
			return true;
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
	public function signKey($auth_key, $csr)
	{
		$this->capiUploadCSR($auth_key, $csr);
		$this->capiAuthorizeCSR();

		$this->cacheInvalidate();
		CA::sendMailNotification($this->order_number,
		                         date('Y-m-d H:i T'),
		                         $_SERVER['REMOTE_ADDR'],
		                         ConfusaConstants::$ESCIENCE_PRODUCT,
		                         $this->person);
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
        /* use the last 64-characters of the CRMF as an auth_key */
		$auth_key = substr($csr, strlen($csr)-65, strlen($csr)-1);

        switch($browser) {
        case "msie_post_vista":
            $this->capiUploadCSR($auth_key, $csr, 'csr');
            break;

        case "msie_pre_vista":
            $this->capiUploadCSR($auth_key, $csr, 'csr');
            break;

        case "keygen":
            $this->capiUploadCSR($auth_key, $csr, 'spkac');
            break;

        default:
            throw new ConfusaGenException("Browser $browser is unsupported!");
            break;
        }

		$this->capiAuthorizeCSR();
		$this->cacheInvalidate();
		CA::sendMailNotification($this->order_number,
		                         date('Y-m-d H:i T'),
		                         $_SERVER['REMOTE_ADDR'],
		                         ConfusaConstants::$ESCIENCE_PRODUCT,
		                         $this->person);

		Logger::log_event(LOG_INFO, "Signed CSR for user with order_number " .
		                            $this->order_number);
        return $this->order_number;
    }

    /**
     * Return an array with all the certificates obtained by the person managed by this
     * CA.
     *
     * Don't include expired, revoked and rejected certificates in the list
     * @throws CGE_ComodoAPIException
     */
    public function getCertList()
    {
		if (!$this->cacheHasExpired()) {
			$res = $this->cacheLookupList();

			if (isset($res)) {
				return $res;
			}
		}

        $common_name = $this->person->getX509ValidCN();
        $organization = 'O=' . $this->person->getSubscriber()->getOrgName();

        $params = $this->capiGetCertList($common_name);
        $res=array();
		$dates = array();

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

            $subject = $params[$i . '_1_subjectDN'];
            $dn_components = explode(',', $subject);

			/* don't return order number and the owner subject
			 * if the organization is not present in the DN
			 */
			if (array_search($organization, $dn_components) === false) {
				continue;
			}


			if (isset($params[$i . '_1_notAfter'])) {
				/* for simplicity, format the time just as an SQL server would return it */
				$valid_untill = $params[$i . '_1_notAfter'];
				$valid_untill = date('Y-m-d H:i:s', $valid_untill);
				$res[$i-1]['valid_untill'] = $valid_untill;
			}

            $res[$i-1]['order_number'] = $params[$i . '_orderNumber'];
            $res[$i-1]['cert_owner'] = $this->person->getX509ValidCN();
			$res[$i-1]['status'] = $status;
			$dates[] = time() - $params[$i . '_dateTime'];
        }

		$this->cacheSetExpiryDate(min($dates));
		$this->cacheInsertList($res);
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
     * Restrict the result set to organization $org.
     *
     * Dependant on the form of the query, the order in which it filters for
     * commonName and organizationName will be different.
     *
     * The filtering will be performed as follows:
     * 	- pure wildcard search "%": remote organizationName, then local common-name
     * 	- short search string with two non-adjacent wildcards, like "%jo%" -
     * 			remote organizationName, then local common-name
     *	- string containing "@": remote common-name, then local organization-name
     * 	- all others: remote common-name, then local organization-name
     *
     * @param $common_name The common_name to search for
     * @param $org The organization to restrict the search to
     *
     * @return An array with each row consisting of
     * 		- orderNumber
     * 		- validUntil
     * 		- subjectDN
     * of the matched certificate.
     */
    public function getCertListForPersons($common_name, $org)
    {

		/* org-name *must* be set */
		if (empty($org)) {
			return NULL;
		}

		/* don't want to do work twice - if one of these is set, don't match
		 * orgname or CN in PHP any more */
		$organizationVerified = false;
		$cnVerified = false;

		/* the common-name consists only of a wildcard, effectively this is an
		 * organization-wide search */
		if (trim($common_name) == "%") {
			$params = $this->capiGetOrgCertList($org);
			$organizationVerified = true;
			$cnVerified = true;
		/* the common-name consists of two non-adjacent wildcards with a total
		 * length smaller than 7, meaning something like "%jo%". Here it is
		 * probably more efficient to search for the organization name first.
		 */
		} else if (substr_count($common_name, "%") >= 2 &&
		           stripos($common_name, "%%") === false &&
		           strlen($common_name) < 9) {
			$params = $this->capiGetOrgCertList($org);
			$organizationVerified = true;
		/* eppn-ish, expecting fewer results, do a common_name search */
		} else if (stripos($common_name, "@") !== false) {
			$params = $this->capiGetCertList($common_name);
			$cnVerified = true;
		/* longer search string, expecting fewer results if querying for the
		 * common-name first */
		} else {
			$params = $this->capiGetCertList($common_name);
			$cnVerified = true;
		}

        $res = array();
        $organization = "O=" . $org;
        /* rewrite SQL-ish wildcards to grep-wildcards from the common-name string */
        $cn =  "/CN=" . str_replace("%", "(.)*", $common_name) . "/";

        for ($i = 1; $i <= $params['noOfResults']; $i++) {
		if (!array_key_exists($i . '_1_status', $params)) {
			continue;
		}
            $status = $params[$i . '_1_status'];

            /* don't consider expired, revoked or pending certificates */
            if ($status != "Valid") {
                continue;
            }

            $subject = $params[$i . '_1_subjectDN'];

			/* don't return order number and the owner subject
			 * if the organization is not present in the DN
			 */
			if (!$organizationVerified &&
			    strpos($subject, $organization) === false) {
				continue;
			}

			if (!$cnVerified &&
			    preg_match($cn, $subject) === 0) {
				continue;
			}

			/* Note that this field will not get exported if the order is not yet authorized */
            if (!empty($params[$i . '_1_notAfter'])) {
				$valid_untill = $params[$i . '_1_notAfter'];
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
        $key = $this->transformToOrderNumber($key);

        $polling_endpoint = ConfusaConstants::$CAPI_COLLECT_ENDPOINT .
                        "?loginName=" . $this->login_name .
                        "&loginPassword=" . $this->login_pw .
                        "&orderNumber=" . $key .
                        "&queryType=0";

		$data = CurlWrapper::curlContact($polling_endpoint);

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
    public function getCert($key)
    {
        $key = $this->transformToOrderNumber($key);

        Logger::log_event(LOG_NOTICE, "Trying to retrieve certificate with order number " .
                                      $key .
                                      " from the Comodo collect API. Sending to user with ip " .
                                      $_SERVER['REMOTE_ADDR']);

        $collect_endpoint = ConfusaConstants::$CAPI_COLLECT_ENDPOINT .
                            "?loginName=" . $this->login_name .
                            "&loginPassword=" . $this->login_pw .
                            "&orderNumber=" . $key .
                            "&queryType=2" .
                            "&responseMimeType=application/x-x509-user-cert";

		$data = CurlWrapper::curlContact($collect_endpoint);

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
				throw new CGE_ComodoAPIException("Received error message $data $msg\n");
            } else {
              $msg = "Received an unexpected response from the remote API!n" .
                     "Maybe Confusa is not properly configured?\n";
              throw new CGE_ComodoAPIException($msg);
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
     * @throws CGE_ComodoAPIException if revocation fails
     */
    public function revokeCert($key, $reason)
    {
        $key = $this->transformToOrderNumber($key);

        $return_res = NULL;

        Logger::log_event(LOG_NOTICE, "Trying to revoke certificate with order number " .
                                      $key .
                                      " using Comodo's auto-revoke-API. Sending to user with ip " .
                                      $_SERVER['REMOTE_ADDR']);

        $revoke_endpoint = ConfusaConstants::$CAPI_REVOKE_ENDPOINT;
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

		$data = CurlWrapper::curlContact($revoke_endpoint, "post", $postfields_revoke);

        /* try to catch all kinds of errors that can happen when connecting */
        if ($data === FALSE) {
            throw new CGE_ComodoAPIException("Could not connect to revoke-API! " .
                                        "Check Confusa configuration!\n"
            );
        } else {
			$error_parts = explode("\n", $data, 2);
			$STATUS_OK = "0";

			if (!is_numeric($error_parts[0])) {
				throw new CGE_ComodoAPIException("Received an unexpected response from " .
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
				throw new CGE_ComodoAPIException("Received error message $data. $msg");
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
		$key = $this->transformToOrderNumber($key);

		$list_endpoint = ConfusaConstants::$CAPI_LISTING_ENDPOINT;
		$postfields_list = array();
        $postfields_list["loginName"]		= $this->login_name;
        $postfields_list["loginPassword"]	= $this->login_pw;
        $postfields_list["orderNumber"] = $key;
        $data = CurlWrapper::curlContact($list_endpoint, "post", $postfields_list);
		$params = array();
		parse_str($data, $params);

		if (!isset($params['errorCode'])) {
			throw new CGE_ComodoAPIException("Response from Comodo API looks malformed! " .
			                                 "Maybe the Confusa instance is misconfigured? Please " .
			                                 "contact an administrator!");
		}

        if ($params['errorCode'] != 0) {
			$msg = $this->capiErrorMessage($data['errorCode'], $data['errorMessage']);
			throw new CGE_ComodoAPIException("Could not query information about " .
			                                 "certificate with key $key. Error was " .
			                                 $data['errorMessage'] . "\n\n$msg");
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

        $key = $this->transformToOrderNumber($key);

        switch ($browser) {
        case "msie_post_vista":
            $collect_endpoint = ConfusaConstants::$CAPI_COLLECT_ENDPOINT .
                                   "?loginName=" . $this->login_name .
                                "&loginPassword=" . $this->login_pw .
                                "&orderNumber=" . $key .
                                "&queryType=1" .
                                "&responseType=2" . /* PKCS#7 */
                                "&responseEncoding=2" . /* encode in Javascript */
                                "&responseMimeType=text/javascript" .
                                /* call that function after the JS variable-declarations */
                                "&callbackFunctionName=installIEVistaCertificate";

			$data = CurlWrapper::curlContact($collect_endpoint);
            return "<script type=\"text/javascript\">$data</script>";
            break;

        case "msie_pre_vista":
            $collect_endpoint = ConfusaConstants::$CAPI_COLLECT_ENDPOINT .
                                   "?loginName=" . $this->login_name .
                                "&loginPassword=" . $this->login_pw .
                                "&orderNumber=" . $key .
                                "&queryType=1" .
                                "&responseType=2" . /* PKCS#7 */
                                "&responseEncoding=2" . /* encode in Javascript */
                                "&responseMimeType=text/javascript" .
                                /* call that function after the JS variable-declarations */
                                "&callbackFunctionName=installIEXPCertificate";

			$data = CurlWrapper::curlContact($collect_endpoint);
            return "<script type=\"text/javascript\">$data</script>";
            break;

        case "keygen":
            $collect_endpoint = ConfusaConstants::$CAPI_COLLECT_ENDPOINT .
                                   "?loginName=" . $this->login_name .
                                    "&loginPassword=" . $this->login_pw .
                                    "&orderNumber=" . $key .
                                    "&queryType=2" .
                                    "&responseType=3" . /* PKCS#7 */
                                    "&responseEncoding=0"; /* encode base-64 */

			$data = CurlWrapper::curlContact($collect_endpoint);
            return trim(substr($data,2));
            break;

        default:
            throw new ConfusaGenException("Deployment in browser $browser not supported");
            break;
        }
    }

    /*
     * Query the remote API for the list of certificates belonging to
     * common_name $common_name.
     *
     * @param $common_name The common-name for which the list is retrieved
     */
    private function capiGetCertList($common_name)
    {
        Logger::log_event(LOG_DEBUG, "Trying to get the list with the certificates " .
                                    "for person $common_name");

        $list_endpoint = ConfusaConstants::$CAPI_LISTING_ENDPOINT;
        $postfields_list["loginName"]		= $this->login_name;
        $postfields_list["loginPassword"]	= $this->login_pw;
        $postfields_list["commonName"]		= $common_name;

        $data = CurlWrapper::curlContact($list_endpoint, "post", $postfields_list);
        $params=array();
        parse_str($data, $params);

        if (!isset($params['errorCode'])) {
			$msg  = "Unexpected response from remote endpoint. ";
			$msg .= "Perhaps some configuration-switch is not properly set.";
			$msg .= "Server gave no error-code.";
			throw new CGE_ComodoAPIException($msg);
        }

        if ($params['errorCode'] == "0") {
            return $params;
        } else {
			$msg = $this->capiErrorMessage($params['errorCode'], $params['errorMessage']);
			throw new CGE_ComodoAPIException("Received error when trying to list " .
			                                 "certificates from the remote-API: " .
			                                 $params['errorMessage'] . $msg
            );
        }
    }

	/**
	 * Query the remote API for the list of certificates belonging to
	 * organization $organization.
	 *
	 * @param $organization The organization for which the list is retrieved
	 */
    private function capiGetOrgCertList($organization)
    {
		Logger::log_event(LOG_DEBUG, "Trying to get the list with the certificates " .
							"for organization $organization");

		$listEndpoint = ConfusaConstants::$CAPI_LISTING_ENDPOINT;
		$postfieldsList["loginName"]		= $this->login_name;
		$postfieldsList["loginPassword"]	= $this->login_pw;
		$postfieldsList["organizationName"]	= $organization;

		$data = CurlWrapper::curlContact($listEndpoint, "post", $postfieldsList);
		$params =array();
		parse_str($data, $params);

		if ($params['errorCode'] == "0") {
			return $params;
		} else {
			$msg = $this->capiErrorMessage($params['errorCode'], $params['errorMessage']);
			throw new CGE_ComodoAPIException("Received error when trying to list " .
			                                 "certificates from the remote-API: " .
			                                 $params['errorMessage'] . $msg);
		}
	} /* end capiGetOrgCertList */

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
    private function capiUploadCSR($auth_key, $csr, $csr_format = "csr")
    {
        $sign_endpoint = ConfusaConstants::$CAPI_APPLY_ENDPOINT;
        $ca_cert_id = ConfusaConstants::$CAPI_ESCIENCE_ID;

        $postfields_sign_req=array();

        /* clutter TEST all over it and reduce validity period
         * if the certs are part of a testing process
         */
        if (Config::get_config('capi_test')) {
          $postfields_sign_req["subject_domainComponent_7"] = ConfusaConstants::$CAPI_TEST_DC_PREFIX;
          $days = ConfusaConstants::$CAPI_TEST_VALID_DAYS;
        } else {
          $days = ConfusaConstants::$CAPI_VALID_DAYS;
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
        $postfields_sign_req["subject_commonName_1"] =
            $this->person->getX509ValidCN();

        $postfields_sign_req["subject_organizationName_2"] =
		$this->person->getSubscriber()->getOrgName();
        $postfields_sign_req["subject_countryName_3"] = $this->person->getNREN()->getCountry();
        $postfields_sign_req["subject_domainComponent_4"] = "tcs";
        $postfields_sign_req["subject_domainComponent_5"] = "terena";
        $postfields_sign_req["subject_domainComponent_6"] = "org";

		$data = CurlWrapper::curlContact($sign_endpoint, "post", $postfields_sign_req);

        $params=array();
        parse_str($data, $params);
        /*
         * If something has failed, an errorCode parameter will be set in
         * the return message
         */
        if (isset($params['errorCode'])) {
		$msg = "Received an error when uploading the CSR to the remote CA: ";
		if (isset($params['errorMessage'])) {
			$msg .= " " . $params['errorMessage'];
		}
		if (isset($params['errorItem'])) {
			$msg .= " " . $params['errorItem'];
		}
		$this->capiErrorMessage($params['errorCode'], $params['errorMessage']);
		throw new CGE_ComodoAPIException($msg);
        }

        else {

            if (!isset($params['orderNumber'])) {
                $msg = "Response looks malformed. Maybe there is a configuration " .
                       "error in Confusa's Comodo-CA configuration!";
                throw new CGE_ComodoAPIException($msg);
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
        } /* end capiUploadCSR */
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
    private function transformToOrderNumber($auth_key)
    {
      /* first check if it is an order number already */
      if (is_numeric($auth_key)) {
        /* if it is numeric, chances are quite high that we have an order number.
         * at the same time this is the only formal restriction we have for an order number.
        */
        return $auth_key;
      } else if(strlen($auth_key) == ConfusaConstants::$AUTH_KEY_LENGTH) {
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
    private function capiAuthorizeCSR()
    {
        $authorize_endpoint = ConfusaConstants::$CAPI_AUTH_ENDPOINT;

        $postfields_auth = array();
        $postfields_auth["loginName"] = $this->login_name;
        $postfields_auth["loginPassword"] = $this->login_pw;
        $postfields_auth["orderNumber"] = $this->order_number;
		$data = CurlWrapper::curlContact($authorize_endpoint, "post", $postfields_auth);

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
            throw new CGE_ComodoAPIException($msg);
        }

    } /* end capiAuthorizeCSR */

} /* end class CA_Comodo */
?>
