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
			case 'update_map':
				$cn		= $_POST['cn'];
				$mail		= $_POST['mail'];
				$entitlement	= $_POST['entitlement'];

				if ($this->person->isNRENAdmin()) {
					$epodn		= $_POST['epodn'];
					if ($this->updateMapNREN($epodn, $cn, $mail, $entitlement)) {
						Framework::success_output("Updated map successfully.");
					}
				} else if ($this->person->isSubscriberAdmin()) {
					if ($this->updateMapSubscriber($cn, $mail, $entitlement)) {
						Framework::success_output("Updated map successfully.");
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
			$this->handleAttrValueAJAX($_GET['attr_value']);
			return;
		}

		$this->tpl->assign('handle_map',	true);

		if ($this->person->isNRENAdmin()) {
			$map = AuthHandler::getNRENMap($this->person->getNREN());
		} else if ($this->person->isSubscriberAdmin()) {
			$map = AuthHandler::getSubscriberMap($this->person->getNREN(),
			                                     $this->person->getSubscriberIdPName());
			if (count($map) == 0) {
				/* no subscriber map, fall back to NREN map */
				$map = AuthHandler::getNRENMap($this->person->getNREN());
			}
		}

		if (isset($map['epodn'])) {
			$orgName = $this->person->getSession()->getAttribute($map['epodn']);
			$this->tpl->assign('epodn', implode(', ', $orgName));
		}

		if (isset($map['cn'])) {
			$cn = $this->person->getSession()->getAttribute($map['cn']);
			$this->tpl->assign('cn', implode(', ', $cn));
		}

		if (isset($map['mail'])) {
			$mail = $this->person->getSession()->getAttribute($map['mail']);
			$this->tpl->assign('mail', implode(', ', $mail));
		}

		if (isset($map['entitlement'])) {
			$entitlement = $this->person->getSession()->getAttribute($map['entitlement']);
			$this->tpl->assign('entitlement', implode(', ', $entitlement));
		}

		$this->tpl->assign('map',		$map);
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
	 * Update the attribute-map, which only applies to the domain of the admin's
	 * subscriber.
	 *
	 * @param $cn string the key for the full name of the user
	 * @param $mail string the key for the mail address of the user
	 * @param $entitlement string the key for the user's entitlement
	 */
	private function updateMapSubscriber($cn, $mail, $entitlement)
	{
		$subscriber = $this->person->getSubscriber()->getIdPName();
		$nren = $this->person->getNREN();
		$nrenMap = AuthHandler::getNRENMap($nren);
		$epodn = $nrenMap['epodn'];

		$query = "SELECT n.nren_id, s.subscriber_id " .
		         "FROM nrens n, subscribers s " .
		         "WHERE s.nren_id = n.nren_id AND s.name = ? " .
		         "AND n.name = ?";

		try {
			$res = MDB2Wrapper::execute($query,
			                            array('text', 'text'),
			                            array($subscriber, $nren));

			if (count($res) == 1) {
				$query = "SELECT id, cn, mail, entitlement " .
				         "FROM attribute_mapping " .
				         "WHERE nren_id = ? AND subscriber_id = ?";
				$mapping = MDB2Wrapper::execute($query,
				                                array('text','text'),
				                                array($res[0]['nren_id'], $res[0]['subscriber_id']));
			}

		} catch (DBQueryException $dbqe) {
			Framework::error_output("Errors trying to get the attribute map for " .
			                        "your subscriber " . htmlentities($subscriber) .
			                        "! Probably an error with the input data: " .
			                        htmlentities($dbqe->getMessage()));
			Logger::log_event(LOG_NOTICE, "[sadm] Error when trying to find subscriber " .
			                 "map for NREN $nren and subscriber $subscriber: " . $dbqe->getMessage() . "!");
			return false;
		} catch (DBStatementException $dbse) {
			Framework::error_output("Errors trying to get the attribute map for " .
			                        "your subscriber " . htmlentities($subscriber) . "!" .
			                        " Probably a server configuration error: " .
			                        htmlentities($dbse->getMessage()));
			Logger::log_event(LOG_NOTICE, "[sadm] Statement error when trying to find subscriber " .
			                  "map for NREN $nren and subscriber $subscriber: " . $dbse->getMessage() . "!");
			return false;
		}

		switch (count($mapping)) {
		case 0:
			try {
				$update = "INSERT INTO attribute_mapping(nren_id, subscriber_id, eppn, epodn, cn, mail, entitlement) " .
				          "VALUES(?, ?, ?, ?, ?, ?, ?)";
				$types = array('text', 'text', 'text', 'text', 'text', 'text', 'text');
				$params = array($res[0]['nren_id'], $res[0]['subscriber_id'], $this->person->getEPPNKey(), $epodn, $cn, $mail, $entitlement);

				MDB2Wrapper::update($update,
				                    $types,
				                    $params);
			} catch (ConfusaGenException $cge) {
				Framework::error_output("Could not insert new attribute mapping for your NREN " . htmlentities($nren) .
				                        " and subscriber " . htmlentities($subscriber) . " Server error was: " .
				                        htmlentities($cge->getMessage()));
				Logger::log_event(LOG_WARNING, "[sadm] Could not insert attribute mapping for NREN $nren's " .
				                  "subscriber $subscriber due to error " . $cge->getMessage());
				return false;
			}
			break;

		case 1:
			if ($mapping[0]['cn'] != $cn ||
			    $mapping[0]['mail'] != $mail ||
			    $mapping[0]['entitlement'] != $entitlement) {

				$update = "UPDATE attribute_mapping " .
				          "SET epodn = ?, cn = ?, mail = ?, entitlement = ? " .
				          "WHERE id = ?";
				$types = array('text', 'text', 'text', 'text', 'text');
				$params = array($epodn, $cn, $mail, $entitlement, $mapping[0]['id']);

				try {
					MDB2Wrapper::update($update,
					                    $types,
					                    $params);
				} catch (ConfusaGenException $cge) {
					Framework::error_output("Could not update existing mapping for subscriber . " .
					                        htmlentities($subscriber) . " of NREN " .
					                        htmlentities($nren) . ", due to server problem: " .
					                        htmlentities($cge->getMessage()));
					Logger::log_event(LOG_WARNING, "[sadm] Could not update existing mapping for subscriber " .
					                 htmlentities($subscriber) . " of NREN " .
					                 htmlentities($nren) . ", due to error: " .
					                 htmlentities($cge->getMessage()));
					return false;
				}
			}

			break;
		default:
			Framework::error_output("More than one mapping for subscriber " . htmlentities($subscriber) .
			                        " of NREN " . htmlentities($nren) . " found! Something is very wrong...");
			return false;
			break;
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
		if (empty($attr_key)) {
			exit(0);
		}

		$session = $this->person->getSession();
		$attr_value = @implode(", ", $session->getAttribute($attr_key));
		echo htmlentities($attr_value, ENT_COMPAT, "UTF-8");
		exit(0);
	}
}

$fw = new Framework(new CP_Attributes());
$fw->start();

?>
