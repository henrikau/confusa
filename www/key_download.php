<?php
require_once('confusa_include.php');	/* get path */
require_once('logger.php');
require_once('config.php');
require_once('csr_lib.php');
require_once('mdb2_wrapper.php');

/* only accept downloads from sources that specify *both* auth_var and
 * common_name (the client should know this anyway */
if (isset($_GET[Config::get_config('auth_var')]) && $_GET['common_name']) {
      $authvar        = htmlentities($_GET[Config::get_config('auth_var')]);
      $user           = base64_decode($_GET['common_name']);

  if (Config::get_config('standalone')) {
    $res = MDB2Wrapper::execute("SELECT cert FROM cert_cache WHERE auth_key=? AND cert_owner=? AND valid_untill > current_timestamp()",
                                      array('text', 'text'),
                                      array($authvar, $user));

     if (count($res) == 1) {
      echo $res[0]['cert'] . "\n";
                  Logger::log_event(LOG_NOTICE, "Sending certificate with hash " . pubkey_hash($res[0]['cert'], false) . " and auth-token $authvar to user from ip " . $_SERVER['REMOTE_ADDR']);
     }
          else {
               echo "Error in getting certificate, got " . count($res) . " results\n";
         echo "<pre>\n";
         echo "SELECT cert FROM cert_cache WHERE auth_key='$authvar' AND cert_owner='$user' AND valid_untill > current_timestamp()";
         echo "</pre>\n";
          }
     /* not standalone, get the certificate by calling the remote API */
     } else {
      $res = MDB2Wrapper::execute("SELECT collection_code, order_number FROM order_store WHERE auth_key=? AND common_name=?",
                                  array('text', 'text'),
                                  array($authvar, $user));

      if (count($res) == 1) {

        Logger::log_event(LOG_NOTICE, "Looked up $authvar in the order-store. Trying to retrieve certificate with order number " .
                                      $res[0]['order_number'] .
                                      " from the Comodo collect API. Sending to user with ip " .
                                      $_SERVER['REMOTE_ADDR']);

        $collectionCode = $res[0]['collection_code'];
        $collect_endpoint = Config::get_config('capi_collect_endpoint') . "?collectionCode=" . $collectionCode . "&queryType=2";
        $postfields_download["responseType"]="2";
        $postfields_download["responseEncoding"]="0";
        $postfields_download["responseMimeType"]="application/x-x509-user-cert"; 

        $ch = curl_init($collect_endpoint);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $data=curl_exec($ch);
        curl_close($ch);

        /* The first character of the return message is always a status code
         */
        $status=(int)$data;

        if ($status == 0) {
          echo "The certificate is being processed and is not yet available<br />\n";
        } else if ($status == 2) {
           /* no need to send back the status code, only the certificiate
           */
          echo substr($data,1) . "\n";
        } else {
          echo "Received error message $data <br />\n";
        }
      } else {
        echo "Error in getting certificate, did not find order number and collection code in DB<br/>\n";
        echo "<pre>\n";
        echo "SELECT collection_code, order_number FROM order_store WHERE auth_key=$authvar AND common_name=$user";
        echo "</pre>\n";
      }

   }
}
?>
