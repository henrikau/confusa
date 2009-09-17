<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'input.php';
require_once 'logger.php';
require_once 'mdb2_wrapper.php';
require_once 'translator.php';

final class CP_Language_Selector extends Content_Page
{

	private $full_names;

	function __construct()
	{
		parent::__construct("Language Selector", true);
		$available_languages = Config::get_config('language.available');
		$this->full_names = Translator::getFullNamesForISOCodes($available_languages);
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		/* only subscriber- or NREN admins may update the preferred language */
		if ($person->isSubscriberAdmin() || $person->isNRENAdmin()) {
			if (isset($_POST['language_operation'])) {
				switch ($_POST['language_operation']) {
					case 'update':
						if (isset($_POST['language'])) {
							$new_language = Input::sanitize($_POST['language']);

							if ($person->isSubscriberAdmin()) {
								$this->updateSubscriberLanguage($person->getSubscriberOrgName(),
																$new_language);
							} else if ($person->isNRENAdmin()) {
								$this->updateNRENLanguage($person->getNREN(),
														  $new_language);
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
	}

	function process()
	{
		if ($this->person->isNRENAdmin()) {
			$current_language = $this->getNRENLanguage($this->person->getNREN());
		} else if ($this->person->isSubscriberAdmin()) {
			$current_language = $this->getSubscriberLanguage($this->person->getSubscriberOrgName());
		}

		if (is_null($current_language)) {
			$current_language = Config::get_config('language.default');
		}

		$this->tpl->assign('languages', $this->full_names);
		$this->tpl->assign('current_language', $current_language);
		$this->tpl->assign('language_codes', array_keys($this->full_names));
		$this->tpl->assign('content', $this->tpl->fetch('language_selector.tpl'));
	}

	private function updateNRENLanguage($nren, $new_language)
	{
		$query = "UPDATE nrens SET lang=? WHERE name=?";

		try {
			MDB2Wrapper::update($query,
								array('text','text'),
								array($new_language, $nren));
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_NOTICE, "Updating the language to $new_language " .
							"failed for NREN $nren. Error was: " . $dbqe->getMessage());
			Framework::error_output("Updating the language to $new_language for your " .
									"NREN $nren failed, probably due to problems with " .
									"the supplied data. Server said: " . $dbqe->getMessage());
			return;
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_NOTICE, "Updating the language to $new_language " .
							"failed for NREN $nren. Error was: " . $dbse->getMessage());
			Framework::error_output("Updating the language to $new_language for your " .
									"NREN $nren failed, probably due to problems with the " .
									"server configuration! Server said: " . $dbse->getMessage());
			return;
		}

		Logger::log_event(LOG_DEBUG, "Default language changed to $new_language for NREN $nren");
		Framework::success_output("Default language for your NREN successfully changed to " .
									$this->full_names[$new_language]);
	}

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
	}

	private function getNRENLanguage($nren)
	{
		$query = "SELECT lang FROM nrens WHERE name=?";

		$res = MDB2Wrapper::execute($query,
									array('text'),
									array($nren));
		return $res[0]['lang'];
	}

	private function getSubscriberLanguage($subscriber)
	{
		$query = "SELECT lang FROM subscribers WHERE name=?";

		$res = MDB2Wrapper::execute($query,
									array('text'),
									array($subscriber));
		return $res[0]['lang'];
	}
}

$fw = new Framework(new CP_Language_Selector());
$fw->start();
?>
