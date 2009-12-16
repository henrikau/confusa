<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'content_page.php';

/**
 * Manage the mapping from the attributes that the IdPs sent to the keys
 * that are consumed by Confusa. That can be done by NREN and subscriber admins.
 *
 */
class CP_Attributes extends Content_Page
{
	function __construct()
	{
		parent::__construct("Attribute mapping", true);
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		if (!$person->isNRENAdmin() && !$person->isSubscriberAdmin()) {
			return;
		}

		if (isset($_POST['attributes_operation'])) {
			switch($_POST['attributes_operation']) {
			case 'update_map':
				$cn		= Input::sanitizeText($_POST['cn']);
				$mail		= Input::sanitizeText($_POST['mail']);
				$entitlement	= Input::sanitizeText($_POST['entitlement']);

				if ($this->person->isNRENAdmin()) {
					$epodn		= Input::sanitizeText($_POST['epodn']);
					if ($this->person->getNREN()->saveMap($this->person->getEPPNKey(), $epodn, $cn, $mail, $entitlement)) {
						Framework::success_output("Updated map successfully. You will have to logout and login again " .
						                          "to see the the effects of the changed map!");
					}
				} else if ($this->person->isSubscriberAdmin()) {
					try {
						$result = $this->person->getSubscriber()->saveMap($cn, $mail, $entitlement);
					} catch (DBQueryException $dbqe) {
						Framework::error_output("Could not update the subscriber-mapping, probably due to a " .
						                        "problem with the map keys. You sent:<br />CN: " . htmlentitities($cn) .
						                        "<br />Mail: " . htmlentities($mail) .
						                        "<br />Entitlement: " . htmlentities($entitlement) .
						                        "<br />Server said: " . htmlentities($dbqe->getMessage()));
						Logger::log_event(LOG_NOTICE, __FILE__ . ", " . __LINE__ . ": " . $dbqe->getMessage());
					} catch (DBStatementException $dbse) {
						Framework::error_output("Could not update the subscriber-mapping, probably due to a " .
						                        "problem with the server-configuration. Server said: " .
						                        htmlentities($dbse->getMessage()));
						Logger::log_event(LOG_NOTICE, __FILE__ . ", " . __LINE__ . ": " . $dbse->getMessage());
					}
					if ($result === true) {
						Framework::success_output("Updated map successfully. You will have to logout and login again " .
						                          "to see the the effects of the changed map!");
					}
				}
				break;
			default:
				Framework::error_output("Unknown operation chosen on attributes mask!");
				break;
			}
		}
	}

	public function process()
	{
		if (!$this->person->isNRENAdmin() && !$this->person->isSubscriberAdmin()) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->getX509ValidCN() . " tried to access the NREN-area");
			$this->tpl->assign('reason', 'You are not an NREN or subscriber-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}

		if (isset($_GET['attr_value'])) {
			/* no need for sanitization, only used in array lookup & does not go
			 * into the DB
			 */
			$this->handleAttrValueAJAX($_GET['attr_value']);
			return;
		}

		$this->tpl->assign('handle_map',	true);

		if ($this->person->isNRENAdmin()) {
			$map = $this->person->getNREN()->getMap();
		} else if ($this->person->isSubscriberAdmin()) {
			/* This will get the Subscriber-map if available,
			 * otherwise it will return the NREN-map. */
			$map = $this->person->getMap();
		}
		$session = $this->person->getSession();
		if (isset($session)) {
			if (isset($map['epodn'])) {
				$orgName = $session->getAttribute($map['epodn']);
				$this->tpl->assign('epodn', implode(', ', $orgName));
			} else {
				$this->tpl->assign('epodn', '');
			}

			if (isset($map['cn'])) {
				$cn = $session->getAttribute($map['cn']);
				$this->tpl->assign('cn', implode(', ', $cn));
			} else {
				$this->tpl->assign('cn', '');
			}

			if (isset($map['mail'])) {
				$mail = $session->getAttribute($map['mail']);
				$this->tpl->assign('mail', implode(', ', $mail));
			} else {
				$this->tpl->assign('mail', '');
			}

			if (isset($map['entitlement'])) {
				$entitlement = $session->getAttribute($map['entitlement']);
				$this->tpl->assign('entitlement', implode(', ', $entitlement));
			} else {
				$this->tpl->assign('entitlement', '');
			}
		}
		$this->tpl->assign('map',		$map);
		$this->tpl->assign('keys',		AuthHandler::getAuthManager($this->person)->getAttributeKeys($this->person->isNRENAdmin()));
		$this->tpl->assign('content', 	$this->tpl->fetch('attributes.tpl'));
	}

		/**
	 * Return the value for the key to an IdP attribute (if defined)
	 *
	 * @param attr_key string The key of the attribute
	 * @return string The value for the supplied attribute key
	 */
	private function handleAttrValueAJAX($attr_key)
	{
		if (empty($attr_key)) {
			exit(0);
		}

		$session = $this->person->getSession();
		if (!is_null($session)) {
			$attr_value = @implode(", ", $session->getAttribute($attr_key));
			echo htmlentities($attr_value, ENT_COMPAT, "UTF-8");
		}
		exit(0);
	}
}

$fw = new Framework(new CP_Attributes());
$fw->start();

?>
