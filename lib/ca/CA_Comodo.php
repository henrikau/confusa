<?php
declare(encoding = 'utf-8');
require_once 'Person.php';
require_once 'CA.php';
require_once 'key_sign.php';
require_once 'db_query.php';
require_once 'MDB2Wrapper.php';
require_once 'CGE_ComodoAPIException.php';
require_once 'confusa_constants.php';
require_once 'CGE_ComodoCredentialException.php';
require_once 'CurlWrapper.php';
require_once 'CS.php';
require_once 'NRENAccount.php';

/**
 * CA_Comodo. Comodo signing extension for CA.
 *
 * Class for connecting to Comodo, uploading CSRs,
 * ordering certificates, listing certificates etc.
 *
 * PHP version 5
 * @author: Thomas Zangerl <tzangerl@pdc.kth.se>
 * @author: Henrik Austad <henrik@austad.us>
 */
class CA_Comodo extends CA
{
    /* order number for communication with the remote API */
    private $order_number;
	private $account;


    function __construct($pers, $validityPeriod)
    {
        parent::__construct($pers, $validityPeriod);

		$this->account = NRENAccount::get($pers);
		if (Config::get_config('capi_test') == true) {
			$this->dcs[] = ConfusaConstants::$CAPI_TEST_DC_PREFIX;
		}

		if (Config::get_config('cert_product') == PRD_ESCIENCE) {
			$this->dcs[] = "org";
			$this->dcs[] = "terena";
			$this->dcs[] = "tcs";
		}
    }

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

