#!/usr/bin/php
<?php
if (php_sapi_name() !=="cli") {
	echo "test_capi will only run in cli-mode!\n";
	exit(1);
}
$extended = false;

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
	echo "-e extended\tExtended search for a certificate, get as much info about the owner as possible (requires a valid orderNumber)\n";
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
function getSubjectArray($subject)
{
	$res = array();
	foreach (explode("/", trim($subject)) as $key => $item) {
		if ($item==="")
			continue;
		$component = explode("=", $item);
		if (array_key_exists($component[0], $res)) {
			$res[$component[0]] .= ", " . $component[1];
		} else {
			$res[$component[0]] = $component[1];
		}
	}
	return $res;
}
function getEppnCN($cn)
{
	/* last element is eppn */
	$ceppn = explode(" ", trim($cn));
	return $ceppn[count($ceppn) - 1];
}
function getNameCN($cn)
{
	/* assume eppn is present, i.e. PRD_ESCIENCE */
	$eppn = getEppnCN($cn);
	$epos = strpos($cn, $eppn);
	return substr($cn, 0, $epos);
}
function getAndPrintOrder($ca, $order, $print)
{
	$cert = $ca->getCert($order);
	if ($cert !== null && $print) {
		echo "Subject:\t"    . $cert->getSubject()     . "\n";
		echo "Length:\t\t"   . $cert->getLength()      . "\n";
		echo "Type:\t\t"     . $cert->getType()        . "\n";
		echo "Fingerprint:\t". $cert->getFingerprint() . "\n";
		echo "Hash:\t\t"     . $cert->getHash()        . "\n";
		echo "Pubkeyhash:\t" . $cert->getPubKeyHash()  . "\n";
		echo "Serial:\t\t"   . $cert->getSerial()      . "\n";
		echo "Valid from:\t" . $cert->getBeginDate()   . "\n";
		echo "Valid to:\t" .   $cert->getEnddate()     . "\n";
	}
	return $cert;
}
function getCert($ca, $order, $person)
{
	global $extended;
	$cert = getAndPrintOrder($ca, $order, !$extended);

	if ($extended) {
		echo "Running extended search\n";
		$subj = getSubjectArray($cert->getSubject());
		$eppn = null;
		/* need to decorate $person further in order for CA to accept it */
		$name = $subj['CN'];
		/* set eppn if appropriate */
		if (Config::get_config('cert_product') === PRD_ESCIENCE) {
			$eppn = getEppnCN($name);
			$name = getNameCN($name);
		}
		$person->setName($name);
		$person->setEPPN($eppn);
		$list = $ca->getCertListForEPPN(getEppnCN($subj['CN']), $subj['O']);
		foreach ($list as $item) {
			echo "Looking at order " . $item['auth_key'] . "\n";
			getAndPrintOrder($ca, $item['auth_key'], true);
		}
	}
} /* end getCert */

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
		return;
	}
	echo "Response from CA backend: " . $errors[0] . ":\n";
	switch($errors[0]) {
	case 0:
		echo "Certificate is currently being processed by Comodo\n";
		break;
	case 1:
		echo "Certificate available, no errors detected\n";
		getCert($ca, $order, $person);
		break;
	case -1:
		echo "Request via vulnerable channel (non-https)\n";
		break;
	case -2:
		echo "Unrecognized argument sent to CA backend.\n";
		echo $status . "\n";
		break;
	case "-3":
	case "-4":
		/* invalid password? */
		echo "You are not allowed to log in and view this certificate\n";
		$caa = "CA Account problems -";
		if (strpos($errors[1], "loginPassword") !== FALSE) {
			echo "$caa invalid password\n";
		}
		/* invalid username? */
		if (strpos($errors[1], "loginName") !== FALSE) {
			echo "$caa invalid username\n";
		}
		if (strpos($errors[1], "ap") !== FALSE) {
			echo "$caa invalid AP-Name\n";
		}
		if (strpos($errors[1], "orderNumber") !== FALSE) {
			echo "Invalid orderNumber, make sure that the certificate you are looking for".
				" are accessible via this NREN-account!\n";
		}
		break;
	case "-13":
		echo "The CSR contained a publickey with invalid keysize, make sure it is long enough!\n";
		break;
	case "-14":
		echo "Unknown error\n";
		break;
	case "-16":
		echo "Permission denied when contacting Comodo backend\n";
		break;
	case "-17":
		echo "Confusa used GET insted of POST when contacting CA backend\n";
		break;
	case "-20":
		echo "CSR rejected by CA\n";
		break;
	case "-21":
		echo "Certificate has been revoked\n";
		break;
	case "-22":
		echo "Awaiting payment, certificate on hold\n";
		break;
	default:
		echo "unknown error (" . $errors[0] . ")\n";
		break;
	} /* endswitch */

	print_r($errors[1]);
	echo "\n";
}

$options = getopt("o:n:ehl");
if (is_null($options) || count($options) == 0) {
	show_help();
	exit(0);
}
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
	case 'e':
		$extended = true;
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