
<?php
require_once('mdb2_wrapper.php');
require_once('logger.php');
require_once('csr_lib.php');

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
	    $this->pubkey_checksum=pubkey_hash($this->user_csr);

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
   *
   * In the future, it will sign the CSR and ship it to the CA, receive the
   * response and notify the user
   */
  function sign_key($auth_key)
    {
         if ($this->verify_csr()) {
              $cert_path = 'file://'.dirname(WEB_DIR) . '/cert_handle/cert/sigma_cert.pem';
              $ca_priv_path = 'file://'.dirname(WEB_DIR) . '/cert_handle/priv/sigma_priv_key.pem';

              if (Config::get_config('standalone')) {
                 $sign_days = 11;
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
              /* external CA */
              else {
                   Logger::log_event(LOG_DEBUG, "Signing key using remote CA");
                   $ca_addr = Config::get_config('ca_host');
                   $ca_port = Config::get_config('ca_port');

                   if (openssl_sign($this->user_csr, $signed_pkcs10, $ca_priv_path)) {
                        $tmp = chunk_split(base64_encode($signed_pkcs10));
                        Print "<pre>\n$tmp\n</pre>\n";
                   }
                   return false;
              }
         }
         Logger::log_event(LOG_INFO, "Will not sign invalid CSR for user " . $this->person->get_common_name() . " from ip " . $_SERVER['REMOTE_ADDR']);
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
               else if (!($subject['C'] === $this->person->get_country() &&
                          $subject['O'] === "Nordugrid" &&
                          $subject['OU'] === "Nordugrid" &&
                          $subject['CN'] === $this->person->get_common_name())) {
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
} /* end class CertManager */

?>
