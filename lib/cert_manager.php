<?php
require_once('mail_manager.php');
require_once('mdb2_wrapper.php');
require_once('pw.php');
require_once('logger.php');
class CertManager
{
  private $person;
  private $user_csr;
  private $pubkey_checksum;
  private $valid_csr;
  private $user_cert;

  /* 
   * Should register all values so that when a sign_key request is issued,
   * all values are in place. 
   */
  function __construct($csr, $pers)
    {
	    if (!isset($pers) || !($pers instanceof Person)) {
		    echo __FILE__ . " Cannot function without a person!<BR>\n";
		    exit(0);
	    }
	    $this->person = $pers;
	    $this->user_csr = $csr;
      
	    /* read public key and create sum */
	    $this->pubkey_checksum=trim(shell_exec("exec echo \"".$csr."\" | openssl req -pubkey -noout | sha1sum | cut -d ' ' -f 1"));

    } /* end __construct */

  /* this function is quite critical, as it must remove residual information
   * about the certificate and it's owner from the server
   */
  function __destruct()
    {
      unset($this->person);
      unset($this->valid_csr);
      unset($this->user_cert);
    } /* end destructor */

  /* sign_key()
   *
   * This is the signing routine of the system. In this release, it will use PHP
   * for signing, using a local CA-key.
   */
  function sign_key($auth_key)
    {
         if ($this->verify_csr()) {
                 $sign_days = 11;
                 $cert_path = 'file://'.dirname(WEB_DIR) . '/cert_handle/cert/sigma_cert.pem';
                 $ca_priv_path = 'file://'.dirname(WEB_DIR) . '/cert_handle/priv/sigma_priv_key.pem';

                 $tmp_cert = openssl_csr_sign($this->user_csr, $cert_path, $ca_priv_path, $sign_days , array('digest_alg' => 'sha1'));
                 openssl_x509_export($tmp_cert, $this->user_cert, true);
                 /* echo __FILE__ .":".__LINE__ ." Certificate successfully signed. <BR>\n"; */
                 

                    /* store cert in database */
                    /* 
                       mysql> desc cert_cache;
                       +--------------+-------------+------+-----+---------+----------------+
                       | Field        | Type        | Null | Key | Default | Extra          |
                       +--------------+-------------+------+-----+---------+----------------+
                       | cert_id      | int(11)     | NO   | PRI | NULL    | auto_increment |
                       | cert         | text        | NO   |     |         |                |
                       | auth_key     | varchar(64) | NO   |     |         |                |
                       | cert_owner   | varchar(64) | NO   |     |         |                |
                       | valid_untill | datetime    | NO   |     |         |                |
                       +--------------+-------------+------+-----+---------+----------------+
                    */
                    MDB2Wrapper::update("INSERT INTO cert_cache (cert, auth_key, cert_owner, valid_untill) VALUES(?, ?, ?, addtime(current_timestamp(), ?))",
                                        array('text', 'text', 'text', 'text'),
                                        array($this->user_cert, $auth_key, $this->person->get_common_name(), Config::get_config('cert_default_timeout')));
		    Logger::log_event(LOG_INFO, "Certificate successfully signed for " . $this->person->get_common_name() . " Contacting us from " . $_SERVER['REMOTE_ADDR']);
		    /* add to database (the hash of the pubkey) */
                    MDB2Wrapper::update("INSERT INTO pubkeys (pubkey_hash) VALUES(?)",
                                        array('text'),
                                        array($this->pubkey_checksum));
		    return true;
	    }
	    else {
		    Logger::log_event(LOG_INFO, "Will not sign invalid CSR for user " . $this->person->get_common_name() . " from ip " . $_SERVER['REMOTE_ADDR']);
	    }
      return false;
    } /* end sign_key() */



  /* verify_csr()
   *
   * This function will test the CSR against several fields.
   * It will test the subject against the person-attributes (which in turn are
   * gathered from simplesamlphp-attributes (Feide, surfnet etc).
   */
  private function verify_csr()
  {
       /* by default, the CSR is valid */
	  $this->valid_csr = true;
	  if (!isset($this->user_csr)) {
               echo __FILE__ . ":" . __LINE__ . " CSR not set in cert-manager<BR>\n";
		  $this->valid_csr = false;
	  }
	  else {
               $subject= openssl_csr_get_subject($this->user_csr);
               /* check fields of CSR to predefined values and user-specific values
                * TODO: Fix these fields to read from config and country to be
                * federate-dependent.
                */
               if (!($subject['C'] === "NO" &&
                     $subject['O'] === "Nordugrid" &&
                     $subject['OU'] === "Nordugrid" &&
                     $subject['CN'] === $this->person->get_common_name() &&
                     $subject['emailAddress'] === $this->person->get_email())) {
                    echo "Error in subject! <BR/>\n";
                    echo "The fields in your CSR was not set properly.<BR>\n";
                    echo "To try again, please download a new version of the script, ";
                    echo "generate a new key and upload again.<BR>\n";
                    print_r($subject);
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
} /* end class CertManager */

?>
