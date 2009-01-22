<?php
require_once('confusa_include.php');	/* get path */
require_once('mdb2_wrapper.php');
require_once('logger.php');
require_once('config.php');
require_once('csr_lib.php');

  /* key_upload.php
   *
   * This page shall receive a base64-encoded get-request from a client, decode
   * to retrieve the CSR and store in db.
   *
   * it will sanitize the csr via 3 steps:
   *    1) Get the subject from the CSR via openssl (this implies that
   *       openssl_csr_get_subject() is adequately foolproof.
   *    2) if the pubkey is known. This is done via openssl (get the public-key,
   *       calculate the hash, compare that to the database.
   *    3) Test the content (if it start and ends with proper syntax). See 
   */
$ip=$_SERVER['REMOTE_ADDR'];
if ( isset($_GET['remote_csr']) && $_GET[Config::get_config('auth_var')]) {
	$csr = base64_decode(htmlentities$_GET['remote_csr']));
     $auth_var = htmlentities($_GET[Config::get_config('auth_var')]);
     $csr_subject=openssl_csr_get_subject($csr);
     if ($csr_subject) {
          $common = $csr_subject['CN'];
          /* test to see if the CSR is valid, not used before and long enough */
          if (test_content($csr)) {
               /* has the ip tried to upload many different CSRs with
                * different common-names? */
               $res_ip = MDB2Wrapper::execute("SELECT common_name, count(*) FROM csr_cache WHERE from_ip=? GROUP BY common_name ORDER BY count(*) DESC",
                                              array('text'),
                                              array($ip));
               if (count($res_ip) > Config::get_config('remote_ips')) {
                    echo "Your IP is temporarily disabled due to CSR-upload overflow. Please try again later<BR>\n";
                    $msg = "Detected abusive client from $ip -> Has " . $res_ip[0]['count(*)'] . " entries ";
                    $msg .= "with common_name " . $res_ip[0]['common_name'] . " -> Dropping content.<BR>\n";
                    Logger::log_event(LOG_WARNING, $msg);
                    exit(1);
               }
               $counter = 0;
               while($counter < count($res_ip)) {
                    $content = $res_ip[$counter];
                    $counter++;
                    if ($content['count(*)'] > Config::get_config('remote_ips')) {
                         echo "Your IP is temporarily disabled due to excessive CSR-upload <BR>\n";
                         echo "You must approve the pending CSRs first, or wait for them to time out. <BR>\n";
                         echo "The timeout normally takes 1 day<BR>\n";
                         Logger::log_event(LOG_WARNING, "Blocked user from entering excessive amount of CSRs. User: " . $content['common_name'] . " from IP: " . $_SERVER['REMOTE_ADDR']);
                         exit(1);
                    }
               }
               MDB2Wrapper::update("INSERT INTO csr_cache (csr, uploaded_date, from_ip, common_name, auth_key) VALUES(?, current_timestamp(), ?, ?, ?)",
                                   array('text', 'text', 'text', 'text'),
                                   array($csr, $ip, $common, $auth_var));
               Logger::log_event(LOG_INFO, "Inserted new CSR from $ip ($common) with auth_key $auth_var and hash " . pubkey_hash($csr));
          }
     }
     else {
          Logger::log_event(LOG_NOTICE, "Invalid CSR received from $ip, aborting");
     }
}
?>
