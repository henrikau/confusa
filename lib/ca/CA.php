<?php
  /* CA
   *
   * Class for signing certificates, verifying CSRs and storing it in the database
   *
   * Author: Henrik Austad <henrik.austad@uninett.no>
   */
require_once 'MDB2Wrapper.php';
require_once 'MailManager.php';
require_once 'Certificate.php';
require_once 'Logger.php';
require_once 'Config.php';
require_once 'CSR.php';

abstract class CA
{
  protected $person;
  /* the number of days that the certificate issued by the CA will be valid */
  protected $validityDays;
  /* domain components that will be added to the certificate subjects */
  protected $dcs;

  /*
   * Should register all values so that when a sign_key request is issued,
   * all values are in place.
   *
   * @param pers: object describing the person and his/hers attributes.
   */
  function __construct($pers, $validity)
    {
	    if (!isset($pers) || !($pers instanceof Person)) {
		    echo __FILE__ . " Cannot function without a person!<BR>\n";
		    exit(0);
	    }
	    $this->person = $pers;
		$this->validityDays = $validity;
		$this->dcs = array();

    } /* end __construct */

  /* this function is quite critical, as it must remove residual information
   * about the certificate and it's owner from the server
   */
  function __destruct()
    {
      unset($this->person);
    } /* end destructor */


  /**
   * signKey()
   *
   * This is the signing routine of the system. In this release, it will use PHP
   * for signing, using a local CA-key.
   *
   * In the future, it will sign the CSR and ship it to the CA, receive the
   * response and notify the user
   *
   * @param	CSR to sign
   * @return	void
   */
  abstract function signKey($csr);

  /**
   * Get the (browser-specific) deployment script for a certain certificate
   * Usually this should return some JavaScript that will call installCertificate()
   */
  abstract function getCertDeploymentScript($key, $browser);
  /*
   * Get a list of all the certificates issued for the managed person.
   * Note that the returned list is implementation dependant.
   * For instance if Confusa is set to standalone-mode, the function will not return
   * remote signed certificates.
   */
  abstract function getCertList();


  abstract function pollCertStatus($key);
  /*
   * Return the certificate associated to key $key
   *
   * @param key An identifier mapping to a certificate, dependant on the implementation.
   */
  abstract function getCert($key);

  /*
   * Return owner and organisation belonging to the certificate with key $key
   *
   * @param key An identifier mapping to a certificate, dependant on the implementation.
   */
  abstract function getCertInformation($key);

  /** deleteCertFromDB()
   *
   * @key : the unique identifier for the certififcate in the database
   */
  abstract function deleteCertFromDB($key);

   /*
   * Revoke a certificate associated to key
   *
   * @param key An identifier mapping to a certificate, dependant on the implementation.
   * @param reason The reason for revocation, as specified in RFC 5280
   */
  abstract function revokeCert($key, $reason);

  /**
   * getCertListForPersons() get all valid certificates for a given user
   *
   * Search for the certificates of a person with a given common_name.
   * Common_name may include wildcard characters.
   *
   * Restrict the result set to organization $org.
   *
   * @param $common_name The common_name to search for
   * @param $org The organization to restrict the search to
   */
  abstract function getCertListForPersons($common_name, $org);

 /**
  * getCertListForEPPN - get all valid certificates for a eppn (scoped to
  * an organization)
  *
  * Search for the certificates containing a certain eppn. This will
  * - query for wildcard-prefixed eppn in common-name field in eScience mode
  * - query for the eppn in the unstructuredName field in personal mode
  *
  * @param string $eppn The EPPN of the subject which is queried for.
  * @param string $org The name of the organization to which the query is
  *                    constrained
  * $return array(auth_key, subject, valid_until) list with meta-information
  *         about the certificates
  */
  abstract function getCertListForEPPN($eppn, $org);

  /**
   * verifyCredentials() test to see if username/password is valid
   *
   * @param String $username
   * @param String $password
   * @return Boolean true if credentials are valid
   */
  abstract function verifyCredentials($username, $password);

