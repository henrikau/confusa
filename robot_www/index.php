<?php
require_once '../www/confusa_include.php';
require_once 'Robot.php';
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

	/*
	 * are we on SSL
	 */
	if (is_null($_SERVER['HTTPS'])) {
		Logger::log_event(LOG_NOTICE,
				  "[RI] ($log_error_code) Environment-variable 'HTTP' not available.");
		exit(0);
	}
	if (strtolower($_SERVER['HTTPS']) != 'on') {
		Logger::log_event(LOG_NOTICE,
				  "[RI] ($log_error_code) Server is not running on SSL. Blocking robot-connections.");
		exit(0);
	}
	/*
	 * SSLv3
	 */
	if (is_null($_SERVER['SSL_PROTOCOL'])) {
		Logger::log_event(LOG_NOTICE,
				  "[RI] ($log_error_code) Environment-variable 'SSL_PROTOCL' not available.");
		exit(0);
	}
	$protocol = strtolower($_SERVER['SSL_PROTOCOL']);
	if (!($protocol == 'sslv3' || $protocol == 'tlsv1')) {
		Logger::log_event(LOG_NOTICE,
				  "[RI] ($log_error_code) Not on proper ssl protocol. Need SSLv3/TLS. Got " .
				  $_SERVER['SSL_PROTOCOL']);
		exit(0);
	}

	/*
	 * do we have a client certificate?
	 */
	if (is_null($_SERVER['SSL_CLIENT_CERT'])) {
		Logger::log_event(LOG_NOTICE,
				  "[RI] ($log_error_code) Environment-variable 'SSL_CLIENT_CERT' not available.");
		exit(0);
	}
	$cert = $_SERVER['SSL_CLIENT_CERT'];
	if (!isset($cert) || $cert == "") {
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) Connection from client (".
				  $_SERVER['REMOTE_ADDR'].
				  ") without certificate. Dropping connection.");
		exit(0);
	}

	/*
	 * Is the certificate properly constructed (can Apache find the DN)?
	 */
	if (is_null($_SERVER['SSL_CLIENT_I_DN'])) {
		Logger::log_event(LOG_NOTICE, "Malformed certificate from " . $_SERVER['REMOTE_ADDR'] . ". Aborting.");
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
	/*
	 * Once confusa_auth has been extended, this part should be moved into
	 * that section. Until then, we do the nitty-gritty work here.
	 */


	global $log_error_code;
	/*
	 * Find the cert in the robot_cert table
	 *
	 * If the query fails for some reason, we jumb out, returning null
	 */
	$fingerprint = openssl_x509_fingerprint($_SERVER['SSL_CLIENT_CERT'], true);
	try {
		$cert_res = MDB2Wrapper::execute("SELECT * FROM robot_certs WHERE fingerprint = ?",
						 array('text'),
						 array($fingerprint));
	} catch (DBStatementException $dbse) {
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) (line: ".__LINE__.
				  ")Error with syntax for robot_certs-query.("
				  .$dbse->getMessage().")");
		return null;
	} catch (DBQueryException $dbqe) {
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) Error with params (line (".
				  __LINE__ .") in robot_certs-query.(".$dbqe->getMessage().")");
		return null;
	}

	switch(count($cert_res)) {
	case 0:
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code): Unauthenticated client connected. Refusing to establish connection. " .
				  $_SERVER['SSL_CLIENT_I_DN']);
		echo "[$log_error_code] You are not authorized to use this API. This incident has been logged.\n";
		return null;
	case 1:
		/*
		 * We have to do the compare in a rather awkward way to ensure
		 * that differences in spaces, newlines, tabs and whatnot are
		 * removed.
		 */
		openssl_x509_export(openssl_x509_read($_SERVER['SSL_CLIENT_CERT']), $stored_admin_dump);
		openssl_x509_export(openssl_x509_read($cert_res[0]['cert']), $stored_client_dump);
		if ($stored_admin_dump != $stored_client_dump) {
			Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) Got matching fingerprint ($fingerprint) ".
					  "but actual certificates differ! Aborting");
			echo "[$code] There were issues with your certificate. Cannot continue using this cert.\n";
			echo "Please use another certificate for the time being.\n";
			echo "This event has been logged.\n";
			return null;
		}
		break;
	default:
		Logger::log_event(LOG_ALERT, "[RI] ($log_error_code) Several certs (".
				  count($cert_res).
				  ") in DB matching fingerprint ($fingerprint), cannot auth client.");
		return null;
	}

	/*
	 * Get the details for the owner of the certificate, use this as a
	 * basis for authenticating the person
	 */
	try {
		$query  = "SELECT sa.*, n.name as nren_name FROM ";
		$query .= "(SELECT s.name as subscriber_name, nren_id, a.* ";
		$query .= "FROM admins a LEFT JOIN subscribers s ON ";
		$query .= "a.subscriber = s.subscriber_id WHERE admin_id=? AND name iS NOT NULL) ";
		$query .= "sa LEFT JOIN nrens n ON n.nren_id = sa.nren_id";
		$res = MDB2Wrapper::execute($query,
					    array('text'),
					    array($cert_res[0]['uploaded_by']));
	} catch (DBStatementException $dbse) {
		$msg = "[$log_error_code] Problem executing query. Is the database-schema outdated?. ";
		Logger::log_message(LOG_INFO, $msg . " Server said: " . $dbse->getMessage());
		echo $msg . "<br />\nServer said: " . htmlentities($dbse->getMessage()) . "<br />\n";
		return null;
	} catch (DBQueryException $dbqe) {
		/* FIXME */
		$msg = "Could not find owner-details for certificate, probably issues with supplied data. ";
		$msg .= "Admin_id: " . htmlentities($cert_res[0]['uploaded_by']);
		Logger::log_message(LOG_INFO, $msg . " Server said: " . $dbqe->getMessage());
		echo $msg . "<br />\nServer said: " . htmlentities($dbqe->getMessage()) . "<br />\n";
		return null;
	}

	if (count($res) != 1) {
		echo "[$log_error_code] Did not find the owner (". htmlentities($cert_res[0]['uploaded_by']) .") of the certificate ";
		echo "(got " . count($res) . " rows in return). <br />\n";
		echo "This certificate should not be present in the DB.<br />\n";
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) No admins appear to own certificate $fingerprint.");
		return null;
	}
	/*
	 * Decorate person.
	 *
	 */
	$person = new Person();
	if (isset($res[0]['admin_name']) && $res[0]['admin_name'] != "") {
		$person->setName($res[0]['admin_name']);
	} else {
		$person->setName($res[0]['admin']);
	}
	try {
		$person->setEPPN($res[0]['admin']);
	} catch (CriticalAttributeException $cae) {
		echo "[$log_error_code] Problems with setting the eduPersonPrincipalName for robot-admin.<br />\n";
		echo "Check the data in admins (admin_id: " . htmlentities($cert_res[0]['uploaded_by']) . ")<br />\n";
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) Internal error? Suddenly provided admin-eppn is not available.");
		return null;
	}
	$person->setAuth(true);
	$person->setNREN(new NREN($res[0]['nren_name']));
	$person->setSubscriber(new Subscriber($res[0]['subscriber_name'], $person->getNREN()));
	$person->setName($res[0]['admin_name']);
	$person->setEmail($res[0]['admin_email']);

	Logger::log_event(LOG_NOTICE, "[RI]: Authenticated robot-client via cert $fingerprint belonging to " . $person->getEPPN());
	return $person;
} /* end createAdminPerson() */

