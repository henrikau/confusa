<?php
require_once 'confusa_include.php';	/* get path */
require_once 'config.php';

if (!Config::get_config('auth_bypass')) {
	include 'not_found.php';
	not_found($_SERVER['SCRIPT_NAME']);
}

if (Config::get_config('maint')) {
	echo "1\n";
	echo "<h2>Under maintenance. Come back later.</h2>\n";
	exit(0);
}

require_once 'mdb2_wrapper.php';
require_once 'logger.php';
require_once 'csr_lib.php';

  /* key_upload.php
   *
   * This page shall receive a base64-encoded get-request from a client, decode
   * to retrieve the CSR and store in db if it conforms to a set of requirements.
   *
   * The uploaded CSR must meet the following criteria:
   *	1) Properly formed CSR
   *	2) Keylength of proper length (set by confusa_config.php::key_length)
   *	3) Auth_url derived from the public key (subset of sha1sum of pubkey,
   *	   length set by confusa_config.php::auth_length)
   *	4) The fields of the DN must match *exactly* the appropriate namespace
   *	5) The public key from the CSR does not belong to a previously signed certificate
   *
   *	A lot of this (the test of the actual CSR) is done by csr_lib. The test
   *	to see if the key has been signed before, is done locally in this file.
   */
$ip=$_SERVER['REMOTE_ADDR'];
if ( isset($_GET['remote_csr']) && $_GET[Config::get_config('auth_var')]) {
	$csr = base64_decode(htmlentities($_GET['remote_csr']));
     $auth_var = htmlentities($_GET[Config::get_config('auth_var')]);
     $csr_subject=openssl_csr_get_subject($csr);
     if ($csr_subject) {
          $common = $csr_subject['CN'];
          /*
	   * test to see if the CSR is valid, properly formed
	   */
          if (test_content($csr, $auth_var)) {

		  /*
		   * test to see if the CSR already exists in the database
		   */
		  $res = MDB2Wrapper::execute("SELECT auth_key, from_ip FROM csr_cache WHERE csr=?",
					      array('text'),
					      array($csr));
		  if (count($res) > 0) {
			  foreach ($res as $key => $value) {
				  if ($value['from_ip'] == $_SERVER['REMOTE_ADDR']) {
					  echo "NOK, previously updated CSR. Create a new keypair, and try again.<BR>\n";
					  exit(1);
				  }
				  else {
					  echo "NOK<BR>\n";
					  echo "previously updated CSR. Create a new keypair, and try again.<BR>\n";
					  $msg  = __FILE__ . " test_content() identical CSR from several remote hosts (current: ";
					  $msg .= $_SERVER['REMOTE_ADDR'] . ") ";
					  $msg .= "(previous: " . $value['from_ip'] . ")";
					  Logger::log_event(LOG_WARNING, $msg);
					  exit(1);
				  }
			  }
			  $logstring  = __FILE__ . " test_content() got " . count($res);
			  $logstring .= " matches on an incoming CSR from " . $_SERVER['REMOTE_ADDR'];
			  Logger::log_event(LOG_WARNING, $logstring);
			  $testres = false;
		  }
		  else if (count($res) < 0) {
			  echo "some undefined error<BR>\n";
			  exit(1);
		  }

		  /*
		   * Abusive remote tests:
		   */

		  /* has the ip tried to upload many different CSRs with
                * different common-names? */
		  $ip = $_SERVER['REMOTE_ADDR'];
		  $res_ip = MDB2Wrapper::execute("SELECT count(*) FROM csr_cache WHERE from_ip=?",
						 array('text'),
						 array($ip));
		  if ((int)$res_ip[0]['count(*)'] > (int)Config::get_config('remote_ips')) {
			  echo "Your IP is temporarily disabled due to CSR-upload overflow. Please try again later<BR>\n";
			  $msg = __FILE__ . " Detected abusive client from $ip -> Has " . $res_ip[0]['count(*)'] . " entries ";
			  $msg .= "with common_name " . $res_ip[0]['common_name'] . " -> Dropping content.<BR>\n";
			  Logger::log_event(LOG_WARNING, $msg);
			  exit(1);
		  }

		  /* Has the system received several CSRs from one (or many)
		   * IPs with the same common_name ?*/
		  $res_cn = MDB2Wrapper::execute("SELECT count(*) FROM csr_cache WHERE common_name=?",
						 array('text'),
						 array($common));
		  if ((int)$res_cn[0]['count(*)'] > Config::get_config('remote_ips')) {
			  echo "NOK. Too many CSRs reside in the cache with matching common_name<BR>\n";
			  $str  = __FILE__ . " Blocked user from entering excessive amount of CSRs. ";
			  $str .= "User: " . $content['common_name'] . " ";
			  $str .= "from IP: " . $_SERVER['REMOTE_ADDR'];
			  Logger::log_event(LOG_WARNING, $str );
			  exit(1);
		  }

		  /* No error found, CSR looks valid */
		  $query  = "INSERT INTO csr_cache (csr, uploaded_date, from_ip,";
		  $query .= " common_name, auth_key)";
		  $query .= " VALUES(?, current_timestamp(), ?, ?, ?)";
		  MDB2Wrapper::update($query,
				      array('text', 'text', 'text', 'text'),
				      array($csr, $ip, $common, $auth_var));
		  $logmsg  = __FILE__ . " Inserted new CSR from $ip ($common) with hash " . pubkey_hash($csr, true);
		  Logger::log_event(LOG_INFO, $logmsg);
	       echo "OK<BR>\n";
          }
     }
     else {
          Logger::log_event(LOG_NOTICE, "Invalid CSR received from $ip, aborting");
     }
}
?>
