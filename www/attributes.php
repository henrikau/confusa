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
	public function pre_process($person)
	{
		parent::pre_process($person);

		if (!$person->isNRENAdmin() && !$person->isSubscriberAdmin()) {
			return;
		}

		if (isset($_POST['attributes_operation'])) {
			switch($_POST['attributes_operation']) {
			case 'update_map_nren':
				$epodn		= $_POST['epodn'];
				$cn		= $_POST['cn'];
				$mail		= $_POST['mail'];
				$entitlement	= $_POST['entitlement'];
				if ($this->updateMapNREN($epodn, $cn, $mail, $entitlement)) {
					Framework::success_output("Updated map successfully.");
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
			$this->handleAttrValueAJAX($_GET['attr_value']);
			return;
		}

		$this->tpl->assign('handle_map',	true);
		$nrenMap = AuthHandler::getNRENMap($this->person->getNREN());

		if (isset($nrenMap['epodn'])) {
			$orgName = $this->person->getSession()->getAttribute($nrenMap['epodn']);
			$this->tpl->assign('epodn', implode(', ', $orgName));
		}

		if (isset($nrenMap['cn'])) {
			$cn = $this->person->getSession()->getAttribute($nrenMap['cn']);
			$this->tpl->assign('cn', implode(', ', $cn));
		}

		if (isset($nrenMap['mail'])) {
			$mail = $this->person->getSession()->getAttribute($nrenMap['mail']);
			$this->tpl->assign('mail', implode(', ', $mail));
		}

		if (isset($nrenMap['entitlement'])) {
			$entitlement = $this->person->getSession()->getAttribute($nrenMap['entitlement']);
			$this->tpl->assign('entitlement', implode(', ', $entitlement));
		}

		$this->tpl->assign('NRENMap',		AuthHandler::getNRENMap($this->person->getNREN()));
		$this->tpl->assign('keys',		AuthHandler::getAuthManager($this->person)->getAttributeKeys($this->person->isNRENAdmin()));
		$this->tpl->assign('content', 	$this->tpl->fetch('attributes.tpl'));
	}

	/**
	 * updateMapNREN() - take the new values and update/create the map for
	 * an NREN
	 */
	private function updateMapNREN($epodn, $cn, $mail, $entitlement)
	{
		/* Does the map exist? */
		$nren_id_query = "SELECT nren_id FROM nrens WHERE name=?";
		$query = "SELECT * FROM attribute_mapping WHERE nren_id=? AND subscriber_id IS NULL";
		try {
			$nren_id = MDB2Wrapper::execute($nren_id_query, array('text'), $this->person->getNREN());
			if (count($nren_id) != 1) {
				$errorMsg = __FILE__ . ":" . __LINE__ . " ";
				$errorMsg .= "Problems finding the NREN in the database. Got " . count($nren_id) . " from the DB";
				Framework::error_output($errorMsg);
				return false;
			}
			$res = MDB2Wrapper::execute($query,
						    array('text'),
						    $nren_id[0]['nren_id']);
		} catch (DBStatementException $dbse) {
			/* FIXME */
			Framework::error_output(__FILE__ . ":" . __LINE__ . " " . htmlentities($dbse->getMessage()));
			return false;
		} catch (DBQueryException $dbqe) {
			/* FIXME */
			Framework::error_output(__FILE__ . ":" . __LINE__ . " " . htmlentities($dbqe->getMessage()));
			return false;
		}

		switch(count($res)) {
		case 0:
			try {
				$update = "INSERT INTO attribute_mapping(nren_id, eppn, epodn, cn, mail, entitlement) VALUES(?, ?, ?, ?, ?, ?)";
				MDB2Wrapper::update($update,
						    array('text', 'text', 'text', 'text', 'text', 'text'),
						    array($nren_id[0]['nren_id'], $this->person->getEPPNKey(), $epodn, $cn, $mail, $entitlement));
			} catch (DBQueryException $dbqe) {
				Framework::error_output("Could not create new map.<br />Server said: " . htmlentities($dbqe->getMessage()));
				return false;
			}
			break;
		case 1:
			if ($epodn	!= $res[0]['epodn'] ||
			    $cn		!= $res[0]['cn'] ||
			    $mail	!= $res[0]['mail'] ||
			    $entitlement	!= $res[0]['entitlement']) {
				$update = "UPDATE attribute_mapping SET epodn=?, cn=?, mail=?, entitlement=? WHERE id=?";
				MDB2Wrapper::update($update,
						    array('text', 'text', 'text', 'text', 'text'),
						    array($epodn, $cn, $mail, $entitlement, $res[0]['id']));
			} else {
				Framework::error_output("No need to update row with identical elements");
				return false;
			}
			break;
		default:
			Framework::error_output("Error in getting the correct ID, it looks like the NREN has several (".count($res).") in the database");
			return false;
		}

		return true;
	}

		/**
	 * Return the value for the key to an IdP attribute (if defined)
	 *
	 * @param attr_key string The key of the attribute
	 * @return string The value for the supplied attribute key
	 */
	private function handleAttrValueAJAX($attr_key)
	{
		$session = $this->person->getSession();
		$attr_value = implode(", ", $session->getAttribute($attr_key));
		echo htmlentities($attr_value, ENT_COMPAT, "UTF-8");
		exit(0);
	}
}

$fw = new Framework(new CP_Attributes());
$fw->start();

?>