/**
 * printXMLRes() Print the returned array as a valid ConfusaRobot XML-file
 *
 * @param $resArray Array of data to print
 * @param $type String indicating the class of output
 */
function printXMLRes($resArray, $type = 'userList')
{
	/* lets hope that the header has not yet been set so we can trigger
	 * proper XML headers */
	header ("content-type: text/xml");

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
			$line = "\t\t<listElement eppn=\"". htmlentities($value['eppn']) ."\"";
			if (isset($value['count'])) {
				$line .= " count=\"".$value['count']."\"";
			}
			if (isset($value['fullDN'])) {
				$line .= " fullDN=\"". htmlentities($value['fullDN']) ."\"";
			}
			echo $line . " />\n";
		}
		echo $ending;
	}
	echo "</ConfusaRobot>\n";
}

/* Safe environment? */
assertEnvironment();

/* Is the certificate a legit cert? */
$admin = createAdminPerson();
if(!isset($admin) || !$admin->isAuth()) {
	echo "Not authenticated! Cannot continue<br />\n";
	exit(0);
}
if (false && Config::get_config('debug')) {
	echo "<hr />\n";
	echo "<table class=\"small\">";
	echo "<tr><td><b>Name:</b></td><td>". htmlentities($admin->getName()) ."</td></tr>";
	echo "<tr><td><b>eduPersonPrincipalName:</b></td><td>".htmlentities($admin->getEPPN())."</td></tr>";
	echo "<tr><td><b>CommonName in DN</b></td><td>".htmlentities($admin->getX509ValidCN())."</td></tr>";
	echo "<tr><td><b>email:</b></td><td>".htmlentities($admin->getEmail())."</td></tr>";
	echo "<tr><td><b>Country:</b></td><td>".htmlentities($admin->getNREN()->getCountry())."</td></tr>";
	echo "<tr><td><b>OrganizationalName:</b></td><td>".htmlentities($admin->getSubscriber()->getOrgName())."</td></tr>";
	echo "<tr><td><b>Entitlement:</b></td><td>".htmlentities($admin->getEntitlement())."</td></tr>";
	echo "<tr><td><b>NREN:</b></td><td>".htmlentities($admin->getNREN())."</td></tr>";
	echo "<tr><td><b>Complete /DN:</b></td><td>".htmlentities($admin->getX509SubjectDN())."</td></tr>";
	echo "	<tr><td></td><td></td></tr>";
	echo "	<tr><td><b>Time left</b></td><td>".htmlentities($timeLeft)."</td></tr>";
	echo "<tr><td><b>Time since AuthN</b></td><td>".htmlentities($timeSinceStart)."</td></tr>";
	echo "</table><br />";
	echo "<hr />\n";
}


/* Get list of issued certiticates */
if (isset($_POST['action'])) {
	$action = Input::sanitize($_POST['action']);
} else {
	/* if no action provided, assume the client wants a list of issued certificates. */
	$action = 'cert_list';
}


switch($action) {
case 'cert_list':
	$res = Robot::createCertList($admin);
	printXMLRes($res, 'userlist');
	break;
case 'revoke_list':
	if (!isset($_POST['list'])) {
		echo "No data provided.<br />\n";

	}
	$xml = $_POST['list'];
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
				$res = Robot::parseRevList($value, $admin);
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
