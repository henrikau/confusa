<?php
require_once 'confusa_include.php';
require_once 'Robot.php';
require_once 'mdb2_wrapper.php';
require_once 'cert_lib.php';
require_once 'logger.php';
require_once 'person.php';
require_once 'CA.php';

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
				  ") without certificate. Dropping connection. Make sure apache is configured with SSLVerifyClient optional_no_ca");
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
	global $log_error_code;
	/*
	 * Try to find the certificate in the robot_certs-table. If we have a
	 * match, we have a legit user and create a proxy-admin.
	 *
	 * If the query fails for some reason, we jumb out, returning null
	 */
	$fingerprint = openssl_x509_fingerprint($_SERVER['SSL_CLIENT_CERT'], true);
	if (is_null($fingerprint)) {
		return null;
	}

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
	 * basis for authenticating the person.
	 *
	 * It does not really matter which IdP-map we use, as long as we get one
	 * that points to the correct NREN. This is probably not the 'correct'
	 * way of using the idp_map, but atm, this is the only 'correct' way of
	 * decorating the NREN-object.
	 */
	try {
		/* get admin */
		$ares = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin_id=?",
					    array('text'),
					    array($cert_res[0]['uploaded_by']));

		if (count($ares) != 1) {
			/* no admin found. This should not be possible, but be
			 * safe and test nevertheless */
			return null;
		}

		/* get Subscriber */
		$sres = MDB2Wrapper::execute("SELECT * FROM subscribers WHERE subscriber_id=?",
					     array('text'),
					     array($cert_res[0]['subscriber_id']));
		if (count($sres) != 1) {
			/* No subscriber found */
			return null;
		}

		/* get NREN */
		$nres = MDB2Wrapper::execute("SELECT n.*,im.idp_url FROM nrens n LEFT JOIN idp_map im ON im.nren_id = n.nren_id WHERE n.nren_id=?",
					     array('text'),
					     array($sres[0]['nren_id']));
		if (count($nres) < 1) {
			/* No nrens found at all, which means that the
			 * subscriber is bogus. Since this is a foreign-key
			 * constraint, we've run into a corrupt db. Let's hope
			 * this'll never happen :-) */
			Logger::log_event(LOG_EMERG,
					  "Found subscriber (".
					  $sres[0]['subscriber_id'] . ":" .
					  $sres[0]['name'] .
					  ") without a corresponding NREN (".
					  $sres[0]['nren_id']
					  ."), you have a corrupt database");
		}
	} catch (DBStatementException $dbse) {
		$msg = "[$log_error_code] Problem executing query. Is the database-schema outdated?. ";
		Logger::log_event(LOG_INFO, $msg . " Server said: " . $dbse->getMessage());
		echo $msg . "<br />\nServer said: " . htmlentities($dbse->getMessage()) . "<br />\n";
		return null;
	} catch (DBQueryException $dbqe) {
		/* FIXME */
		$msg = "Could not find owner-details for certificate, probably issues with supplied data. ";
		$msg .= "Admin_id: " . htmlentities($cert_res[0]['uploaded_by']);
		Logger::log_event(LOG_INFO, $msg . " Server said: " . $dbqe->getMessage());
		echo $msg . "<br />\nServer said: " . htmlentities($dbqe->getMessage()) . "<br />\n";
		return null;
	}

	/*
	 * Decorate person.
	 */
	$person = new Person();
	if (isset($ares[0]['admin_name']) && $ares[0]['admin_name'] != "") {
		$person->setName($ares[0]['admin_name']);
	} else {
		$person->setName($ares[0]['admin']);
	}
	try {
		$person->setEPPN($ares[0]['admin']);
	} catch (CriticalAttributeException $cae) {
		echo "[$log_error_code] Problems with setting the eduPersonPrincipalName for robot-admin.<br />\n";
		echo "Check the data in admins (admin_id: " . htmlentities($cert_res[0]['uploaded_by']) . ")<br />\n";
		Logger::log_event(LOG_NOTICE, "[RI] ($log_error_code) Internal error? Suddenly provided admin-eppn is not available.");
		return null;
	}
	$person->setAuth(true);
	$person->setNREN(new NREN($nres[0]['idp_url']));
	$person->setSubscriber(new Subscriber($sres[0]['name'], $person->getNREN()));
	$person->setName($ares[0]['admin_name']);
	$person->setEmail($ares[0]['admin_email']);

	/* Robot authenticated, we can return the person and live happily ever
	 * after */
	Logger::log_event(LOG_NOTICE,
			  "[RI]: Authenticated robot-client via cert $fingerprint belonging to " .
			  $person->getEPPN());
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
	global $admin;

	$xml = new SimpleXMLElement("<ConfusaRobot></ConfusaRobot>");
	$xml->addAttribute("date", date("Y-m-d H:i:s"));
	$xml->addAttribute("subscriber", $admin->getSubscriber()->getOrgName());
	$xml->addAttribute("elementCount", 1);
	$xml->addAttribute("version", "1.0");

	$element = null;
	switch(strtolower($type)) {

	case 'userlist':
		$element = $xml->addChild("userList");
		break;
	case 'revokelist':
		$element = $xml->addChild("revokedCerts");
		break;
	default:
		return;

	}
	if (isset($resArray) && is_array($resArray) && count($resArray) > 0) {
		foreach($resArray as $value) {
			$le = $element->addChild('listElement');
			$le->addAttribute('eppn', htmlentities($value['eppn']));
			if (isset($value['count'])) {
				$le->addAttribute('count', $value['count']);
			}
			if (isset($value['fullDN'])) {
				$le->addAttribute('fullDN', htmlentities($value['fullDN']));
			}
		}
	}

	header ("content-type: text/xml");
	echo $xml->asXML();
}

/* Safe environment? */
assertEnvironment();

/* Is the certificate a legit cert? */
$admin = createAdminPerson();
if(!isset($admin) || !$admin->isAuth()) {
	echo "Not authenticated! Cannot continue<br />\n";
	exit(0);
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
		echo "No data provided.\n";
		exit(0);

	}
	$xml = str_replace("\\", "", $_POST['list']);
	/* Start parsing */
	if (!is_null($xml)) {
		try {
			$parsedXML = new SimpleXMLElement($xml);
		} catch(Exception $e) {
			echo $e->getMessage();
			exit(0);
		}
		$name	= $parsedXML->getName();
		if ($name != "ConfusaRobot") {
			echo "Wrong type of XML. Aborting.\n";
			exit(0);
		}

		foreach ($parsedXML as $key => $value) {
			switch ($key) {
			case 'revocationList':
				$res = Robot::parseRevList($value, $admin);
				break;
			default:
				echo "Unknown type ($key). Are you sure you are following the DTD?\n";
				exit(0);
				break;
			}
		}
	}

	if (! is_null($res)) {
		printXMLRes($res, 'revokeList');
	}
	break;
default:
	echo "Unknown action.<br />\n";
	exit(0);
}

?>