  /**
   * Send a notification upon the issuance of a new X.509 certificate, as it is
   * required in section 3.2 of the MICS-profile.
   *
   * @param $orderNumber The unique identifier of the certificate - good to supply
   * that to the user
   * @param $timestamp Also include the time at which the certificate was signed
   * @param $ip And the IP-address of the contacting endpoint
   * @param $productName string the name of the certificate (eScience, personal,
   *                            code-signing) that we actually issued
   * @param $recipient person the person that is going to receive the
   *                          notification mail
   * @param $custom_content String|null the custom template to use when sending
   *					 the mail. If set, this will be used
   *					 instead of the default (from Config)
   */
	public static function sendMailNotification($orderNumber,
	                                            $timestamp,
	                                            $ip,
	                                            $recipient,
												$distName,
						    $custom_content = null)
	{
	/* if a notification e-mail is not a *template*, then what is? */
	$tpl	= new Smarty();
	$nren = $recipient->getNREN();

	if (Config::get_config('cert_product') == PRD_ESCIENCE) {
		$productName = ConfusaConstants::$ESCIENCE_PRODUCT;
	} else {
		$productName = ConfusaConstants::$PERSONAL_PRODUCT;
	}

	$custom_template = Config::get_config('custom_mail_tpl') . $nren . '/custom.tpl';
	$tpl->compile_dir	= ConfusaConstants::$SMARTY_TEMPLATES_C;
	$tpl->config_dir	= Config::get_config('install_path') .
	                          'lib/smarty/configs';
	$tpl->cache_dir	= ConfusaConstants::$SMARTY_CACHE;
	$subscriber = $recipient->getSubscriber()->getOrgName();
	$support_mail = $recipient->getSubscriber()->getHelpEmail();
	$help_url = $recipient->getSubscriber()->getHelpURL();
	$dn = $distName;
	$download_url = Config::get_config('server_url') .
	                '/download_certificate.php';

	if (isset($custom_content)) {
		$msg = $custom_content;
		$msg = str_ireplace('{$subscriber}', $subscriber, $msg);
		$msg = str_ireplace('{$subscriber_support_email}', $support_mail, $msg);
		$msg = str_ireplace('{$subscriber_support_url}', $help_url, $msg);
		$msg = str_ireplace('{$confusa_url}', Config::get_config('server_url'),
		                    $msg);
		$msg = str_ireplace('{$dn}', $dn, $msg);
		$msg = str_ireplace('{$download_url}', $download_url, $msg);
		$msg = str_ireplace('{$issue_date}', $timestamp, $msg);
		$msg = str_ireplace('{$ip_address}', $ip, $msg);
		$msg = str_ireplace('{$order_number}', $orderNumber, $msg);
		$msg = str_ireplace('{$product_name}', $productName, $msg);
		$msg = str_ireplace('{$nren}', $nren, $msg);
	} else {
		$tpl->assign('subscriber', $subscriber);
		$tpl->assign('subscriber_support_email', $support_mail);
		$tpl->assign('subscriber_support_url', $help_url);
		$tpl->assign('confusa_url', Config::get_config('server_url'));
		$tpl->assign('dn', $dn);
		$tpl->assign('download_url', $download_url);
		$tpl->assign('issue_date', $timestamp);
		$tpl->assign('ip_address', $ip);
		$tpl->assign('order_number', $orderNumber);
		$tpl->assign('nren', $nren);
		$tpl->assign('product_name', $productName);

		if (file_exists($custom_template)) {
			$msg = $tpl->fetch($custom_template);
		} else {
			$default_template = Config::get_config('install_path') .
				ConfusaConstants::$SMARTY_TEMPLATES . 'email/notification.tpl';
			$msg = $tpl->fetch($default_template);
		}
	}
	$subject = "Your new $productName certificate is ready.";

	/* send notification, test to see if it is *one* address, or multiple */
	$rce = $recipient->getRegCertEmails();

	if (empty($rce)) {
		/* fallback to standard mail address to be used in any case
		 * (in case that session is not set) */
		$rce = array();
		$rce[] = $recipient->getEmail();
	}

	switch ($recipient->getNREN()->getEnableEmail()) {
	case '1':
		$mm = new MailManager($recipient,
				      Config::get_config('sys_from_address'),
				      Config::get_config('system_name'),
				      Config::get_config('sys_header_from_address'),
				      $rce[0]);
		$mm->setSubject($subject);
		$mm->setBody($msg);
		$mm->sendMail();
		break;
	case 'n':
		if (isset($rce) && count($rce) > 0) {
			foreach ($rce as $email) {
				$mm = new MailManager($recipient,
						      Config::get_config('sys_from_address'),
						      Config::get_config('system_name'),
						      Config::get_config('sys_header_from_address'),
						      $email);
				$mm->setSubject($subject);
				$mm->setBody($msg);
				$mm->sendMail();
			}
			return;
		}
		/* if we don't have an email set, fall-through to standard,
		 * i.e. send notification to default address. */
	default:
		$mm = new MailManager($recipient,
				      Config::get_config('sys_from_address'),
				      Config::get_config('system_name'),
				      Config::get_config('sys_header_from_address'));
		$mm->setSubject($subject);
		$mm->setBody($msg);
		$mm->sendMail();
	break;
	}
  } /* end sendMailNotification */

