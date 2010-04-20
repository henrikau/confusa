<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'Content_Page.php';
require_once 'Confusa_Auth.php';
require_once 'AuthHandler.php';

/**
 * Manage the mapping from the attributes that the IdPs sent to the keys
 * that are consumed by Confusa. That can be done by NREN and subscriber admins.
 *
 */
class CP_Attributes extends Content_Page
{
	function __construct()
	{
		parent::__construct("Attribute mapping", true, "attributes");
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

				/* only NREN-admin can change the mapping for
				 * - organization-identifier
				 * - entitlement
				 */
				if ($this->person->isNRENAdmin()) {
					$epodn		= Input::sanitizeText($_POST['epodn']);
					$entitlement	= Input::sanitizeText($_POST['entitlement']);
					if ($this->person->getNREN()->saveMap($this->person->getEPPNKey(),
									      $epodn,
									      $cn,
									      $mail,
									      $entitlement)) {
						Framework::success_output($this->translateTag('l10n_suc_updmap', 'attributes'));
					}
				} else if ($this->person->isSubscriberAdmin()) {
					try {
						$result = $this->person->getSubscriber()->saveMap($this->person->getEPPNKey(),
												  $cn,
												  $mail);
					} catch (DBQueryException $dbqe) {
						Framework::error_output($this->translateTag('l10n_err_updmap1', 'attributes') . "<br />" .
						                        $this->translateTag('l10n_label_cn', 'attributes')
						                        .  ": " . htmlentities($cn) . "<br />" .
						                        $this->translateTag('l10n_label_mail', 'attributes')
						                        . ": " . htmlentities($mail) . "<br />" .
						                        $this->translateMessageTag('err_servsaid') . " " .
						                        htmlentities($dbqe->getMessage()));
						Logger::log_event(LOG_NOTICE, __FILE__ . ", " . __LINE__ . ": " . $dbqe->getMessage());
					} catch (DBStatementException $dbse) {
						Framework::error_output("Could not update the subscriber-mapping, probably due to a " .
						                        "problem with the server-configuration. Server said: " .
						                        htmlentities($dbse->getMessage()));
						Logger::log_event(LOG_NOTICE, __FILE__ . ", " . __LINE__ . ": " . $dbse->getMessage());
					}
					if ($result === true) {
						Framework::success_output($this->translateTag('l10n_suc_updmap', 'attributes'));
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

		$auth = AuthHandler::getAuthManager($this->person);

		if (isset($map['epodn'])) {
			$epodn = implode(', ', $auth->getAttributeValue($map['epodn']));
			$this->tpl->assign('epodn', $epodn);
		} else {
			$this->tpl->assign('epodn', '');
		}

		if (isset($map['cn'])) {
			$cn = implode(', ', $auth->getAttributeValue($map['cn']));
			$this->tpl->assign('cn', $cn);
		} else {
			$this->tpl->assign('cn', '');
		}

		if (isset($map['mail'])) {
			$mail = implode(', ', $auth->getAttributeValue($map['mail']));
			$this->tpl->assign('mail', $mail);
		} else {
			$this->tpl->assign('mail', '');
		}

		if (isset($map['entitlement'])) {
			$entitlement = implode(', ', $auth->getAttributeValue($map['entitlement']));
			$this->tpl->assign('entitlement', $entitlement);
		} else {
			$this->tpl->assign('entitlement', '');
		}

		$this->tpl->assign('map',	$map);
		$this->tpl->assign('keys',	AuthHandler::getAuthManager($this->person)->getAttributeKeys($this->person->isNRENAdmin()));
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

		$auth = AuthHandler::getAuthManager($this->person);
		$attr_value = @implode(", ", $auth->getAttributeValue($attr_key));
		echo htmlentities($attr_value, ENT_COMPAT, "UTF-8");
		exit(0);
	}
}

$fw = new Framework(new CP_Attributes());
$fw->start();

?>
