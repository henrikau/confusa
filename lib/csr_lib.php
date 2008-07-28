<?php
include_once('mdb2_wrapper.php');
include_once('logger.php');

/* test_content()
 *
 * This function is to be used when testing uploaded CSRs for flaws and errors.
 * It will test for:
 * - common text-patterns
 * - that the key meets the required key-length
 * - that it is a normal CSR (previous point will fail if it is a 'bogus' CSR
 * - that the CSR has not been uploaded before
 * - that the public-key in the CSR does not belong to a previous
 */
function test_content($content)
{
  global $person;
  $testres = true;
  /* check for start */
  $start = substr($content, 0, 35);
  $end = substr($content, -34, -1);

  /* test start and ending of certificate */
  if (strcmp("-----BEGIN CERTIFICATE REQUEST-----", $start)!==0 &&
      strcmp("-----END CERTIFICATE REQUEST-----", $end) !== 0) {
       $testres = false;
  }

  
  /* test length of pubkey */
  $length = Config::get_config('key_length');
  if (csr_pubkey_length($content) < $length) {
       echo "uploaded key is not long enough. Please download a proper keyscript and try again<BR>\n";
       $testres = false;
  }


  /* test to see if the public-key of the CSR has been part of a previously
   * signed certificate */
  $testres = !known_pubkey($content);

  /* test to see if the CSR already exists in the database */
  $res = MDB2Wrapper::execute("SELECT auth_key, from_ip FROM csr_cache WHERE csr=?",
                              array('text'),
                              array($content));
  echo "count of res: " . count($res) . "<br>\n";
  if (count($res) > 0) {
       foreach ($res as $key => $value) {
            if ($value['from_ip'] == $_SERVER['REMOTE_ADDR']) {
                 echo "You have already uploaded this CSR to the server!<BR>\n";
                 $testres = false;
            }
            else {
                 echo "Someone else has uploaded this CSR to the server<BR>\n";
                 echo "Your ip: " . $_SERVER['REMOTE_ADDR'] . " and previous address: " . $value['from_ip'] . "<BR>\n";
                 $testres = false;
            }
       }
       Logger::log_event(LOG_WARNING, "test_content() got " . count($res) . " matches on an incoming CSR from " . $_SERVER['REMOTE_ADDR']);
       $testres = false;
  }
  return $testres;
}


/* known_pubkey()
 *
 * this function takes a valid CSR and scans the database to check if the
 * public-key has been uploaded before as part of (another) CSR.
 */
function known_pubkey($csr)
{
	$issued_before = true;
	$pubkey_checksum=pubkey_hash($csr);
        $res = MDB2Wrapper::execute("SELECT * FROM pubkeys WHERE pubkey_hash=?",
                                    array('text'),
                                    array($pubkey_checksum));
	if (count($res) == 0) {
		Logger::log_event(LOG_DEBUG, __FILE__ . " CSR with previously unknown public-key (hash: $pubkey_checksum)\n");
		$issued_before=false;
	}
        /* update counter in database */
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
function pubkey_hash($csr)
{
     $cmd = "exec echo \"".$csr."\" | openssl req -pubkey -noout | sha1sum | cut -d ' ' -f 1";
     $pubkey_checksum=trim(shell_exec($cmd));
     return $pubkey_checksum;
}

function csr_pubkey_length($c)
{
     $length = -1;
     $csr = text_csr($c);
     if (preg_match("/Public Key: \([0-9]+ [a-z]+\)/i", $csr, $match)) {
          preg_match("/[0-9]+/", $match[0], $final);
          $length = $final[0];
     }
     return $length;
}
function text_csr($csr)
{
     $cmd = "exec echo \"".$csr."\" | openssl req -noout -text";
     $exported_csr = shell_exec($cmd);
     return $exported_csr;
} /* end text_csr */
?>
