<?php
require_once '../www/confusa_include.php';
requrie_once 'pw.php';
require_once 'mdb2_wrapper.php';
require_once 'cert_lib.php';
require_once 'logger.php';
require_once 'person.php';
require_once 'cert_manager.php';

$log_error_code = create_pw(8);

/**
 * assertEnvironment() make sure that we are operating safely
 *
 * Assert that we are on SSL and on appropriate level before continuing. If any
 * of the requirements are not met, we abort and close the connection.
 *
 * @param void
 * @return void
 */
function assertEnvironment()
{
	global $log_error_code;
	/* are we on SSL */
	if (strtolower($_SERVER['HTTPS']) != 'on') {
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) Server is not running on SSL. Blocking robot-connections.");
		exit(0);
	}
	/* SSLv3 */
	$protocol = strtolower($_SERVER['SSL_PROTOCOL']);
	if (!($protocol == 'sslv3' || $protocol == 'tlsv1')) {
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) Not on proper ssl protocol. Need SSLv3/TLS. Got " . $_SERVER['SSL_PROTOCOL']);
		exit(0);
	}
	/* do we have a client certificate? */
	$cert = $_SERVER['SSL_CLIENT_CERT'];
	if (!isset($cert) || $cert == "") {
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) Connection from client (".
				  $_SERVER['REMOTE_ADDR'].
				  ") without certificate. Dropping connection.");
		exit(0);
	}
	return true;
} /* end assertEnvironment() */

/**
 * createAdminPerson() Create a person-object based on the certificate
 * credentials passed via the client certificate.
 *
 * Ideally, this should be done via Confusa_Auth, however, since we do not have
 * a live Feide-session, but are basing the authetnication on an X.509
 * certificate, the case is a corner, thus we do it here.
 *
 * @return Person|NULL the decorated person or NULL if the creation failed.
 */
function createAdminPerson()
{
	global $log_error_code;
	/*
	 * Find the cert in the robot_cert table
	 *
	 * If the query fails for some reason, we jumb out, returning null
	 */
	$fingerprint = openssl_x509_fingerprint($cert, true);
	try {
		$cert_res = MDB2Wrapper::execute("SELECT * FROM robot_certs WHERE fingerprint = ?",
						 array('text'),
						 array($fingerprint));
	} catch (DBStatementException $dbse) {
		Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ . " Error with syntax for robot_certs-query.(".$dbse->getMessage().")");
		return null;
	} catch (DBQueryException $dbqe) {
		Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ . " Error with params in robot_certs-query.(".$dbqe->getMessage().")");
		return null;
	}

	switch(count($cert_res)) {
	case 0:
		return null;
	case 1:
		/*
		 * We have to do the compare in a rather awkward way to ensure
		 * that differences in spaces, newlines, tabs and whatnot are
		 * removed.
		 */
		openssl_x509_export(openssl_x509_read($cert), $stored_admin_dump);
		openssl_x509_export(openssl_x509_read($cert_res[0]['cert']), $stored_client_dump);
		if ($stored_admin_dump != $stored_client_dump) {
			return null;
		}
		break;
	default:
		Logger::log_event(LOG_ALERT, "Several certs (".count($cert_res).") in DB matching fingerprint ($fingerprint), cannot auth client.");
		return null;
	}

	/* Get the details for the owner of the certificate, use this as a
	 * basis for authenticating the person */
	try {
		$query  = "SELECT sa.*, n.name as nren_name FROM ";
		$query .= "(SELECT s.name as subscriber_name, nren_id, a.* ";
		$query .= "FROM admins a LEFT JOIN subscribers s ON a.subscriber = s.subscriber_id WHERE admin_id=? AND name iS NOT NULL) ";
		$query .= "sa LEFT JOIN nrens n ON n.nren_id = sa.nren_id";
		$res = MDB2Wrapper::execute($query,
					    array('text'),
					    array($cert_res[0]['uploaded_by']));
	} catch (DBStatementException $dbse) {
		echo $dbse->getMessage() . "<br />\n";
		return null;
	} catch (DBQueryException $dbqe) {
		echo $dbqe->getMessage() . "<br />\n";
		return null;
	}

	if (count($res) != 1) {
		echo "Did not find the owner (".$cert_res[0]['uploaded_by'].") of the certificate ";
		echo "(got " . count($res) . " rows in return). <br />\n";
		echo "This certificate should not be present in the DB.<br />\n";
		return null;
	}
	try {
		/*
		 * Decorate person
		 */
		$person = new Person();
		if (isset($res[0]['admin_name']) && $res[0]['admin_name'] != "") {
			$person->setName($res[0]['adminn_name']);
		} else {
			$person->setName($res[0]['admin']);
		}
		$person->setEPPN($res[0]['admin']);
		$person->setAuth(true);
		$person->setEntitlement(Config::get_config('entitlement_admin'));
		$person->setSubscriberOrgName($res[0]['subscriber_name']);
		$person->setNREN($res[0]['nren_name']);
		$person->setName($res[0]['admin_name']);
		$person->setEmail($res[0]['admin_email']);
	} catch (CriticalAttributeException $cae) {
		echo "Problems with setting the eduPersonPrincipalName for person.<br />\n";
		echo "Check the data in admins (admin_id: " . $cert_res[0]['uploaded_by'] . ")<br />\n";
		return null;
	}
	return $person;
}

