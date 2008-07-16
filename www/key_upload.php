<?php
require_once('confusa_include.php');	/* get path */
require_once('sql_lib.php');
require_once('logger.php');
require_once('confusa_config.php');
require_once('csr_lib.php');

if (!isset($confusa_config)) {
	/* trouble detecting config, terminating gracefully as sql will also fail */
	echo "ERROR! Cannot detect config. Terminating. <BR>\n";
	exit(0);
}

  /* key_upload.php
   *
   * This page shall receive a base64-encoded get-request from a client, decode
   * to retrieve the CSR and store in db.
   * Also, a few components shall be logged.
   */
$ip=$_SERVER['REMOTE_ADDR'];
global $confusa_config;
if ( isset($_GET['remote_csr']) && $_GET[$confusa_config['auth_var']]) {
	$csr = base64_decode($_GET['remote_csr']);
	$auth_var = htmlentities($_GET[$confusa_config['auth_var']]);

	$csr_subject=openssl_csr_get_subject($csr);
	if ($csr_subject) {
		$common = $csr_subject['CN'];
		/* contact db */
		$sql = get_sql_conn();

		if (!known_pubkey($csr)) {
			/* check ip to see if it's been abusive */
			$ip_query="SELECT common_name, count(*) FROM csr_cache WHERE from_ip='".$ip."' GROUP BY common_name ORDER BY count(*) DESC";
			$res_ip=$sql->execute($ip_query);
                        /* has the ip tried to upload many different CSRs with
                         * different common-names? */
			if (mysql_numrows($res_ip) > $confusa_config['remote_ips']) {
				echo "Your IP is temporarily disabled due to CSR-upload overflow. Please try again later<BR>\n";
				Logger::log_event(LOG_WARNING, "Detected abusive client from ".$ip.". Dropping content.<BR>\n");
				exit(1);
			}
                        while($content = mysql_fetch_assoc($res_ip)) {
                             if ($content['count(*)'] > $confusa_config['remote_ips']) {
                                  echo "Your IP is temporarily disabled due to excessive CSR-upload <BR>\n";
                                  echo "You must approve the pending CSRs first, or wait for them to time out. <BR>\n";
                                  echo "The timeout normally takes 1 day<BR>\n";
                                  exit(1);
                             }
                        }
			if (test_content($csr)) {
				$query = "INSERT INTO csr_cache (csr, uploaded_date, from_ip, common_name, auth_key) ";
				$query .= "VALUES ";
				$query .= "('". $csr . "', ";
				$query .= "current_timestamp(), ";
				$query .= "'" . $ip . "', ";
				$query .= "'" . $common . "',";
				$query .= "'" .$auth_var. "')";
				$sql->update($query);
				Logger::log_event(LOG_INFO, "Inserted new CSR from ".$ip." (".$common.")");
			}
			else {
                             Logger::log_event(LOG_WARNING, "Uploaded CSR from ".$ip." not valid, caught by test_content");
                             exit(1);
			}
		}
		else {
                     Logger::log_event(LOG_NOTICE, "Old key (hash: " .pubkey_hash($csr) .") uploaded from ".$ip." - stopping transaction");
                     echo "This is an old key. Genereate a <B>new</B> key please<BR>\n";
		}
	}
}
?>
