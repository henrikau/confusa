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

  /*
   * Get a list of all the certificates issued for the managed person.
   * Note that the returned list is implementation dependant.
   * For instance if Confusa is set to standalone-mode, the function will not return
   * remote signed certificates.
   */
  abstract function get_cert_list();

  /*
   * Return the certificate associated to key $key
   *
   * @param key An identifier mapping to a certificate, dependant on the implementation.
   */
  abstract function get_cert($key);

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

  abstract function get_cert_list_for_persons($common_name, $org);
  /*
   * If the person has been changed in the framework or elsewhere, it can be updated here
   */
  public function update_person($pers) {
    $this->person = $pers;
  }

  /**
   * Send a notification upon the issuance of a new X.509 certificate, as it is
   * required in section 3.2 of the MICS-profile.
   *
   * @param $auth_key The unique identifier of the certificate - good to supply
   * that to the user
   * @param $timestamp Also include the time at which the certificate was signed
   * @param $ip And the IP-address of the contacting endpoint
   */
  protected function sendMailNotification($auth_key, $timestamp, $ip)
  {
    $subject = "Issued a new certificate for " . $this->person->getX509SubjectDN();
    $msg = "A new X.509 certificate was just issued to *you*, with subject-DN " . $this->person->getX509SubjectDN() . ".\n\n";
    $msg .= "The certificate was signed using the Confusa portal (";
    $msg .= Config::get_config('server_url') . "), at date " . $timestamp . ".\n";
    $msg .= "The IP address of the contacting user agent was $ip. ";
    $msg .= "Probably this happened because you requested a certificate by Confusa. If this is not the case, ";
    $msg .= "please notify " . Config::get_config('sys_from_address') . " or an admin contact in your local ";
    $msg .= "institution!\n\n";
    $msg .= "The unique identifier of your certificate is: " . $auth_key . ".\n";
    $msg .= "\nBest regards,\n";
    $msg .= "   The Confusa team\n";
    $mm = new MailManager($this->person,
			  Config::get_config('sys_from_address'),
			  $subject,
			  $msg);
    $mm->send_mail();
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
			      CertManagerHandler::$cert_manager = new CertManager_Standalone($person);
			      break;

			case CA_ONLINE:
			      CertManagerHandler::$cert_manager = new CertManager_Online($person);
			      break;

			default:

			}
		}
	        return CertManagerHandler::$cert_manager;
	}
}
?>
