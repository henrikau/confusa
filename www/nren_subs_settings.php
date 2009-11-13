<?php
require_once 'confusa_include.php';
require_once 'content_page.php';
require_once 'framework.php';
require_once 'translator.php';
require_once 'mdb2_wrapper.php';

class CP_NREN_Subs_Settings extends Content_Page
{
	function __construct()
	{
		parent::__construct("NREN_Subs_Settings", true);
		$available_languages = Config::get_config('language.available');
		$this->full_names = Translator::getFullNamesForISOCodes($available_languages);
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		/* IF user is not subscirber- or nren-admin, we stop here */
		if (!($this->person->isSubscriberAdmin() || $this->person->isNRENAdmin()))
			return false;

		if (isset($_POST['setting'])) {
			switch(htmlentities($_POST['setting'])) {
			case 'nren_contact':
				if ($this->person->isNRENAdmin()) {
					$this->person->getNREN()->set_contact_email(	$_POST['contact_email']);
					$this->person->getNREN()->set_contact_phone(	$_POST['contact_phone']);
					$this->person->getNREN()->set_cert_phone(	$_POST['cert_phone']);
					$this->person->getNREN()->set_cert_email(	$_POST['cert_email']);
					$this->person->getNREN()->set_url(		$_POST['url']);
					$this->person->getNREN()->set_lang(		$_POST['language']);
					$this->person->getNREN()->saveNREN();
				}
				break;
			case 'subscriber_contact':
				if ($this->person->isSubscriberAdmin()) {
					/* ($contact_email, $contact_phone, $resp_name, $resp_email) */
					$this->updateSubscriberContact(
						Input::sanitizeText($_POST['contact_email']),
						Input::sanitizeText($_POST['contact_phone']),
						Input::sanitizeText($_POST['resp_name']),
						Input::sanitizeText($_POST['resp_email']));
				}
				break;
			default:
				Framework::error_output("Unknown action (".$_POST['setting'] . ")");
				break;
			}
		} else if (isset($_POST['language_operation'])) {
				switch ($_POST['language_operation']) {
					case 'update':
						if (isset($_POST['language'])) {
							$new_language = Input::sanitize($_POST['language']);

							if ($person->isSubscriberAdmin()) {
								$this->updateSubscriberLanguage($person->getSubscriberOrgName(),
												$new_language);
							} else if ($person->isNRENAdmin()) {
								$this->person->getNREN()->set_lang(Input::sanitize($_POST['language']));
								$this->person->getNREN()->saveNREN();
							}
						}

						break;

					default:
						Framework::error_output("Unknown operation");
						break;
				}
			} else {
				return;
			}
	}

	public function process()
	{
		if ($this->person->isNRENAdmin()) {
			$info = $this->person->getNREN()->getNRENInfo();
			$current_language = $info['lang'];
			$this->tpl->assign('nrenInfo', $info);
		} else {
			$info = $this->getSubscriberInfo();
			$current_language = $info['lang'];
			$this->tpl->assign('subscriberInfo', $info);
		}

		if (is_null($current_language)) {
			$current_language = Config::get_config('language.default');
		}

		$this->tpl->assign('languages', $this->full_names);
		$this->tpl->assign('current_language', $current_language);
		$this->tpl->assign('language_codes', array_keys($this->full_names));
		$this->tpl->assign('content', $this->tpl->fetch('nren_subs_settings.tpl'));
	}
	/**
	 * Get the current contact information for a subscriber
	 *
	 * @param void 
	 * @return String The contact that was defined for the subscriber
	 */
	private function getSubscriberInfo()
	{
		$query  = "SELECT s.* FROM subscribers s LEFT JOIN nrens n ";
		$query .= "ON n.nren_id = s.nren_id WHERE s.name=? AND n.name=?";
		try {
			$res = MDB2Wrapper::execute($query,
						    array('text', 'text'),
						    array($this->person->getSubscriberIdPName(),
							  $this->person->getNREN()));
		} catch (DBQueryException $dqe) {
			Framework::warning_ouput("Could not get the current contact information for $subscriber");
		} catch (DBStatementException $dse) {
			Framework::warning_output("Could not get the current contact information for $subscriber");
			Logger::log_event(LOG_INFO, "[sadm] Could not get current contact for subscriber " .
							"$subscriber: " . $dse->getMessage());
		}

		return $res[0];
	}

	/**
	 * Update the contact information for a subscriber to a new value
	 *
	 * @param $subscriber string The subscriber for which the contact information
	 *		should be updated
	 * @param $contact string The new contact information
	 */
	private function updateSubscriberContact($contact_email, $contact_phone, $resp_name, $resp_email)
	{
		$subscriber = $this->person->getSubscriberIdPName();
		$query="UPDATE subscribers SET subscr_email=?, subscr_phone=?, subscr_resp_name=?, subscr_resp_email=? WHERE name=?";

		try {
			MDB2Wrapper::update($query,
					    array('text','text','text','text','text'),
					    array($contact_email, $contact_phone, $resp_name, $resp_email, $subscriber));
		} catch (DBQueryException $dqe) {
			Framework::error_output("Could not change the subscriber contact! Maybe something is " .
									"wrong with the data that you supplied? Server said: " .
									$dqe->getMessage());
			Logger::log_event(LOG_INFO, "[sadm] Could not update " .
							"contact of subscriber $subscriber: " .
							$dqe->getMessage());
		} catch (DBStatementException $dse) {
			Framework::error_output("Could not change the subscriber contact! Confusa " .
									"seems to be misconfigured. Server said: " .
									$dse->getMessage());
			Logger::log_event(LOG_WARNING, "[sadm] Could not update " .
							"contact of $subscriber: " .
							$dse->getMessage());
		}

		Framework::success_output("Updated contact information for your subscriber $subscriber.");
		Logger::log_event(LOG_DEBUG, "[sadm] Updated contact for subscriber $subscriber.");
	} /* end updateSubscriberContact */

	/**
	 * Update the default language for a subscriber
	 *
	 * @param $nren string The name of the subscriber
	 * @param $new_language string the ISO 639-1 code for the new default language of the subscriber
	 */
	private function updateSubscriberLanguage($subscriber, $new_language)
	{
		$query = "UPDATE subscribers SET lang=? WHERE name=?";

		try {
			MDB2Wrapper::update($query,
								array('text', 'text'),
								array($new_language, $subscriber));
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_NOTICE, "Updating the language to $new_language " .
							 "failed for subscriber $subscriber. Error was: " . $dbqe->getMessage());
			Framework::error_output("Updating the language to $new_language failed " .
									"for subscriber $subscriber, probably due to problems " .
									"with the supplied data. Server said: " . $dbqe->getMessage());
			return;
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_NOTICE, "Updating the language to $new_language " .
							"faield for subscriber $subscriber. Error was: " . $dbse->getMessage());
			Framework::error_output("Updating the language to $new_language failed " .
									"for subscriber $subscriber, probably due to problems " .
									"with the server configuration. Server said: " . $dbse->getMessage());
			return;
		}

		Logger::log_event(LOG_DEBUG, "Default language changed to $new_language for " .
							"subscriber $subscriber");
		Framework::success_output("Default language for your subscriber successfully changed to " .
									$this->full_names[$new_language]);
	} /* end updateSubscriberLanguage */
}

$fw = new Framework(new CP_NREN_Subs_Settings());
$fw->start();
?>
