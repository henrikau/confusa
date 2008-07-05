<?php
require_once('_include.php');	/* get path */
require_once('sql_lib.php');
require_once('logger.php');
require_once('config.php');
require_once('csr_lib.php');

global $confusa_config;

if (!isset($confusa_config)) {
	/* trouble detecting config, terminating gracefully as sql will also fail */
	echo "ERROR! Cannot detect config. Terminating. <BR>\n";
	exit(0);
}
/* only accept downloads from sources that specify *both* auth_var and
 * common_name (the client should know this anyway */
if (isset($_GET[$confusa_config['auth_var']]) && $_GET['common_name']) {
	$authvar	= htmlentities($_GET[$confusa_config['auth_var']]);
	$user		= htmlentities($_GET['common_name']);

	/* search db for cert, include in page if found */
	Logger::log_event(LOG_NOTICE, "Got request for cert with auth_key: ".$authvar ." for user ". $user);

	$sql=get_sql_conn();
	$query="SELECT cert FROM cert_cache WHERE auth_key='".$authvar."' AND cert_owner='".$user."' AND valid_untill > current_timestamp()";
	$res=$sql->execute($query);
	if (mysql_num_rows($res) > 0) {
		$cert = mysql_fetch_assoc($res);
		echo $cert['cert'] . "\n";
	}
	mysql_free_result($res);
}
?>
