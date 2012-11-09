#!/usr/bin/php
<?php
if (php_sapi_name() !=="cli") {
	echo "test_capi will only run in cli-mode!\n";
	exit(1);
}
require_once dirname(__FILE__) . "/../www/confusa_include.php";
require_once "confusa_constants.php";
require_once "NREN_Handler.php";
require_once "Person.php";
require_once "CA.php";

function show_help()
{
	echo "Usage: " . $GLOBALS['argv'][0] . " OPTIONS\n\n";
	echo "-l\t\tList all nrens found in the database\n";
	echo "-n NREN\t\tID of NREN as found in the database\n";
	echo "-o orderNumber\tThe ordernumber assigned to the request by Comodo\n";
	echo "-q\t\tQuiet, no extra output, just status\n";
	echo "-h\t\tShow this helptext\n";
}

function listNRENs()
{
	$nrens = NREN_Handler::getAllNRENs();
	if (is_null($nrens) or count($nrens) === 0) {
		echo "No NRENs found in the database\n";
		return;
	}
	echo "Listing NRENs in the database\n";
	echo "ID\tName\n";
	foreach ($nrens as $value) {
		echo $value['id'] . "\t" . $value['name'] . "\n";
	}
}

function queryOrder($nren, $order)
{
	echo "Looking for $order issued to nren $nren\n";
	$nren = NREN_Handler::getByID($nren);
	if (!$nren) {
		echo "\n\tError when retrieving NREN $nren, please use correct NREN-ID\n\n";
		listNRENs();
		return;
	}
	$person = new Person();
	$person->setNREN($nren);
	$person->isAuth(true);
	$ca = CAHandler::getCA($person);
	$status = $ca->pollCertStatus($order, true);
	$errors = explode("\n", $status, 2);
	if (!is_numeric($errors[0])) {
		echo "Malformed response from CA, all bets are off :/\n";
		print_r($errors);
		return;
	}
	switch($errors[0]) {
	case 1:
		echo "Certificate looks OK, no errors detected\n";
		echo "TODO: download cert and inspect content here\n";
		break;

	case "-4":
		/* invalid password? */
		$known_error = false;
		echo "You are not allowed to log in and view this certificate\n";
		$caa = "CA Account problems -";
		if (strpos($errors[1], "loginPassword") !== FALSE) {
			echo "$caa invalid password\n";
			$known_error = true;
		}
		/* invalid username? */
		if (strpos($errors[1], "loginName") !== FALSE) {
			echo "$caa invalid username\n";
			$known_error = true;
		}
		if (strpos($errors[1], "ap") !== FALSE) {
			echo "$caa invalid AP-Name\n";
			$known_error = true;
		}
		if (strpos($errors[1], "orderNumber") !== FALSE) {
			echo "Invalid orderNumber, make sure that the certificate you are looking for".
				" are accessible via this NREN-account!\n";
			$known_error = true;
		}
		if (!$known_error)
			print_r($errors);
		break;
	case "-16":
		echo "meh";
		break;
	case "-13":
		echo "The CSR contained a publickey with invalid keysize, make sure it is long enough!\n";
		print_r($errors[1]);
		break;
	case "-20":
		echo "CSR rejected\n";
		print_r($errors[1]);
		break;
	case "-21":
		echo "Certificate has been revoked\n";
		print_r($errors[1]);
		break;
	default:
		echo "unknown error (" . $errors[0] . ")\n";
		print_r($errors[1]);
		break;
	} /* endswitch */
	echo "\n";
}

$options = getopt("o:n:hql");
if (is_null($options) || count($options) == 0) {
	show_help();
	exit(0);
}
$quiet = false;
foreach($options as $opt => $value) {
	switch($opt) {
	case 'l':
		listNRENs();
		exit(0);
	case 'n':
		$nren = $value;
		break;
	case 'o':
		$orderNumber = $value;
		break;
	case 'q':
		$quiet = true;
		break;

		/* fallthrough  */
	case 'h':
	default:
		show_help();
		exit(0);
	}
} /* end foreach */

if (isset($nren) && isset($orderNumber)) {
		queryOrder($nren, $orderNumber);
} else {
	echo "Some errors with params, please provide proper input\n";
	show_help();
}

?>