  /**
   * get the final DN for the person associated to this CA.
   * The DCs are dependant on the actual instance of the CA, while all other
   * fields can be decorated through the person object associated with this
   * CA.
   *
   * @return string the full DN as it will look like in the certificate DN
   */
  public function getFullDN()
  {
		$dn = "";
		foreach ($this->dcs as $dc) {
			$dn .= "/DC=$dc";
		}

		$nren = $this->person->getNREN();
		$subscriber = $this->person->getSubscriber();

		if (isset($nren)) {
			$dn .= "/C=" . $this->person->getNREN()->getCountry();
		}

		if (isset($subscriber)) {
			$dn .= "/O=" . $this->person->getSubscriber()->getOrgName();
		}
		$dn .= "/CN=" . $this->person->getX509ValidCN();
		if (Config::get_config('cert_product') == PRD_PERSONAL) {
			$dn .= "/unstructuredName=" . $this->person->getEPPN();
		}
		return $dn;
  } /* end getFulLDN */

	/**
	 * Return the DN of the person, but in a more "browser-friendly" format,
	 * i.e. separated by commas in the form of C=SE, O=EvilMasterminds, CN= Dr. Evil
	 * instead of /C=SE/O=EvilMastermindes/CN=Dr. Evil
	 *
	 * This is needed for in-browser request signing
	 * @return string the DN in comma-separated format
	 */
  public function getBrowserFriendlyDN()
  {
		$dn = "";
		foreach ($this->dcs as $dc) {
			$dn .= "DC=$dc, ";
		}

		$dn .= "C=" . $this->person->getNREN()->getCountry() . ", ";

		$dn .= "O=" . $this->person->getSubscriber()->getOrgName() . ", ";
		$dn .= "CN=" . stripslashes($this->person->getX509ValidCN());
		if (Config::get_config('cert_product') == PRD_PERSONAL) {
			$dn .= "/unstructuredName=" . $this->person->getEPPN();
		}
		return $dn;
	}


} /* end class CA */

class CAHandler
{
	private static $ca;
	public static function getCA($person)
	{
		if (!isset(CAHandler::$ca)) {
			if (Config::get_config('cert_product') == PRD_PERSONAL) {
				/* if no NREN object is set, we can not know anything about the days */
				if (!is_object($person->getNREN())) {
					$days = min(ConfusaConstants::$CAPI_VALID_PERSONAL);
				} else {
					$days = $person->getNREN()->getCertValidity();
				}
			} else if (Config::get_config('cert_product') == PRD_ESCIENCE) {
				$days = ConfusaConstants::$CAPI_VALID_ESCIENCE;
			} else {
				throw new ConfusaGenException("Confusa's configured product-mode is " .
				                              "illegal! Must be one of: PRD_ESCIENCE, " .
				                              "PRD_PERSONAL. Please contact an IT " .
				                              "administrator about that!");
			}

			switch((int)Config::get_config('ca_mode')) {

			case CA_STANDALONE:
				require_once 'CA_Standalone.php';
				CAHandler::$ca = new CA_Standalone($person, $days);
				break;

			case CA_COMODO:
				require_once 'CA_Comodo.php';

				if (Config::get_config('capi_test') == TRUE) {
					$days = ConfusaConstants::$CAPI_TEST_VALID_DAYS;
				}

				CAHandler::$ca = new CA_Comodo($person, $days);
				break;

			default:
				/* This is going to produce a *lot* of errors,
				 * but it should also catch the attention of
				 * the operators. */
				Logger::log_event(LOG_ALERT, "Tried to instantiate CA " .
						  Config::get_config('ca_mode') .
						  " but this is unknow. Config-file has errors. Check the ca_mode.");
				return null;
			}
		}
	        return CAHandler::$ca;
	}
}
?>
