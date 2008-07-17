<?php
include_once('sql_lib.php');
include_once('logger.php');
/* test_file_content()
 *
 * Function for testing the content of certificate-files.
 * Since we know that we'll receiving CSRs, we can custom-design the test here.
 *
 * Other 'lowlevel'-tests are performed by FileUpload-object.
 */
function test_content($content)
{
  global $person;
  $testres = false;
  /* check for start */
  $start = substr($content, 0, 35);
  $end = substr($content, -34, -1);

  /* test start and ending of certificate */
  if (strcmp("-----BEGIN CERTIFICATE REQUEST-----", $start)===0 &&
      strcmp("-----END CERTIFICATE REQUEST-----", $end) === 0) {
    $testres = true;
  }

  /* test fields of CSR */
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

	$query="SELECT * FROM pubkeys WHERE pubkey_hash='".$pubkey_checksum."'";
	$sql=get_sql_conn();
	$res=$sql->execute($query);
	if (mysql_num_rows($res) == 0) {
		Logger::log_event(LOG_DEBUG, __FILE__ . " CSR with previously unknown public-key (hash: $pubkey_checksum)\n");
		$issued_before=false;
	}
        /* update counter in database */
        else if (mysql_numrows($res) == 1) {
             $row = mysql_fetch_assoc($res);
             $query = "UPDATE pubkeys SET uploaded_nr = uploaded_nr + 1 WHERE pubkey_hash='" . $row['pubkey_hash'] . "'";
             $sql->update($query);
        }
	else {
		Logger::syslog(LOG_ERR,"Duplicate signed certificates in database! -> $pubkey_checkusm");
                mysql_free_result($res);
                exit(1);
	}

	mysql_free_result($res);
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

?>
