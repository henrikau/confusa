<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'send_element.php';
require_once 'csv_lib.php';
require_once 'input.php';
require_once 'mdb2_wrapper.php';

class CP_RevokeCertificate extends Content_Page
{
	/*
	 * CRL reason codes according to RFC 3280.
	 *
	 * Those having no real meaning for NREN and institution admins have
	 * been removed from this list.
	 */
	private $nren_reasons = array('unspecified',
				      'keyCompromise',
				      'affiliationChanged',
				      'superseded',
				      'certificateHold',
				      'privilegeWithdrawn',
				      'aACompromise');

	function __construct()
	{
		parent::__construct("Revoke Certificate(s)", true);
		Framework::sensitive_action();
	}

	function __destruct()
	{
		parent::__destruct();
	}

	/**
	 * pre_process - if the user chose revocation action, forward here there
	 * before rendering anything on the page
	 */
	public function pre_process($person)
	{
		parent::pre_process($person);

		if(isset($_GET['revoke'])) {
			switch($_GET['revoke']) {

				/* the revoke single is done via a GET
				 * request. This is to allow for dedicated urls
				 * for revocation to be used. */
			case 'revoke_single':
				$order_number	= Input::sanitize($_GET['order_number']);
				$reason		= Input::sanitize($_GET['reason']);
				try {
					if (!isset($order_number) || !isset($reason)) {
						Framework::error_output("Revoke Certificate: Errors with parameters, not set properly");
					} elseif (!$this->checkRevocationPermissions($order_number)) {
						Framework::error_output("You do not have the permission to revoke that certificate!");
					} elseif (!$this->certManager->revoke_cert($order_number, $reason)) {
						Framework::error_output("Cannot revoke yet ($order_number) for supplied reason: $reason");
					} else {
						Framework::message_output("Certificate ($order_number) successfully revoked.");
					}
				} catch (ConfusaGenException $cge) {
					Framework::error_output("Revocation failed, the following problem was reported: " .
											$cge->getMessage());
				}
				break;
			default:
				Framework::error_output("Unknown operation received in parameter!");
				break;
			}
		}

		if (isset($_POST['revoke_operation'])) {
			switch($_POST['revoke_operation']) {
			case 'revoke_by_cn':
				try {
					$this->revoke_certs(Input::sanitize($_POST['common_name']), Input::sanitize($_POST['reason']));
				} catch (ConfusaGenException $cge) {
					Framework::error_output("Could not revoke certificates because of the " .
											"following problem: " . $cge->getMessage());
				}
				break;

			case 'revoke_by_list':
				try {
					$this->revoke_list(Input::sanitize($_POST['reason']));
				} catch (ConfusaGenException $cge) {
					Framework::error_output("Could not revoke certificates because of the " .
											"following problem: " . $cge->getMessage());
				}
				break;

			default:
				break;
			}
		}
	}

	public function process()
	{
		try {
			if ($this->person->inAdminMode()) {
				$this->showAdminRevokeTable();
			} else {
				if (!isset($_GET['revoke'])) {
					$this->showNonAdminRevokeTable();
				}
			}

		$this->tpl->assign('textual', $textual);
		$this->tpl->assign('content', $this->tpl->fetch('revoke_certificate.tpl'));

		} catch (ConfusaGenException $cge) {
			Framework::error_output("Can not display revocation options! Server " .
									"said: " . $cge->getMessage());
		}
	}