function parseRevList($list, $admin)
{
	$revokedUsers = array();
	$cm = CertManagerHandler::getManager($admin);
	foreach ($list as $value) {
		/* Get eppn */
		if (!isset($value['eppn'])) {
			echo "Need eppn. This is a REQUIRED attribute.<br />\n";
			break;
		}
		$eppn = $value['eppn'];

		/* Search after matches for cn and subscriber */
		$list = $cm->get_cert_list_for_persons($eppn, $admin->getSubscriberOrgName());
		$count = 0;
		if (count($list) > 0) {
			foreach ($list as $key => $value) {
				try {
					if ($cm->revoke_cert($value['auth_key'], "privilegeWithdrawn")) {
						$count = $count + 1;
					}

				} catch (CGE_KeyRevokeException $kre) {
					echo $kre->getMessage() . "<br />\n";
				}
			}
		}
		$revokedUsers[] = array('eppn' => $eppn, 'count' => $count);
	}
	return $revokedUsers;
} /* end parseRevList */

/**
 * createCertList() Create a list of all valid certificates for the given subscriber
 */
function createCertList($admin)
{
	$cm = CertManagerHandler::getManager($admin);
	$list = $cm->get_cert_list_for_persons("", $admin->getSubscriberOrgName());
	$res = array();
	$found_certs = 0;
	$found_users = 0;
	if (isset($list) && is_array($list) && count($list) > 0) {
		foreach($list as $value) {
			$cert = openssl_x509_parse(openssl_x509_read($value['cert']), false);
			$eppn_array = explode(" ", $value['cert_owner']);
			$eppn = $eppn_array[count($eppn_array) - 1];
			if (isset($res[$eppn])) {
				$res[$eppn]['count'] = $res[$eppn]['count'] + 1;
			} else {
				$res[$eppn] = array('eppn' => $eppn, 'fullDN' => $cert['name'], 'count' => '1');
				$found_users = $found_users + 1;
			}
			$found_certs = $found_certs + 1;
		}
	}
	Logger::log_event(LOG_NOTICE, "Created a list of $found_certs valid certificates for $found_users " .
			  "different user(s) in subscriber " . $admin->getSubscriberOrgName());
	return $res;
} /* end createCertList */

