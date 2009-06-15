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
   * If the person has been changed in the framework or elsewhere, it can be updated here
   */
  public function update_person($pers) {
    $this->person = $pers;
  }

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
