<?php
  /* csr_lib.php
   *
   * Small library with common functions to verify contents of CSRs, compare to
   * config-parameters as well as person-attributes.
   *
   * Author: Henrik Austad <henrik.austad@uninett.no>
   */
include_once 'mdb2_wrapper.php';
include_once 'logger.php';
require_once 'csr_not_found.php';

/**
 * test_content - test a CSR for deficiencies
 *
 * This function is to be used when testing uploaded CSRs for flaws and errors.
 * It will test for:
 * - common text-patterns
 * - that the key meets the required key-length
 * - that it is a normal CSR (previous point will fail if it is a 'bogus' CSR
 * - that the auth_url is derived from the supplied CSR
 */
function test_content($content, $auth_url)
{
  global $person;
  $testres = true;
  /* check for start */
  $start = substr($content, 0, strlen("-----BEGIN CERTIFICATE REQUEST-----"));
  $end = substr($content, -(strlen("-----END CERTIFICATE REQUEST-----")+1), -1);

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
  return true;
}
function get_algorithm($csr)
{
	$cmd = "exec echo \"$csr\" | openssl req -noout -text |grep 'Public Key Algorithm'|sed 's/\(.*\:\)[\ ]*\([a-z]*\)Encryption/\\2/g'";
	return exec($cmd);
}

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

function get_csr_from_db_raw($eppn, $auth_key)
{
	$csr_res = MDB2Wrapper::execute("SELECT * FROM csr_cache WHERE auth_key=? AND common_name=?",
					array('text', 'text'),
					array($auth_key, $eppn));
	$size = count($csr_res);
	switch ($size) {
	case 0:
		throw new CSRNotFoundException("CSR with token $auth_key not found for $eppn");
	case 1:
		return $csr_res[0];
	}
	throw new ConfusaGenException("Too many CSRs found in the database with token $auth_token");
	
}
function get_csr_from_db($person, $auth_key)
{
	$csr = get_csr_from_db_raw($person->getX509ValidCN(), $auth_key);
	return $csr['csr'];
}

function delete_csr_from_db($person, $auth_key)
{
	if (!$person->isAuth())
		return false;

	/* Verify that the CSR is present */
	try {
		$csr = get_csr_from_db_raw($person->getX509ValidCN(), $auth_key);
	} catch (CSRNotFoundException $csrnfe) {
		echo "No matching CSR found.<BR>\n";
		$msg  = "Could not delete CSR from ip ".$_SERVER['REMOTE_ADDR'];
		$msg .= " : " . $person->getX509ValidCN() . " Reason: not found";
		Logger::log_event(LOG_NOTICE, $msg);
		return false;
	} catch (ConfusaGenException $cge) {
		$msg  = "Error in deleting CSR (" . $auth_key . ")";
		$msg .= "for user: " . $person->getX509ValidCN() . " ";
		$msg .= "Too many hits!";
		Framework::error_output($msg);
		Logger::log_event(LOG_ALERT, $msg);
		return false;
	}

	MDB2Wrapper::update("DELETE FROM csr_cache WHERE auth_key=? AND common_name=?",
			    array('text', 'text'),
			    array($auth_key, $person->getX509ValidCN()));
	$msg  = "Dropping csr ". $auth_key . " ";
	$msg .= "for user ".$person->getX509ValidCN()."  (".$_SERVER['REMOTE_ADDR'] . ") from csr_cache";
	logger::log_event(LOG_NOTICE, $msg);
	return true;
}

function print_csr_details($person, $auth_key)
{
	try {
		$csr = get_csr_from_db_raw($person->getX509ValidCN(), $auth_key);
	} catch (CSRNotFoundException $csrnfe) {
		$msg  = "Error with auth-token ($auth_key) - not found. ";
		$msg .= "Please verify that you have entered the correct auth-url and try again.";
		$msg .= "If this problem persists, try to upload a new CSR and inspect the fields carefully";
		Framework::error_output($msg);
		return false;
	} catch (ConfusaGenException $cge) {
		$msg = "Too menu returns received. This can indicate database inconsistency.";
		Framework::error_output($msg);
		Logger::log_event(LOG_ALERT, "Several identical CSRs (" . $auth_token . ") exists in the database for user " . $person->getX509ValidCN());
		return false;
	}
	$subj = openssl_csr_get_subject($csr['csr'], false);
	echo "<table class=\"small\">\n";
	echo "<tr><td>AuthToken</td><td>".$csr['auth_key']."</td></tr>\n";

	/* Print subject-elements */
	foreach ($subj as $key => $value)
                  echo "<tr><td>$key</td><td>$value</td></tr>\n";
	echo "<tr><td>Length:</td><td>".csr_pubkey_length($csr['csr']) . " bits</td></tr>\n";
	echo "<tr><td>Uploaded </td><td>".$csr['uploaded_date'] . "</td></tr>\n";
	echo "<tr><td>From IP: </td><td>".format_ip($csr['from_ip'], true) . "</td></tr>\n";
	echo "<tr><td></td><td></td></tr>\n";
	echo "<tr><td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?delete_csr=$auth_key\">Delete from Database</A> ]</td>\n";
	echo "<td>[ <A HREF=\"".$_SERVER['PHP_SELF']."?sign_csr=$auth_key\">Approve for signing</A> ]</td></tr>\n";
	echo "</table>\n";
	echo "<BR>\n";

	return true;
}

function get_csr_details($person, $auth_key)
{
	$csr = get_csr_from_db_raw($person->getX509ValidCN(), $auth_key);
	$subj = openssl_csr_get_subject($csr['csr'], false);
	$result = array(
		'auth_token'	=> $csr['auth_key'],
		'length'	=> csr_pubkey_length($csr['csr']),
		'uploaded'	=> $csr['uploaded_date'],
		'from_ip'	=> format_ip($csr['from_ip'], true)
		);
	foreach($subj as $key => $value) {
		$result[$key] = $value;
	}

	return $result;
}
?>