function printXMLRes($resArray, $type = 'userList')
{
	echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n";
	echo "<ConfusaRobot>\n";
	$ending = "";
	switch(strtolower($type)) {

	case 'userlist':
		$start  = "\t<userList>\n";
		$ending = "\t</userList>\n";
		break;
	case 'revokelist':
		$start  =  "\t<revokedCerts>\n";
		$ending = "\t</revokedCerts>\n";
		break;
	default:
		break;

	}
	if (isset($resArray) && is_array($resArray) && count($resArray) > 0) {
		echo $start;
		foreach($resArray as $value) {
			$line = "\t\t<listElement eppn=\"".$value['eppn']."\"";
			if (isset($value['count'])) {
				$line .= " count=\"".$value['count']."\"";
			}
			if (isset($value['fullDN'])) {
				$line .= " fullDN=\"".$value['fullDN']."\"";
			}
			echo $line . " />\n";
		}
		echo $ending;
	}
	echo "</ConfusaRobot>\n";
}

/* Is the certificate a legit cert? */
$admin = createAdminPerson();
if(!isset($admin) || !$admin->isAuth()) {
	echo "Not authenticated! Piss off!<br />\n";
	exit(0);
}
if (false && Config::get_config('debug')) {
	echo "<hr />\n";
	echo "<table class=\"small\">";
	echo "<tr><td><b>Name:</b></td><td>".$admin->getName()."</td></tr>";
	echo "<tr><td><b>eduPersonPrincipalName:</b></td><td>".$admin->getEPPN()."</td></tr>";
	echo "<tr><td><b>CommonName in DN</b></td><td>".$admin->getX509ValidCN()."</td></tr>";
	echo "<tr><td><b>email:</b></td><td>".$admin->getEmail()."</td></tr>";
	echo "<tr><td><b>Country:</b></td><td>".$admin->getCountry()."</td></tr>";
	echo "<tr><td><b>OrganizationalName:</b></td><td>".$admin->getSubscriberOrgName()."</td></tr>";
	echo "<tr><td><b>Entitlement:</b></td><td>".$admin->getEntitlement()."</td></tr>";
	echo "<tr><td><b>NREN:</b></td><td>".$admin->getNREN()."</td></tr>";
	echo "<tr><td><b>Complete /DN:</b></td><td>".$admin->getX509SubjectDN()."</td></tr>";
	echo "	<tr><td></td><td></td></tr>";
	echo "	<tr><td><b>Time left</b></td><td>".$timeLeft."</td></tr>";
	echo "<tr><td><b>Time since AuthN</b></td><td>".$timeSinceStart."</td></tr>";
	echo "</table><br />";
	echo "<hr />\n";
}


/* Get list of issued certiticates */
if (isset($_GET['action'])) {
	$action = Input::sanitize($_GET['action']);
} else {
	/* if no action provided, assume the client wants a list of issued certificates. */
	$action = 'cert_list';
}

/* We are going to dump something as xml anyways, so set the header as xml */
header ("content-type: text/xml");

switch($action) {
case 'cert_list':
	$res = createCertList($admin);
	printXMLRes($res, 'userlist');
	break;
case 'revoke_list':
	if (isset($_GET['list'])) {
		$xml = $_GET['list'];
	}
	else if (isset($_POST['list'])) {
		$xml = $_POST['list'];
	} else if (Config::get_config('debug')) {
		$xml = file_get_contents('robot/example.xml');
	} else {
		echo "No data provided.<br />\n";
	}
	/* Start parsing */
	if (isset($xml)) {
		$parsedXML = new SimpleXMLElement($xml);
		$name	= $parsedXML->getName();
		if ($name != "ConfusaRobot") {
			echo "wrong type of XML. Aborting...<br />\n";
			exit(0);
		}

		foreach ($parsedXML as $key => $value) {
			switch ($key) {
			case 'revocationList':
				$res = parseRevList($value, $admin);
				break;
			default:
				echo "Unknown type. Are you sure you are following the DTD?<br />\n";
				break;
			}
		}
	}
	printXMLRes($res, 'revokeList');
	break;
default:
	echo "Unknown action.<br />\n";
	exit(0);
}

?>