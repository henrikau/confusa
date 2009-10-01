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
				      'superseeded',
				      'certificateHold',
				      'privilegeWithdrawn',
				      'aACompromise');

	function __construct()
	{
		parent::__construct("Revoke Certificate(s)", true);
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
		Framework::sensitive_action();

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
					}
					elseif (!$this->certManager->revoke_cert($order_number, $reason)) {
						Framework::error_output("Cannot revoke yet ($order_number) for supplied reason: $reason");
					} else {
						Framework::message_output("Certificate ($order_number) successfully revoked.");
					}
				} catch (ConfusaGenException $cge) {
					Framework::error_output("Revocation failed, the following problem was reported: " .
											$cge->getMessage());
				}
				break;

			case 'do_revoke':

				try {
					$this->revoke_certs(Input::sanitize($_POST['order_numbers']), Input::sanitize($_POST['reason']));
				} catch (ConfusaGenExcpetion $cge) {
					Framework::error_output("Could not revoke certificates because of the " .
											"following problem: " . $cge->getMessage());
				}
				break;

			case 'do_revoke_list':
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
				$this->normal_revoke();
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
		}

		/* Test access-rights */
		if (!$this->person->isAdmin())
			Framework::error_output("Insufficient rights for revocation!");

		/* Get the right subscriber for which revocation should happen */
		if ($this->person->isNRENAdmin()) {
			$subscribers = $this->getNRENSubscribers($this->person->getNREN());

			if (isset($_POST['subscriber'])) {
				$subscriber = Input::sanitize($_POST['subscriber']);

				/* check if the given subscriber is a legitimate subscriber
				 * for the given NREN
				 */
				if (!array_search($subscriber, $subscribers)) {
					Logger::log_event(LOG_NOTICE, "[nadm] Administrator for NREN " .
						$this->person->getNREN() . ", contacting us from " .
						$_SERVER['REMOTE_ADDR'] . " tried to revoke certificates for " .
						"subscriber $subscriber, which is not part of the NREN!");
					Framework::error_output("Subscriber $subscriber is not part of " .
									"your NREN!");
					return;
				}
			} else {
				$subscriber = $subscribers[0];
			}

			$this->tpl->assign('subscriber', htmlentities($subscriber));
			$this->tpl->assign('subscribers', $subscribers);
		} else {
			$subscriber = $this->person->getSubscriberOrgName();
		}

		$this->tpl->assign('file_name', 'eppn_list');

		/* No need to do processing */
		if (!isset($_GET['revoke']))
			return;

		/* Test for revoke-commands */
		switch($_GET['revoke']) {
		/* when we want so search for a particular certificate
		 * to revoke. */
		case 'search_display':
			$common_name = Input::sanitize($_POST['search']);
			$this->search_certs_display($common_name, $subscriber);
			break;
		case 'search_list_display':
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
	 * normal_revoke() - certificate revocation for the ordinary man
	 *
	 * Not being blessed with the privileges that institution-adminship offers,
	 * the normal user will only be given the possibility to revoke the full
	 * set of her own certificates.
	 */
	 private function normal_revoke()
	{
		$this->search_certs_display($this->person->getEPPN());
	}

	/**
	 * search_certs_display() - find and display a particular certificate
	 *
	 * Perform the search for a certain common name. Use the organization of the
	 * person as a search restriction. Display the result along with a revoke-
	 * option in a list grouped by different certificate owners.
	 *
	 * @param $common_name The common-name that is searched for. Will be automatically
	 *                     turned into a wildcard
	 * @param $subscriber The name of the subscriber to which the search is constrained
	 */
	private function search_certs_display($common_name, $subscriber)
	{
		$common_name = "%" . $common_name . "%";

		$certs = $this->certManager->get_cert_list_for_persons($common_name, $subscriber);

		if (count($certs) > 0) {
			/* get the certificate owner/order number pairs into a ordering that
			 * permits us to send the order-numbers for each certificate owner
			 * to the revocation method */
			foreach($certs as $row) {
				$owners[] = $row['cert_owner'];
				$orders[$row['cert_owner']][] = array($row['auth_key'], $row['valid_untill']);
			}

			$owners = array_unique($owners);
			$this->tpl->assign('owners', $owners);
			$this->tpl->assign('revoke_cert', true);
			$this->tpl->assign('orders', $orders);
			$this->tpl->assign('nren_reasons', $this->nren_reasons);
			$this->tpl->assign('selected', 'unspecified');
		}
	}

	/*
	 * Revoke all the certificates in $auth_key_list with the supplied reason
	 *
	 * @param $auth_key_list The references to the certificates that are to be
	 *                       revoked
	 * @param $reason The reason for revocation as defined in RFC 3280
	 *
	 */
	private function revoke_certs($auth_key_list, $reason)
	{

		$auth_key_list = $this->sanitize($auth_key_list);

		if (array_search($reason, $this->nren_reasons) === FALSE) {
			Framework::error_output("Encountered an unknown revocation " .
										  "reason!"
			);
		}

		$num_certs = count($auth_key_list);

		Logger::log_event(LOG_INFO, "Trying to revoke $num_certs certificates." .
									"Administrator contacted us from " .
									$_SERVER['REMOTE_ADDR']
		);

		foreach($auth_key_list as $auth_key) {
			$this->certManager->revoke_cert($auth_key, $reason);
		}

		Logger::log_event(LOG_NOTICE, "Successfully revoked $num_certs certificates." .
									  "Administrator contacted us from " .
									  $_SERVER['REMOTE_ADDR']
		);
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

		Logger::log_event(LOG_INFO, "Trying to revoke $num_certs certificates." .
									"Administrator contacted us from " .
									$_SERVER['REMOTE_ADDR'] .
									" in a bulk (list) revocation request."
		);

		foreach($auth_keys as $auth_key) {
			$this->certManager->revoke_cert($auth_key, $reason);
		}

		Logger::log_event(LOG_INFO, "Successfully revoked $num_certs certificates." .
									"Administrator contacted us from " .
									$_SERVER['REMOTE_ADDR'] .
									" in a bulk (list) revocation request."
		);
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
			$eppn = $this->sanitize_eppn($eppn);
			$eppn = "%" . $eppn . "%";
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
	 * Remove anything that could be dangerous from user input.
	 * Common-name search patterns should contain only [a-z][0-9] @.
	 * So all inputs can be limited to [a-z][0-9] @.
	 *
	 * @param $input The input which is going to be sanitized
	 */
	private function sanitize($input)
	{
	  if (is_array($input)) {
		foreach($input as $var=>$val) {
		  $output[$var] = $this->sanitize($val);
		}
	  }
	  /* also allow the wildcard character and the e-mail character*/
	  $output = preg_replace('/[^a-z0-9 @.]+/i','',$input);
	  return $output;
	}

	/**
	 * Limit the eduPersonPrincipalName to a set of expected characters.
	 *
	 * @param mixed $eppn An eduPersonPrincipalName or an array therof
	 *
	 * @return The sanitized string/array
	 */
	private function sanitize_eppn($eppn)
	{
		if (is_array($eppn)) {
			foreach($eppn as $var=>$val) {
			  $output[$var] = sanitize_eppn($val);
			}
		}
	  /* also allow the the e-mail characters @, . and _ */
	  $output = preg_replace('/[^a-z0-9@._]+/','',$eppn);
	  return $output;
	}
}

$fw = new Framework(new CP_RevokeCertificate());
$fw->start();

?>
