<?php
require_once '../confusa_include.php';
require_once 'AuthHandler.php';
require_once 'Person.php';

/* if nothing is sent via REST, close */
if (is_null($_SERVER['PATH_INFO'])) {
	if (Config::get_config('debug')) {
		echo "No path set!<br />\n";
	}
	exit(0);
}

/* valid session? */
$person = new Person();
$auth = AuthHandler::getAuthManager($person);
$auth->authenticate(true);
if (!$person->isAuth()) {
	if (Config::get_config('debug')) {
		echo "Client is not authenticated!<br />\n";
	}
	exit(0);
}

/* get path, explode and parse content. */
$path = $_SERVER['PATH_INFO'];
$res = explode("/", trim($path, "/"));

if (count($res) != 2) {
	if (Config::get_config('debug')) {
		echo "error with params!<br />\n";
		exit(0);
	}
}

/* personal certificates may have UTF-8 chars in the DN */
if (Config::get_config('cert_product') == PRD_PERSONAL) {
	$dn_name = mysql_real_escape_string($_POST['dn_name']);
} else {
	$dn_name = Input::sanitizeOrgName($_POST['dn_name']);
}

switch($res[0]) {
case 'subscriber_name':
	$name = $res[1];
	if (Config::get_config('cert_product') == PRD_PERSONAL) {
		$dn_name = mysql_real_escape_string($name);
	} else {
		$dn_name = Input::sanitizeOrgName($name);
	}
	if ($dn_name === $name
) {
		echo "OK\n";
	} else {
		echo "NOK\n";
	}
	exit(0);
default:
	print "NOK\n";
	exit(0);
}

/* Find the type */
/* sanitize data based on type */
/* compare provided data with washed data */
/* if OK, return "OK", otherwase "NOK" */
?>