		switch($timeSinceCertificateOrder) {
		case 0:
		case 1:
			CS::setSessionKey('confusaCacheTimeout', time() + 30);
			break;
		case 2:
		case 3:
		case 4:
		case 5:
			CS::setSessionKey('confusaCacheTimeout', time() + 60);
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
			CS::setSessionKey('confusaCacheTimeout', time() + 120);
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
			CS::setSessionKey('confusaCacheTimeout', time() + 300);
			break;
		default:
			CS::setSessionKey('confusaCacheTimeout', time() + 600);
			break;
		}
	}

	/**
	 * Return true if all certs going back for $days days are stored in the
	 * certificate cache. The cache has also an expiry-date, so the function
	 * additionally checks if the cache has not expired. If both conditions are
	 * met (number of days stored in cache and cache not expired),
	 * the function returns true.
	 *
	 * @param $days integer The number of days in the certificate history
	 *                      that the cache stores
	 * @return boolean true if valid cert history found, false otherwise
	 */
	private function cacheHasCertHistory($days)
	{
		$cacheTimeout = CS::getSessionKey('confusaCacheTimeout');
		$cachedDays   = CS::getSessionKey('confusaCachedDays');

		if (empty($cacheTimeout)) {
			return false;
		} else if (empty($cachedDays)) {
			return false;
		/* do we have the full history? */
		} else if ($cachedDays >= $days) {
			/* is the cache not expired? */
			return $cacheTimeout > time();
		} else {
			return false;
		}
	}


	/**
	 * Sign the CSR identified by auth_key using the Online-CA's remote API
	 *
	 * @param	String the auth-key used to identify the CSR in the database
	 * @param	CSR the CSR to be signed
	 * @return	void
	 * @access	public
	 *
	 * @fixme	make sure all callers of signKey is updated to use CSR.
	 */
	public function signKey($csr)
	{
		if (!$this->person->getSubscriber()->isSubscribed()) {
			throw new KeySignException("Subscriber not subscribed, cannot create certificate!");
		}

		$authKey = $csr->getAuthToken();
		Logger::logEvent(LOG_INFO, __CLASS__, "signKey()",
		                 "Preparing to sign CSR ($authKey) " .$this->owner_string,
		                 __LINE__);

		/* FIXME: better solution */
		if ($csr instanceof CSR_PKCS10) {
			$this->capiUploadCSR($authKey,
			                     $csr->getPEMContent(),
			                     ConfusaConstants::$CAPI_FORMAT_PKCS10);
		} else if ($csr instanceof CSR_SPKAC) {
			$this->capiUploadCSR($authKey,
			                     $csr->getDERContent(),
			                     ConfusaConstants::$CAPI_FORMAT_SPKAC);
		}

		$this->capiAuthorizeCSR();

		CS::deleteSessionKey('rawCertList');
		$timezone = new DateTimeZone($this->person->getTimezone());
		$dt       = new DateTime("now", $timezone);

		CA::sendMailNotification($this->order_number,
		                         $dt->format('Y-m-d H:i T'),
		                         $_SERVER['REMOTE_ADDR'],
		                         $this->person,
		                         $this->getFullDN());
		Logger::log_event(LOG_INFO, "Successfully signed new certificate. ". $this->owner_string);
		return $this->order_number;
	} /* end signKey() */

    /**
     * Return an array with all the certificates obtained by the person managed by this
     * CA.
     *
     * Don't include expired, revoked and rejected certificates in the list
     * @param $showAll boolean retrieve all certificates (time limit does not apply)
     * @throws CGE_ComodoAPIException
     */
    public function getCertList($getAll = false)
    {
		if ($getAll === true) {
			if (Config::get_config('capi_test') == true) {
				$days = ConfusaConstants::$CAPI_TEST_VALID_DAYS;
			} else {
				if (Config::get_config('cert_product') == PRD_PERSONAL) {
					$days = max(ConfusaConstants::$CAPI_VALID_PERSONAL);
				} else {
					$days = ConfusaConstants::$CAPI_VALID_ESCIENCE;
				}
			}
		} else {
			$days = Config::get_config('capi_default_cert_poll_days');
		}

		/*
		 * TODO: Refactor the whole mess - for instance by making a separate
		 * "Certificate" class
		 */
		if ($this->cacheHasCertHistory($days)) {
			$res = CS::getSessionKey('rawCertList');

			if (isset($res)) {
				/* apply local date filtering (much faster than querying again) */
				if (!$getAll) {
					$filtered_res = array();
					foreach ($res as $row) {
						if ($row['valid_from'] >= (time() - $days*24*3600)) {
							$filtered_res[] = $row;
						}
					}
					return $filtered_res;
				} else {
					return $res;
				}
			}
		}

        $uid = $this->person->getEPPN();
        $organization = 'O=' . $this->person->getSubscriber()->getOrgName();

        $params = $this->capiGetEPPNCertList($uid, $days);
        $res=array();
		$dates = array();
		/* initiallize the array with a high value, so that the cache stays
		 * valid very long if there are no certificates at all (ordering a
		 * cert will invalidate it anyways) */
		$dates[] = time();
		$timezone = new DateTimeZone($this->person->getTimezone());

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
				$dt = new DateTime("@$valid_untill");
				$dt->setTimezone($timezone);
				$valid_untill = $dt->format('Y-m-d H:i:s T');
				$res[$i-1]['valid_untill'] = $valid_untill;
			}

            $res[$i-1]['order_number'] = $params[$i . '_orderNumber'];
            $res[$i-1]['cert_owner'] = stripslashes($this->person->getX509ValidCN());
			$res[$i-1]['status'] = $status;

			if (isset($params[$i . '_1_notBefore'])) {
				$res[$i-1]['valid_from'] = $params[$i . '_1_notBefore'];
			} else {
				$res[$i-1]['valid_from'] = 0;
			}

			$dates[] = time() - $params[$i . '_dateTime'];
        }

		$this->cacheSetExpiryDate(min($dates));
		CS::setSessionKey('rawCertList', $res);
		CS::setSessionKey('confusaCachedDays', $days);
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

		if (Config::get_config('capi_test') === true) {
			$days = ConfusaConstants::$CAPI_TEST_VALID_DAYS;
		} else {
			if (Config::get_config('cert_product') == PRD_PERSONAL) {
				$days = max(ConfusaConstants::$CAPI_VALID_PERSONAL);
			} else {
				$days = ConfusaConstants::$CAPI_VALID_ESCIENCE;
			}
		}

		/* don't want to do work twice - if one of these is set, don't match
		 * orgname or CN in PHP any more */
		$organizationVerified = false;
		$cnVerified = false;

		/* the common-name consists only of a wildcard, effectively this is an
		 * organization-wide search */
		if (trim($common_name) == "%") {
			$params = $this->capiGetOrgCertList($org, $days);
			$organizationVerified = true;
			$cnVerified = true;
		/* the common-name consists of two non-adjacent wildcards with a total
		 * length smaller than 7, meaning something like "%jo%". Here it is
		 * probably more efficient to search for the organization name first.
		 */
		} else if (substr_count($common_name, "%") >= 2 &&
		           stripos($common_name, "%%") === false &&
		           strlen($common_name) < 9) {
			$params = $this->capiGetOrgCertList($org, $days);
			$organizationVerified = true;
		/* eppn-ish, expecting fewer results, do a common_name search */
		} else if (stripos($common_name, "@") !== false) {
			$params = $this->capiGetCertList($common_name, $days);
			$cnVerified = true;
		/* longer search string, expecting fewer results if querying for the
		 * common-name first */
		} else {
			$params = $this->capiGetCertList($common_name, $days);
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
	 * query the remote API for certificates matching a certain eppn
	 *
	 * @see CA::getCertListForEPPN
	 */
	public function getCertListForEPPN($eppn, $org)
	{
		/* org-name *must* be set */
		if (empty($org)) {
			return NULL;
		}

		if (Config::get_config('capi_test') === true) {
			$days = ConfusaConstants::$CAPI_TEST_VALID_DAYS;
		} else {
			if (Config::get_config('cert_product') == PRD_PERSONAL) {
				$days = max(ConfusaConstants::$CAPI_VALID_PERSONAL);
			} else {
				$days = ConfusaConstants::$CAPI_VALID_ESCIENCE;
			}
		}

		$params = $this->capiGetEPPNCertList($eppn, $days);
		$organization = "O=" . $org;
		$res = array();

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

			if (strpos($subject, $organization) === FALSE) {
				continue;
			}

			if (!empty($params[$i . '_1_notAfter'])) {
				$valid_untill = $params[$i . '_1_notAfter'];
				$valid_untill = date('Y-m-d H:i:s', $valid_untill);
				$res[$i-1]['valid_untill'] = $valid_untill;
			}

			$res[$i-1]['auth_key'] = $params[$i . '_orderNumber'];
			$res[$i-1]['cert_owner'] = $subject;
		}
		return $res;
	} /* end getCertListForEPPN */
	/**
	 * verifyCredentials() validate username/password to Comodo
	 *
	 * @param String $username
	 * @param String $password
	 * @return Boolean true if username/password was ok
	 */
	function verifyCredentials($username, $password)
	{
		require_once "pw.php";
		require_once "CurlWrapper.php";
		$pf = $this->bs_pf();
		$pf["commonName"]    = "".PW::create(32);
		$data = CurlWrapper::curlContact(ConfusaConstants::$CAPI_LISTING_ENDPOINT, "post", $pf);
		parse_str($data, $params);
		if (array_key_exists('errorCode', $params) && $params['errorCode'] === "0")
			return true;
		return false;
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
			"?loginName="     . $this->account->getLoginName() .
			"&loginPassword=" . $this->account->getPassword(true) .
			"&orderNumber="   . $key .
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
     *
     * @params	key	either an order-number that can be used to retrieve a
     *			certificate directly or an auth-key with which we can
     *			retrieve the order-number
     * @param	key	The order-number or an auth_key that can be transformed
     *			to order_number.
     * @access	public
     * @throws ConfusaGenException
     */
    public function getCert($key)
    {
        $key = $this->transformToOrderNumber($key);

        Logger::log_event(LOG_DEBUG, "Trying to retrieve certificate with order number " .
						  $key .
						  " from the Comodo collect API. " . $this->owner_string);

        $collect_endpoint = ConfusaConstants::$CAPI_COLLECT_ENDPOINT .
			"?loginName="     . $this->account->getLoginName() .
			"&loginPassword=" . $this->account->getPassword(true) .
			"&orderNumber=" . $key .
			"&queryType=2" .
			"&responseMimeType=application/x-x509-user-cert";

		$data = CurlWrapper::curlContact($collect_endpoint);

        $STATUS_PEND="0";
        $STATUS_OK="2";
        /* Parse the status response from the remote API
         */

        $status=substr($data,0,1);
	$cert = false;
        switch($status) {
        case $STATUS_OK:
		$cert = new Certificate(substr($data,2));
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
        return $cert;
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

        Logger::log_event(LOG_NOTICE, "Revoking certificate with order number " .
						  $key ." using Comodo's auto-revoke-API. " . $this->owner_string);

        $revoke_endpoint = ConfusaConstants::$CAPI_REVOKE_ENDPOINT;
        $postfields_revoke = $this->bs_pf();
        $postfields_revoke["revocationReason"] = $reason;
        $postfields_revoke["orderNumber"]      = $key;
        $postfields_revoke["includeInCRL"]     = 'Y';

        /* will not revoke test certificates? */
        if (Config::get_config('capi_test')) {
			Logger::log_event(LOG_DEBUG, "CA_C: in test-mode");
			$postfields_revoke["test"] = 'Y';
        }
		$data = CurlWrapper::curlContact($revoke_endpoint, "post", $postfields_revoke);

        /* try to catch all kinds of errors that can happen when connecting */
        if ($data === FALSE) {
		Logger::log_event(LOG_NOTICE, "[CA_C]: Could not connect to revoke-API. Check configuration.");
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
				CS::deleteSessionKey('rawCertList');
				Logger::log_event(LOG_NOTICE, "Revoked certificate with " .
								  "order number $key using Comodo's AutoRevoke " .
								  "API. " . $this->owner_string);
				return true;
				break;
			default:
				$msg = $this->capiErrorMessage($error_parts[0], $error_parts[1]);
				Logger::log_event(LOG_ERR, "Revocation of certificate with " .
								  "order_number $key failed! ". $this->owner_string);
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
		$postfields_list = $this->bs_pf();
        $postfields_list["orderNumber"]		= $key;
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
				"?loginName=" . $this->account->getLoginName() .
				"&loginPassword=" . $this->account->getPassword(true) .
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
				"?loginName="     . $this->account->getLoginName() .
				"&loginPassword=" . $this->account->getPassword(true) .
				"&orderNumber="   . $key .
				"&queryType=1" .
				"&responseType=2" . /* PKCS#7 */
				"&responseEncoding=2" . /* encode in Javascript */
				"&responseMimeType=text/javascript" .
				/* call that function after the JS variable-declarations */
				"&callbackFunctionName=installIEXPCertificate";

			$data = CurlWrapper::curlContact($collect_endpoint);
            return "<script type=\"text/javascript\">$data</script>";
            break;

        case "chrome":
			 $collect_endpoint = ConfusaConstants::$CAPI_COLLECT_ENDPOINT .
				 "?loginName="     . $this->account->getLoginName() .
				 "&loginPassword=" . $this->account->getPassword(true) .
				 "&orderNumber="   . $key .
				 "&queryType=2" .
				 "&responseType=3" . /* PKCS#7 */
				 "&responseEncoding=0"; /* encode base-64 */

            $data = CurlWrapper::curlContact($collect_endpoint);
            $cert = new Certificate(trim(substr($data, 2)));
            $der_cert = $cert->getDERContent(true);
            return $der_cert;
            break;

        case "mozilla":
        case "safari":
        case "opera":
            $collect_endpoint = ConfusaConstants::$CAPI_COLLECT_ENDPOINT .
			"?loginName="     . $this->account->getLoginName() .
			"&loginPassword=" . $this->account->getPassword(true) .
			"&orderNumber="   . $key .
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

	private function capiGetEPPNCertList($eppn, $days)
	{
		Logger::log_event(LOG_DEBUG, "Trying to get the the list of the certificates " .
		                             "for user $eppn");
		$list_endpoint = ConfusaConstants::$CAPI_LISTING_ENDPOINT;
		$postfields_list = $this->bs_pf();

		if (Config::get_config('cert_product') == PRD_ESCIENCE) {
			/* be sure to match the EPPN only and not also its suffix. For
			 * instance test@feide.no should not match confusatest@feide.no
			 */
			$postfields_list["commonName"] = urlencode("% " . $eppn);
		} else if (Config::get_config('cert_product') == PRD_PERSONAL) {
			$postfields_list["unstructuredName"] = urlencode($eppn);
		} else {
			throw new ConfusaGenException("Cert-Product must be one of PRD_ESCIENCE, " .
			                              "PRD_PERSONAL!");
		}

		$postfields_list["notBefore"]		= time() - $days*24*3600;

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
	} /* end capiGetEPPNCertList */

    /*
     * Query the remote API for the list of certificates belonging to
     * common_name $common_name
     *
     * @param $common_name The common-name for which the list is retrieved
     * @param $days The number of days that search should look "back" in time
     */
    private function capiGetCertList($common_name, $days)
    {
        Logger::log_event(LOG_DEBUG, "Trying to get the list with the certificates " .
                                    "for person $common_name");

        $list_endpoint = ConfusaConstants::$CAPI_LISTING_ENDPOINT;
		$postfields_list = $this->bs_pf();
		$postfields_list["commonName"]		= $common_name;
        $postfields_list["notBefore"]		= time() - $days*24*3600;

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
	 * @param $days Days to look "back" in history in the reporting
	 */
    private function capiGetOrgCertList($organization, $days)
    {
		Logger::log_event(LOG_DEBUG, "Trying to get the list with the certificates " .
							"for organization $organization");

		$listEndpoint = ConfusaConstants::$CAPI_LISTING_ENDPOINT;
		$postfieldsList = $this->bs_pf();
		$postfieldsList["organizationName"]	= $organization;
		$postfieldsList["notBefore"] 		= time() - $days*24*3600;

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
     *
     * @fixme: Update this to use CSR (which reduce the number of arguments to 1)
    */
    private function capiUploadCSR($auth_key, $csr, $csr_format)
    {
        $sign_endpoint = ConfusaConstants::$CAPI_APPLY_ENDPOINT;

		if (Config::get_config('cert_product') == PRD_PERSONAL) {
			$ca_cert_id = ConfusaConstants::$CAPI_PERSONAL_ID;
		} else if (Config::get_config('cert_product') == PRD_ESCIENCE) {
			$ca_cert_id = ConfusaConstants::$CAPI_ESCIENCE_ID;
		} else {
			throw new KeySignException("Confusa's configured product-mode is " .
			                           "illegal! Must be one of: PRD_ESCIENCE, " .
			                           "PRD_PERSONAL. Please contact an IT " .
			                           "administrator about that!");
		}
		$orgName = $this->person->getSubscriber()->getOrgName();

        $postfields_sign_req=array();
		$pf_counter = 1;

        /* set all the required post parameters for upload */
		$postfields_sign_req["ap"] = $this->account->getAPName();
	$postfields_sign_req[$csr_format] = $csr;
	$postfields_sign_req["days"] = $this->validityDays;
	$postfields_sign_req["successURL"] = "none";
	$postfields_sign_req["errorURL"] = "none";
	$postfields_sign_req["caCertificateId"] = $ca_cert_id;

	$cert_email_option = $this->person->getNREN()->getEnableEmail();
	$rce = $this->person->getRegCertEmails();
	$no_cert_error  = "No email for certificate available. " .
		"Need one, cannot continue before this has been selected.";

	switch($cert_email_option) {
	case '0':
		break;
	case '1':
		if (!is_null($rce)) {
			$email = $rce[0];
			/* set the field */
			$postfields_sign_req["subject_rfc822name_".$pf_counter++] = $email;
		} else {
			throw new KeySignException($no_cert_error);
		}
		break;
	case 'm':
		if (is_null($rce)) {
			throw new KeySignException($no_cert_error);
		}
		/*
		 *		---	FALLTHROUGH	---
		 *
		 * to 'n' as we want to add all addresses now that
		 * we know that at least one has been provided by the user. */
	case 'n':
		/* set all, if none set, that is configured by user. */
		if (!is_null($rce)) {
			/* set the fields */
			foreach ($rce as $email) {
				$postfields_sign_req["subject_rfc822name_".$pf_counter++] = $email;
			}
		}
		break;
	default:
		Logger::log_event(LOG_ALERT, "Error in stored value for enable_email. ".
				  "DB-value outside enum-scope. Corrupted table possible.");
		break;
	}

		/* need an unstructured name for personal certificates */
		if (Config::get_config('cert_product') == PRD_PERSONAL) {
			$postfields_sign_req["subject_unstructuredName_".$pf_counter++] =
				$this->person->getEPPN();
		}

		/* manually compose the subject. Necessary, because we want to have
         * Terena domainComponents */
        $postfields_sign_req["subject_commonName_$pf_counter"] =
			stripslashes($this->person->getX509ValidCN());
		$pf_counter++;

        $postfields_sign_req["subject_organizationName_".$pf_counter++] = $orgName;
        $postfields_sign_req["subject_countryName_".$pf_counter++] =
			$this->person->getNREN()->getCountry();

	foreach(array_reverse($this->dcs) as $dc) {
		if ($dc == ConfusaConstants::$CAPI_TEST_DC_PREFIX)
			continue;
		$postfields_sign_req["subject_domainComponent_".$pf_counter++] = $dc;
	}
	if (Config::get_config('capi_test') == true) {
		$postfields_sign_req["subject_domainComponent_".$pf_counter++] = ConfusaConstants::$CAPI_TEST_DC_PREFIX;
	}

	$data = CurlWrapper::curlContact($sign_endpoint, "post", $postfields_sign_req);

        $params=array();
        parse_str($data, $params);
        /*
         * If something has failed, an errorCode parameter will be set in
         * the return message
         *
         * FIXME: add l10n to these messages
         */
        if (isset($params['errorCode'])) {
			$msg = "Received an error when uploading the CSR to the remote CA: ";
		if (isset($params['errorMessage'])) {
			$msg .= " " . $params['errorMessage'];
		}
		if (isset($params['errorItem'])) {
			$msg .= " " . $params['errorItem'];
		}
		$msg .= $this->capiErrorMessage($params['errorCode'], $params['errorMessage']);
		throw new CGE_ComodoAPIException($msg);
        }

        else {

            if (!isset($params['orderNumber'])) {
                $msg = "Response looks malformed. Maybe there is a configuration " .
                       "error in Confusa's Comodo-CA configuration!";
                throw new CGE_ComodoAPIException($msg);
            }

            $this->order_number = $params['orderNumber'];

			Logger::log_event(LOG_INFO, "Successfully uploaded CSR to remote CA, got ".
							  $this->order_number . $this->owner_string);

          $sql_command= "INSERT INTO order_store(auth_key, owner, " .
                        "order_number, order_date, authorized)" .
                        "VALUES(?, ?, ?, now(),'unauthorized')";

          MDB2Wrapper::update($sql_command,
                            array('text', 'text', 'text'),
                            array($auth_key, $this->person->getX509ValidCN(),
                            $this->order_number));
        }
    } /* end capiUploadCSR */

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
			$msg .= "create your certificate requests with a keysize of " . Config::get_config('default_key_length');
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
		$postfields_auth =$this->bs_pf();
        $postfields_auth["orderNumber"] = $this->order_number;
		$data = CurlWrapper::curlContact($authorize_endpoint, "post", $postfields_auth);

        /* the only formal restriction we have is if the API returns 0 for the query */
        if (substr($data,0,1) == "0") {
          /* update the database-entry to reflect the autorization-state */
          MDB2Wrapper::update("UPDATE order_store SET authorized='authorized' WHERE order_number=?",
                              array('text'),
                              array($this->order_number));
          Logger::log_event(LOG_NOTICE, "Authorized certificate with order number " .
							$this->order_number . ". " . $this->owner_string);
        } else {
			Logger::log_event(LOG_WARNING,
							  "Error authorizing CSR ".$this->order_number. " ".
							  "Server said " . $error_parts[0] ." (".$error_parts[1].")";)
            $msg = "Received an error when authorizing the CSR with orderNumber " .
                   $this->order_number . $data . "\n";
				   $error_parts = explode("\n", $data, 2);
			$msg .= $this->capiErrorMessage($error_parts[0], $error_parts[1]);
            throw new CGE_ComodoAPIException($msg);
        }
    } /* end capiAuthorizeCSR */

	/**
	 * bs_pf() Bootstrap Postfields
	 */
	private function bs_pf()
	{
        $pf                  = array();
        $pf["loginName"]     = $this->account->getLoginName();
		/* FIXME: use true as argument, password *should* be urlencoded! (But
		 * comodo will bomb */
        $pf["loginPassword"] = $this->account->getPassword(false);
		return $pf;
	}
} /* end class CA_Comodo */
?>