	/**
	 * showAdminRevokeTable - Render a revocation interface for the sublime of users.
	 *
	 * For NREN admins it is planned to restrict the permission to revoke to an
	 * incident response team. Revocation can either take place
	 * by a wildcard-search for an ePPN or by uplading a CSV with ePPNs which
	 * will be searched wrapped into wildcards
	 */
	private function showAdminRevokeTable()
	{
		if (!$this->person->isAdmin()) {
			Logger::log_event(LOG_ALERT, "User " . $this->person->getX509ValidCN() . " allowed to set admin-mode, but is not admin");
			Framework::error_output("Impossible condition. NON-Admin user in admin-mode!");
			return;
		}

		/* Get the right subscriber for which revocation should happen */
		if ($this->person->isNRENAdmin()) {
			$subscribers = $this->getNRENSubscribers($this->person->getNREN());

			if (isset($_POST['subscriber'])) {
				$subscriber = Input::sanitize($_POST['subscriber']);

				/* check if the given subscriber is a legitimate subscriber
				 * for the given NREN
				 */
				if (array_search($subscriber, $subscribers) === false) {
					Logger::log_event(LOG_NOTICE, "[nadm] Administrator for NREN " .
						$this->person->getNREN() . ", contacting us from " .
						$_SERVER['REMOTE_ADDR'] . " tried to revoke certificates for " .
						"subscriber $subscriber, which is not part of the NREN!");
					Framework::error_output("Subscriber $subscriber is not part of " .
									"your NREN!");
					return;
				}
			} else {
				/* if no preferred subscriber is set, use the
				 * subscriber where the NREN-admin belongs.
				 * If, for some strange reason, the NREN has no
				 * Subscriber set, not even via the IdP, use the
				 * first in the list.
				 */
				$subscriber = $this->person->getSubscriberIdPName();
				if (!isset($subscriber) || $subscriber=="") {
					echo "[DEBUG] ".__FILE__ . ":" . __LINE__;
					echo " no subscriber set, getting the first available.<br />\n";
					$subscriber = $subscribers[0];
				}
			}

			$this->tpl->assign('subscriber', htmlentities($subscriber));
			$this->tpl->assign('subscribers', $subscribers);
		} else {
			$subscriber = $this->person->getSubscriberIdPName();
		}

		$this->tpl->assign('file_name', 'eppn_list');

		/* No need to do processing */
		if (!isset($_POST['revoke_operation']))
			return;

		/* Test for revoke-commands */
		switch($_POST['revoke_operation']) {
		/* when we want so search for a particular certificate
		 * to revoke. */
		case 'search_by_cn':
			$common_name = Input::sanitize($_POST['search']);
			$this->searchCertsDisplay($common_name, $subscriber);
			break;
		case 'search_by_list':
			$this->search_list_display('eppn_list', $subscriber);
			break;

		default:
			break;
		}

	} /* end showAdminRevokeTable */

	/**
	 * Get the subscribers that belong to a certain NREN.
	 * TODO: Move that functionality to lib/, it is needed in more graphical
	 * 		classes.
	 * @param $nren The NREN for which the subscribers are queried
	 */
	private function getNRENSubscribers($nren)
	{
		$query = "SELECT subscriber FROM nren_subscriber_view WHERE nren=?";

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch(DBStatementException $dbse) {
			Framework::error_output("Cannot retrieve subscriber from database!<BR /> " .
				"Probably wrong syntax for query, ask an admin to investigate." .
				"Server said: " . $dbse->getMessage());
			return null;
		} catch(DBQueryException $dbqe) {
			Framework::error_output("Query failed. This probably means that the values passed to the "
								. "database are wrong. Server said: " . $dbqe->getMessage());
			return null;
		}

		$subscribers = array();

		if (count($res) > 0) {

			foreach($res as $row) {
				$subscribers[] = $row['subscriber'];
			}
		}

		return $subscribers;
	}

	/**
	 * showNonAdminRevokeTable() - certificate revocation for the ordinary man
	 *
	 * Not being blessed with the privileges that institution-adminship offers,
	 * the normal user will only be given the possibility to revoke the full
	 * set of her own certificates.
	 */
	 private function showNonAdminRevokeTable()
	{
		$this->searchListDisplay($this->person->getEPPN(), $this->person->getSubscriberOrgName());
	}

	/**
	 * searchListDisplay() find and display a particular certificate
	 *
	 * Perform the search for a certain common name. Use the organization of the
	 * person as a search restriction. Display the result along with a revoke-
	 * option in a list grouped by different certificate owners.
	 *
	 * @param $common_name The common-name that is searched for. Will be automatically
	 *                     turned into a wildcard
	 * @param $subscriber  The name of the subscriber to which the search is
	 *                     constrained
	 *
	 * @return void
	 */
	private function searchListDisplay($common_name, $subscriber)
	{
		if (isset($_SESSION['auth_keys'])) {
			unset($_SESSION['auth_keys']);
		}

		$common_name = "%" . $common_name . "%";

		$certs = $this->certManager->get_cert_list_for_persons($common_name, $subscriber);

		if (count($certs) > 0) {
			/* get the certificate owner/order number pairs into a ordering that
			 * permits us to send the order-numbers for each certificate owner
			 * to the revocation method */
			foreach($certs as $row) {
				$owners[] = $row['cert_owner'];
				/* Sanitation here is like a form of encoding, so the cert-owner can
				 * serve as an identifier that can be passed to the page and back
				 * via a POST subsequently. If we didn't sanitize here, after coming
				 * back via the POST the cert_owner wouldn't match the stored one
				 * any more.
				 */
				$orders[Input::sanitize($row['cert_owner'])][] = $row['auth_key'];
			}

			/* total number of occurences for every owner */
			$stats = array_count_values($owners);
			$_SESSION['auth_keys'] = $orders;
			$owners = array_unique($owners);
			$this->tpl->assign('owners', $owners);
			$this->tpl->assign('stats', $stats);
			$this->tpl->assign('revoke_cert', true);
			$this->tpl->assign('nren_reasons', $this->nren_reasons);
			$this->tpl->assign('selected', 'unspecified');
		}
	}

