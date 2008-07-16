<?php
require_once('mail_manager.php');
require_once('sql_lib.php');
require_once('pw.php');
require_once('logger.php');
class CertManager
{
  private $person;
  private $user_csr;
  private $pubkey_checksum;
  private $valid_csr;
  private $user_cert;
  /* constructor:
   *
   * Should register all values so that when a create-cert request is issued,
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
   * Will invoke the system shell-script for parsing and signing the
   * script. The script will return the signed csr if successful
   */
  function sign_key($auth_key)
    {
         if ($this->verify_csr()) {
                 $sign_days = 11;
                 $cert_path = 'file://'.dirname(WEB_DIR) . '/cert_handle/cert/sigma_cert.pem';
                 $ca_priv_path = 'file://'.dirname(WEB_DIR) . '/cert_handle/priv/sigma_priv_key.pem';

                 $tmp_cert = openssl_csr_sign($this->user_csr, $cert_path, $ca_priv_path, $sign_days);
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
                    echo "auth_key: " . $auth_key . "<br>\n";
                    $query  = "INSERT INTO cert_cache (cert, auth_key, cert_owner, valid_untill) ";
                    $query .= "VALUES('".$this->user_cert."',";
                    $query .= "'".$auth_key."','".$this->person->get_common_name() . "'";
                    $query .= ", addtime(current_timestamp(), '" . $sign_days . " 0:0'))";
                    /* echo $query ."<br>\n"; */

                    $sql = get_sql_conn();
                    $sql->update($query);

		    Logger::log_event(LOG_INFO, "Certificate successfully signed for " . $this->person->get_common_name());
		    /* add to database (the hash of the pubkey) */
                    $update = "INSERT INTO pubkeys (pubkey_hash) VALUES('" . $this->pubkey_checksum . "')";
                    $sql = get_sql_conn();
                    $sql->update($update);

		    return true;
	    }
	    else {
		    Logger::log_event(LOG_INFO, "Will not sign invalid CSR for user " . $this->person->get_common_name());
	    }
      return false;
    } /* end sign_key() */



  /* strictly speaking, its a private procedure . :-) */
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
