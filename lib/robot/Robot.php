<?php
require_once 'pw.php';
require_once 'person.php';
require_once 'logger.php';
require_once 'mdb2_wrapper.php';
class Robot
{
	private static $log_error_code;
	private static function getError()
	{
		if (!isset(Robot::$log_error_code)) {
			Robot::$log_error_code = create_pw(8);
		}
		return Robot::$log_error_code;
	}


	/**
	 * Createcertlist() Create a list of all valid certificates for the given subscriber
	 *
	 * The function will log the number of certificates found as well, but only the
	 * total number and the number of different users.
	 *
	 * @return Array the list of users and the number of (valid) certificates each user has
	 */
	static function createCertList($admin)
	{
		$ca = CAHandler::getCA($admin);
		$list = $ca->getCertListForPersons("%", $admin->getSubscriber()->getOrgName());
		$res = array();
		$found_certs = 0;
		$found_users = 0;
		if (isset($list) && is_array($list) && count($list) > 0) {
			foreach($list as $value) {
				/* cert is for instance not set when using the Comodo CA */
				if (isset($value['cert'])) {
					$cert = openssl_x509_parse(openssl_x509_read($value['cert']), false);
					$eppn_array = explode(" ", $value['cert_owner']);
					$eppn = $eppn_array[count($eppn_array) - 1];
				} else {
					$cert = array();
					/* Comodo has the full DN as the cert_owner */
					$cert['name'] = $value['cert_owner'];
					$cert_name = $cert['name'];
					$cn_start = stripos($cert_name, 'CN=');
					$cn_end = stripos($cert_name, ',', $cn_start);
					$cn_substr=substr($cert_name, $cn_start, $cn_end);
					$eppn_array = explode(" ", $cn_substr);
					$eppn = $eppn_array[count($eppn_array) -1];
				}

				if (isset($res[$eppn])) {
					if ($res[$eppn]['fullDN'] != $cert['name']) {
						$msg  =  "Several certificates with identical names ($eppn) but different DN";
						$msg .= " " . $res[$eppn]['fullDN']."vs. ".$cert['name'].".";
						Logger::log_event(LOG_ALERT, $msg);
						continue;
					}
					$res[$eppn]['count'] = $res[$eppn]['count'] + 1;
				} else {
					$res[$eppn] = array('eppn' => $eppn, 'fullDN' => $cert['name'], 'count' => '1');
					$found_users = $found_users + 1;
				}
				$found_certs = $found_certs + 1;
			}
		}
		Logger::log_event(LOG_NOTICE, "Created a list of $found_certs valid certificates for $found_users " .
				  "different user(s) in subscriber " . $admin->getSubscriber()->getOrgName());
		return $res;
	} /* end createCertList */


	/**
	 * parseRevList() work through a list of eppns and revoke certificates for those users
	 *
	 * @param list Array list of ePPNs for users to revoke
	 * @param admin Person the admin owning the client-certiticate
	 *
	 * @return Array of a list of persons coupled to the number of revoked
	 * certificates.
	 */
	static function parseRevList($list, $admin)
	{
		$revokedUsers = array();
		$ca = CAHandler::getCA($admin);
		foreach ($list as $value) {

			/* Get eppn from value*/
			$eppn = $value['eppn'];
			if (!isset($eppn) || $eppn == "") {
				echo "Need eppn. This is a REQUIRED attribute.<br />\n";
				break;
			}
			/* Search after matches for cn and subscriber */
			$list = $ca->getCertListForPersons($eppn, $admin->getSubscriber()->getOrgName());
			$count = 0;
			if (count($list) > 0) {
				foreach ($list as $key => $value) {
					try {
						if ($ca->revokeCert($value['auth_key'], "privilegeWithdrawn")) {
							$count = $count + 1;
						}

					} catch (CGE_KeyRevokeException $kre) {
						echo htmlentities($kre->getMessage()) . "<br />\n";
					}
				}
			}
			$revokedUsers[] = array('eppn' => $eppn, 'count' => $count);
		}
		return $revokedUsers;
	} /* end parseRevList */
} /* end class Robot */
?>