	/*
	 * Revoke all the certificates of person with common_name $common_name with the supplied reason.
	 * Upon searching for certificates, the list of auth_keys associated with the
	 * certificate owner with the given sanitized common_name is stored in the
	 * session and retrieved again upon revocation.
	 *
	 * @param $common_name The common name of the certificate owner
	 * @param $reason The reason for revocation as defined in RFC 3280
	 *
	 */
	private function revoke_certs($common_name, $reason)
	{
		if (isset($_SESSION['auth_keys'])) {
			$auth_keys = $_SESSION['auth_keys'];
			unset($_SESSION['auth_keys']);
		} else {
			Framework::error_output("Lost the certificate identifiers associated with " .
									"common name $common_name during the session! Please try again!");
		}

		$auth_key_list = $auth_keys[$common_name];

		if (array_search($reason, $this->nren_reasons) === FALSE) {
			Framework::error_output("Encountered an unknown revocation " .
										  "reason!"
			);
		}

		$num_certs = count($auth_key_list);
		$num_certs_revoked = 0;
		Logger::log_event(LOG_INFO, "Trying to revoke $num_certs certificates." .
									"Administrator contacted us from " .
									$_SERVER['REMOTE_ADDR']
		);

		foreach($auth_key_list as $auth_key) {
			try {
				if (!$this->certManager->revoke_cert($auth_key, $reason)) {
					Framework::error_output("Could not revoke certificate properly.");
				} else {
					$num_certs_revoked = $num_certs_revoked + 1;
				}
			} catch (ConfusaGenException $cge) {
				Framework::error_output($cge->getMessage());
			}
		}

		Logger::log_event(LOG_NOTICE, "Successfully revoked $num_certs_revoked certificates out of $num_certs. " .
									  "Administrator contacted us from " .
									  $_SERVER['REMOTE_ADDR']
		);
		Framework::message_output("Successfully revoked $num_certs_revoked out of $num_certs certificates!");
	}

	/**
	 * Revoke a list of certificates possibly belonging to more than one end-entity
	 * based on an array of auth_keys stored in the session. Based on the number of
	 * certificates that are going to be revoked, this may take some time.
	 *
	 * @param string $reason The reason for revocation (as in RFC 3280)
	 *
	 */
	private function revoke_list($reason)
	{
		$auth_keys = array();
		if (isset($_SESSION['auth_keys'])) {
			$auth_keys = $_SESSION['auth_keys'];
			unset($_SESSION['auth_keys']);
		} else {
			Framework::error_output("Lost session! Please log-out of Confusa, " .
									"log-in again and try again!\n");
		}

		if (array_search($reason, $this->nren_reasons) === false) {
			Framework::error_output("Unknown reason for certificate revocation!");
		}

		$num_certs = count($auth_keys);
		$num_certs_revoked = 0;

		Logger::log_event(LOG_INFO, "Trying to revoke $num_certs certificates." .
									"Administrator contacted us from " .
									$_SERVER['REMOTE_ADDR'] .
									" in a bulk (list) revocation request."
		);

		foreach($auth_keys as $auth_key) {
			try {
				if (!$this->certManager->revoke_cert($auth_key, $reason)) {
					Framework::error_output("Could not revoke certificate $auth_key.");
				} else {
					$num_certs_revoked = $num_certs_revoked + 1;
				}
			} catch (ConfusaGenException $cge) {
				Framework::error_output($cge->getMessage());
			}
		}

		Logger::log_event(LOG_INFO, "Successfully revoked $num_certs_revoked certificates out of $num_certs. " .
									"Administrator contacted us from " .
									$_SERVER['REMOTE_ADDR'] .
									" in a bulk (list) revocation request."
		);
		Framework::message_output("Successfully revoked $num_certs_revoked certificates out of $num_certs!");
	}

