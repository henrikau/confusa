<?php
include_once('sql_lib.php');
include_once('slcs_logger.php');
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


/* this function takes a valid CSR and scans the database to check if the
 * public-key has been uploaded before as part of (another) CSR.
 *
 * It will also check if the csr lies in the csr_cache.
 */
function known_pubkey($csr)
{
	/* echo __FILE__ .":".__LINE__."<BR>\n".$csr . "<br>\n"; */
	$issued_before = true;
	/* get hash of pubkey in CSR*/
	$cmd = "exec echo \"".$csr."\" | openssl req -pubkey -noout | sha1sum | cut -d ' ' -f 1";
	$pubkey_checksum=trim(shell_exec($cmd));

	/* search db for match on hash and the entire csr in csr_cache */
	$query="SELECT * FROM pubkeys WHERE pubkey_hash='".$pubkey_checksum."'";
	/* echo __FILE__.":".__LINE__. " " . $query . "<BR>\n"; */
	$sql=get_sql_conn();
	$res=$sql->execute($query);
	if (mysql_num_rows($res) == 0) {
		SLCSLogger::log_event(LOG_DEBUG,"New and unique key received\n");
		$issued_before=false;
	}
	else if (mysql_numrows($res) > 1) {
		SLCSLogger::syslog(LOG_ERR,"Duplicate signed certificates in database!");
		/* echo "<FONT COLOR=\"red\">Database inconsistent! Contact webmaster! TODO: do this automatically...</FONT><BR>\n"; */
	}
	mysql_free_result($res);
	return $issued_before;
} /* end test_old */

function csr_debug()
{
     /* put cert in temp-file */
     /* phpinfo(); */
     $filename=create_pw(32).".csr";
     $filepath = WEB_DIR."/tmp/".$filename;
     $fileurl  = dirname($_SERVER['HTTP_REFERER'])."/tmp/".$filename;
     $fd=fopen($filepath,'w+');
     fwrite($fd, "Hello World!\n");
     fclose($fd);
     echo "you can get the file here: <a href=\"" . $fileurl ."\">Here</A><br>\n";
}

?>
