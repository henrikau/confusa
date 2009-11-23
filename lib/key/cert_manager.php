<?php
  /* Certmanager
   *
   * Class for signing certificates, verifying CSRs and storing it in the database
   *
   * Author: Henrik Austad <henrik.austad@uninett.no>
   */
require_once 'mdb2_wrapper.php';
require_once 'logger.php';
require_once 'csr_lib.php';
require_once 'config.php';
require_once 'mail_manager.php';

abstract class CertManager
{
  protected $person;

  /*
   * Should register all values so that when a sign_key request is issued,
   * all values are in place.
   *
   * @param pers: object describing the person and his/hers attributes.
   */
  function __construct($pers)
    {
	    if (!isset($pers) || !($pers instanceof Person)) {
		    echo __FILE__ . " Cannot function without a person!<BR>\n";
		    exit(0);
	    }
	    $this->person = $pers;
    } /* end __construct */

  /* this function is quite critical, as it must remove residual information
   * about the certificate and it's owner from the server
   */
  function __destruct()
    {
      unset($this->person);
    } /* end destructor */


  /* sign_key()
   *
   * This is the signing routine of the system. In this release, it will use PHP
   * for signing, using a local CA-key.
   *
   * In the future, it will sign the CSR and ship it to the CA, receive the
   * response and notify the user
   *
   */
  abstract function sign_key($auth_key, $csr);

   /**
    * Sign a CSR as received from the browser's crypto mechanisms.
    * Since these cert requests are not always in PKCS#10 format, handle them
    * specifically to the browser from which they originate
    */
  abstract function signBrowserCSR($csr, $browser);

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
  abstract function get_cert_list();


  abstract function pollCertStatus($key);
  /*
   * Return the certificate associated to key $key
   *
   * @param key An identifier mapping to a certificate, dependant on the implementation.
   */
  abstract function get_cert($key);

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
  abstract function revoke_cert($key, $reason);

  /**
   * get_cert_list_for_persons() get all valid certificates for a given user
   *
   * Search for the certificates of a person with a given common_name.
   * Common_name may include wildcard characters.
   *
   * Restrict the result set to organization $org.
   *
   * @param $common_name The common_name to search for
   * @param $org The organization to restrict the search to
   */
  abstract function get_cert_list_for_persons($common_name, $org);
  /*
   * If the person has been changed in the framework or elsewhere, it can be updated here
   */
  public function update_person($pers) {
    $this->person = $pers;
  }

  /**
   * Convert from DER certificates to PEM certificates.
   * This is needed because TERENA/Comodo publish their CA-certificate in
   * DER format, while PHP's openssl can only process PEM formatted certs
   *
   * @param $der the certificate in DER format
   * @param $type the type of certificate. One of:
   * 				* 'cert' - a X509 certificate
   * 				* 'crl' - a certificate revocation list
   * @return $pem the certificate in PEM format
   */
  public static function DERtoPEM($der, $type)
  {
    $header = "";
    $trailer = "";

    switch($type) {
    case 'cert':
      $header = "-----BEGIN CERTIFICATE-----\n";
      $trailer = "-----END CERTIFICATE-----\n";
      break;
    case 'crl':
      $header = "-----BEGIN X509 CRL-----\n";
      $trailer = "-----END X509 CRL-----\n";
      break;
    default:
      /* nothing we can do for the caller */
      return "Transcoding from DER to PEM failed!";
    }

    $pem = chunk_split(base64_encode($der), 64, "\n");
    $pem =  $header . $pem . $trailer;

    return $pem;
  }

  /**
   * Convert a certificate from PEM format to DER format
   *
   * @param $pem string the certificate in PEM format
   * @param $type string the type of the certificate. One of:
   * 			* 'cert'  - a X509 certificate
   * 			* 'crl'   - a certificate revocation list
   * @return $der string the certificate in DER format
   */
  public static function PEMtoDER($pem, $type)
  {
		switch($type) {
		case 'cert':
			$begin = "CERTIFICATE-----";
			$end = "-----END";
			$pem = substr($pem, strpos($pem, $begin)+strlen($begin));
			$pem = substr($pem, 0, strpos($pem, $end));
			$der = base64_decode($pem);
			return $der;
			break;
		case 'crl':
			$begin = "CRL-----";
			$end = "-----END";
			$pem = substr($pem, strpos($pem, $begin)+strlen($begin));
			$pem = substr($pem, 0, strpos($pem, $end));
			$der = base64_decode($pem);
			return $der;
			break;
		}
	}

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
   */
	protected function sendMailNotification($orderNumber,
	                                        $timestamp,
	                                        $ip,
	                                        $productName)
	{
	/* if a notification e-mail is not a *template*, then what is? */
	$this->tpl	= new Smarty();
	$nren = $this->person->getNREN();
	$custom_template = Config::get_config('custom_mail_tpl') . $nren . '/custom.tpl';

	$this->tpl->compile_dir	= ConfusaConstants::$SMARTY_TEMPLATES_C;
	$this->tpl->config_dir	= Config::get_config('install_path') .
	                          'lib/smarty/configs';
	$this->tpl->cache_dir	= ConfusaConstants::$SMARTY_CACHE;
	$this->tpl->assign('subscriber',
	                   $this->person->getSubscriber()->getOrgName());
	$this->tpl->assign('confusa_url', Config::get_config('server_url'));
	$this->tpl->assign('dn', $this->person->getX509SubjectDN());
	$this->tpl->assign('download_url', Config::get_config('server_url') .
	                                   '/download_certificate.php');
	$this->tpl->assign('issue_date', $timestamp);
	$this->tpl->assign('ip_address', $ip);
	$this->tpl->assign('order_number', $orderNumber);
	$this->tpl->assign('nren', $this->person->getNREN());

	if (file_exists($custom_template)) {
		$msg = $this->tpl->fetch($custom_template);
		/* the mail will go out 7bit */
		$msg = utf8_decode($msg);
	} else {
		$default_template = Config::get_config('install_path') .
		                    '/lib/smarty/templates/email/notification.tpl';
		$msg = $this->tpl->fetch($default_template);
	}

	$subject = "Your new $productName certificate is ready.  Order number " .
	           "$orderNumber, subject " . $this->person->getX509SubjectDN();

	$mm = new MailManager($this->person,
	                      Config::get_config('sys_from_address'),
	                      '"' . Config::get_config('system_name') . '"',
	                      Config::get_config('sys_header_from_address'));
	$mm->setSubject($subject);
	$mm->setBody($msg);
	$mm->sendMail();
  } /* end sendMailNotification */
} /* end class CertManager */

class CertManagerHandler
{
	private static $cert_manager;
	public static function getManager($person)
	{
		if (!isset(CertManagerHandler::$cert_manager)) {
			switch((int)Config::get_config('ca_mode')) {

			case CA_STANDALONE:
				require_once 'cert_manager_standalone.php';
				CertManagerHandler::$cert_manager = new CertManager_Standalone($person);
				break;

			case CA_ONLINE:
				require_once 'cert_manager_online.php';
				CertManagerHandler::$cert_manager = new CertManager_Online($person);
				break;

			default:

			}
		}
	        return CertManagerHandler::$cert_manager;
	}
}
?>