	/**
	 * Display a list of distinguished names whose certificates will be revoked
	 * based on an uploaded CSV with a list of eduPersonPrincipalNames. Offer the
	 * possibility to revoke these certificates.
	 *
	 * @param $eppn_file string The name of the $_FILES parameter containining the
	 *                          CSV of eduPersonPrincipalNames
	 * @param $subscriber string The name of the subscriber by which the search is
	 * 							scoped
	 *
	 */
	private function search_list_display($eppn_file, $subscriber)
	{
		/* These can become a *lot* of auth_keys/order_numbers. Thus, save the list
		 * of auth_keys preferrably in the session, otherwise it will take forever
		 * to download the site and I am not sure if it is such a good idea to send
		 * an endless list of auth_keys as hidden parameters
		 * to the user and then from there back again with a POST to the server
		 */
		if (isset($_SESSION['auth_keys'])) {
			unset($_SESSION['auth_keys']);
		}

		$csvl = new CSV_Lib($eppn_file);
		$eppn_list = $csvl->get_csv_entries();
		$certs = array();
		$auth_keys = array();

		foreach($eppn_list as $eppn) {
			$eppn = Input::sanitize($eppn);
			$eppn_certs = $this->certManager->get_cert_list_for_persons($eppn, $subscriber);
			$certs = array_merge($certs, $eppn_certs);
		}

		if (count($certs) > 0) {
			/* get the certificate owner/order number pairs into a ordering that
			 * permits us to send the order-numbers for each certificate owner
			 * to the revocation method */
			foreach($certs as $row) {
				$owners[] = $row['cert_owner'];
				$auth_keys[] = $row['auth_key'];
			}

			$owners = array_unique($owners);
			$_SESSION['auth_keys'] = $auth_keys;
			$this->tpl->assign('owners', $owners);
			$this->tpl->assign('revoke_list', true);
			$this->tpl->assign('nren_reasons', $this->nren_reasons);
			$this->tpl->assign('selected', 'unspecified');
		}
	}

	/**
	 * Check if the person that called "revoke" on auth_key may revoke the respective
	 * certificate.
	 *
	 * NREN-Admin: Is the certificate issued to one of the subscribers in the
	 * 			constituency of the NREN?
	 * Subscriber-Admin: Is the certificate issued to the organization of the admin?
	 * "Normal" Person: Is the certificate issued to the person herself?
	 *
	 * @param $auth_key mixed The auth_key for which to check
	 * @return boolean true, if revocation of the passed key is permitted
	 */
	private function checkRevocationPermissions($auth_key)
	{
		try {
			$info = $this->certManager->getCertInformation($auth_key);

			if (is_null($info)) {
				Framework::error_output("Certificate with the given auth_key/order-number " .
					"not found! Maybe you misspelled the order-number or auth_key?");
				return false;
			}

			/* if the API is in test mode, inject the TEST CERTIFICATE strings into the
			 * organization name that is queried for */
			$test_prefix = Config::get_config('ca_mode') == CA_ONLINE &&
							Config::get_config('capi_test');

			/**
			 * Check if the NREN admin may revoke the certificate. That holds only
			 * if the organization name in the certificate matches one of the institutions
			 * in the constituency of the NREN
			 */
			if ($this->person->isNRENAdmin()) {
				$subscribers = $this->getNRENSubscribers($this->person->getNREN());

				foreach ($subscribers as $subscriber) {
					if ($test_prefix) {
						$subscriber = ConfusaConstants::$CAPI_TEST_O_PREFIX . $subscriber;
					}

					if ($subscriber === $info['organization']) {
						return true;
					}
				}
			/** check if the subscriber admin may revoke the certificate.
			 * She may do so only if the organization name in the certificate matches
			 * her own organization
			 */
			} else if ($this->person->isSubscriberAdmin() || $this->person->isSubscriberSubAdmin()) {
				$subscriber = $this->person->getSubscriberOrgName();
				if ($test_prefix) {
					$subscriber = ConfusaConstants::$CAPI_TEST_O_PREFIX . $subscriber;
				}

				if ($subscriber === $info['organization']) {
					return true;
				}
			/*
			 * Check if an individual user may revoke a certificate. That holds only
			 * if the CN of the certificate matches the (constructed) CN of the user and
			 * the organization the subscriber-organization of the user
			 *
			 * */
			} else {
				$cn = $this->person->getX509ValidCN();
				$subscriber = $this->person->getSubscriberOrgName();
				if ($test_prefix) {
					$cn = ConfusaConstants::$CAPI_TEST_CN_PREFIX . $cn;
					$subscriber = ConfusaConstants::$CAPI_TEST_O_PREFIX . $subscriber;
				}

				if (($info['cert_owner'] === $cn) && ($info['organization'] === $subscriber)) {
					return true;
				}
			}

		} catch (ConfusaGenException $cge) {
			Framework::error_output("Retrieving certificate information failed: " .
							$cge->getMessage());
			Logger::log_event(LOG_INFO, "[nadm][sadm][norm] Revoking certificate " .
				"with key $auth_key failed, because permissions could not be " .
				"determined!");
		}

		return false;
	} /* end checkRevocationPermissions */
}

$fw = new Framework(new CP_RevokeCertificate());
$fw->start();

?>
