<?php
  /* csr_lib.php
   *
   * Small library with common functions to verify contents of CSRs, compare to
   * config-parameters as well as person-attributes.
   *
   * Author: Henrik Austad <henrik.austad@uninett.no>
   */
include_once('mdb2_wrapper.php');
include_once('logger.php');

/* test_content()
 *
 * This function is to be used when testing uploaded CSRs for flaws and errors.
 * It will test for:
 * - common text-patterns
 * - that the key meets the required key-length
 * - that it is a normal CSR (previous point will fail if it is a 'bogus' CSR
 * - that the auth_url is derived from the supplied CSR
 * - that the public-key in the CSR does not belong to a previously signed certificate
 */
function test_content($content, $auth_url)
{
  global $person;
  $testres = true;
  /* check for start */
  $start = substr($content, 0, 35);
  $end = substr($content, -34, -1);

  /* test start and ending of certificate */
  if (strcmp("-----BEGIN CERTIFICATE REQUEST-----", $start)!==0 &&
      strcmp("-----END CERTIFICATE REQUEST-----", $end) !== 0) {
	  echo "malformed CSR. Please upload a proper CSR to the system <BR>\n";
       return false;
  }
  
  /* test type. IGTF will soon change the charter to *not* issue DSA
   * certificates */
  if (get_algorithm($content) !== "rsa") {
	  echo "Will only accept RSA keys!<BR>\n";
	  return false;
  }
  /*
   * test length of pubkey
   */
  $length = Config::get_config('key_length');
  if (csr_pubkey_length($content) < $length) {
       echo "uploaded key is not long enough. Please download a proper keyscript and try again<BR>\n";
       return false;
  }

  /*
   * test authenticity of auth_url
   */
  $hash = pubkey_hash($content, true);
  if (substr($hash, 0, (int)Config::get_config('auth_length')) != $auth_url) {
	  echo "Uploaded key and auth_url does not match. Please download a new keyscript and try again<BR>\n";
	  return false;
  }
  /*
   * test to see if the public-key of the CSR has been part of a previously
   * signed certificate
   */
  return true; //!known_pubkey($content);
}
function get_algorithm($csr)
{
	$cmd = "exec echo \"$csr\" | openssl req -noout -text |grep 'Public Key Algorithm'|sed 's/\(.*\:\)[\ ]*\([a-z]*\)Encryption/\\2/g'";
	return exec($cmd);
}

/* known_pubkey()
 *
 * this function takes a valid CSR and scans the database to check if the
 * public-key has been uploaded before as part of (another) CSR.
 *
 * It will assume that the CSR belongs to a previously signed certificate, and
 * unless it can prove that it is unique, it will claim that it has been seen
 * before. This is due to the 'better safe-than-sorry' principle.
 *
 * Note: this will only test for pubkeys belonging to *signed* keys, not
 * an identical CSR already present in the database.
 */
function known_pubkey($csr)
{
	$issued_before = true;
	$pubkey_checksum=pubkey_hash($csr, true);
        $res = MDB2Wrapper::execute("SELECT * FROM pubkeys WHERE pubkey_hash=?",
                                    array('text'),
                                    array($pubkey_checksum));
	if (strlen($res[0]) <= 0) {
		Logger::log_event(LOG_DEBUG, __FILE__ . " CSR with previously unknown public-key (hash: $pubkey_checksum)\n");
		$issued_before=false;
    }  /* update counter in database */
        else if (count($res) == 1) {
             MDB2Wrapper::update("UPDATE pubkeys SET uploaded_nr = uploaded_nr + 1 WHERE pubkey_hash=?",
                                 array('text'),
                                 array($res[0]['pubkey_hash']));
        }
	else {
		Logger::syslog(LOG_ERR,"Duplicate signed certificates in database! -> $pubkey_checkusm");
                exit(1);
	}
	return $issued_before;
} /* end known_pubkey */

/* pubkey_hash()
 *
 * Calculates the sha1-hash of the public-key in the uploaded CSR
 */
function pubkey_hash($ssl_data, $is_csr)
{
	if ($is_csr) {
		$pubkey = openssl_csr_get_public_key($ssl_data);
		if (!$pubkey) {
			echo __FILE__ .":".__LINE__." Could not retrieve public key from CSR<br>\n";
			exit(1);
		}
	}
	else {
		$pubkey = openssl_get_publickey($ssl_data);
	}
     $keydata = openssl_pkey_get_details($pubkey);
     return sha1($keydata['key']);
} /* end pubkey_hash */

function csr_pubkey_length($csr)
{
     $csr_pubkey = openssl_csr_get_public_key($csr);
     $keydata = openssl_pkey_get_details($csr_pubkey);
     return $keydata['bits'];
}

/* export the CSR as huma-readable text without the key
 *
 * This should be done by openssl internally, but this is no easy task.
 * openssl_csr_export needs a CSR as a resource, and the only way of getting a
 * CSR as a resource, is via openssl_csr_new. Hence, one cannot import an
 * existing CSR as a CSR in php..
 *
 * The function is deprecated and will be removed
 */
function text_csr($csr)
{
     $cmd = "exec echo \"".$csr."\" | openssl req -noout -text";
     $exported_csr = shell_exec($cmd);
     return $exported_csr;
} /* end text_csr */
?>
