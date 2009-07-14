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

   /*
   * Revoke a certificate associated to key
   *
   * @param key An identifier mapping to a certificate, dependant on the implementation.
   * @param reason The reason for revocation, as specified in RFC 5280
   */
  abstract function revoke_cert($key, $reason);

  /*
   * If the person has been changed in the framework or elsewhere, it can be updated here
   */
  public function update_person($pers) {
    $this->person = $pers;
  }

  /**
   * Look if the person managed by this cert_manager already exists. If the
   * person exists, look if she expires before $expires.
   *
   * - If the person expires before $expires, update her expiry to $expires.
   * - If the person does not exist yet, add her with expiry $expires.
   * - Otherwise, leave the entry in cert_user untouched.
   *
   * @param integer $expires The expiry of the managed person in the
   *                     cert_user table
   * @param string $time_unit one of: SECOND, MINUTE, HOUR, DAY, WEEK, MONTH, YEAR
   */
  public function touch_cert_user($expires, $time_unit)
  {
      $date_string = "INTERVAL $expires $time_unit";
      $common_name = $this->person->get_valid_cn();
      $institution = $this->person->get_orgname();

      $query = "SELECT * FROM cert_user WHERE common_name = ? AND expires > " .
               "date_add(now(),$date_string)";
      $res = MDB2Wrapper::execute($query, array('text'),
                                  array($common_name)
      );

      if (count($res) == 0) {
          Logger::log_event(LOG_INFO, "Adding a new cert_user with common_name " .
                            "$common_name into the cert_user table"
          );

          $update = "INSERT INTO cert_user(common_name, institution, expires) " .
                    "VALUES(?, ?, date_add(now(),$date_string)) ON DUPLICATE KEY " .
                    "UPDATE expires=date_add(now(),$date_string)";
          MDB2Wrapper::update($update, array('text','text'),
                                       array($common_name, $institution)
          );
      }
  }

} /* end class CertManager */

class CertManagerHandler
{
	private static $cert_manager;
	public static function getManager($person)
	{
		if (!isset(CertManagerHandler::$cert_manager)) {
		      if (Config::get_config('standalone')) {
			      if (Config::get_config('debug'))
				  echo "Creating Standalone CertManager<BR>\n";
			      CertManagerHandler::$cert_manager = new CertManager_Standalone($person);
		      } else {
			      if (Config::get_config('debug'))
				  echo "Creating Online CertManager<BR>\n";
			      CertManagerHandler::$cert_manager = new CertManager_Online($person);
		      }
		}
	        return CertManagerHandler::$cert_manager;
	}
}
?>
