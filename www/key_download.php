<?php
require_once('confusa_include.php');	/* get path */
require_once('logger.php');
require_once('config.php');
require_once('csr_lib.php');
require_once('mdb2_wrapper.php');

/* only accept downloads from sources that specify *both* auth_var and
 * common_name (the client should know this anyway */
if (isset($_GET[Config::get_config('auth_var')]) && $_GET['common_name']) {
	$authvar	= htmlentities($_GET[Config::get_config('auth_var')]);
	$user		= htmlentities($_GET['common_name']);

        $res = MDB2Wrapper::execute("SELECT cert FROM cert_cache WHERE auth_key=? AND cert_owner=? AND valid_untill > current_timestamp()",
                                    array('text', 'text'),
                                    array($authvar, $user));
	if (count($res) == 1) {
		echo $res[0]['cert'] . "\n";
                Logger::log_event(LOG_NOTICE, "Sending certificate with hash " . pubkey_hash($res[0]['cert'], false) . " and auth-token $authvar to user from ip " . $_SERVER['REMOTE_ADDR']);
        }
        else {
             echo "Error in getting certificate, got " . count($res) . " results\n";
        }
}
?>
