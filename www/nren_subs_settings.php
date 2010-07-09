<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'Translator.php';
require_once 'MDB2Wrapper.php';

class CP_NREN_Subs_Settings extends Content_Page
{

	private $ESCIENCE_MAILOPTIONS = array('0' => ' None',
	                                      '1' => ' Single',
	                                      'n' => ' Multiple, including 0.',
	                                      'm' => ' Multiple, at least one.');
	private $PERSONAL_MAILOPTIONS = array('1' => ' Single',
	                                      'm' => ' Multiple, at least one.');

	function __construct()
	{
		parent::__construct("NREN/subscriber settings", true, "contactinfo");
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
			switch($_POST['setting']) {
			case 'nren_contact':
				if ($this->person->isNRENAdmin()) {

					$sanitizedEmail =
						Input::sanitizeEmail($_POST['contact_email']);
					$sanitizedContactPhone =
						Input::sanitizePhone($_POST['contact_phone']);
					$sanitizedCertPhone =
						Input::sanitizePhone($_POST['cert_phone']);
					$sanitizedCertEmail =
						Input::sanitizeEmail($_POST['cert_email']);
					$sanitizedURL = Input::sanitizeURL($_POST['url']);
					$sanitizedWAYF = Input::sanitizeURL($_POST['wayf_url']);

					$validationError = false;

					if ($_POST['contact_email'] != $sanitizedEmail) {
						$this->displayInvalidCharError($_POST['contact_email'],
						                               $sanitizedEmail,
						                               'l10n_label_contactemail');
						$validationError = true;
					}

					if ($_POST['contact_phone'] != $sanitizedContactPhone) {
						$this->displayInvalidCharError($_POST['contact_phone'],
						                               $sanitizedContactPhone,
						                               'l10n_label_contactphone');
						$validationError = true;
					}

					if ($_POST['cert_phone'] != $sanitizedCertPhone) {
						$this->displayInvalidCharError($_POST['cert_phone'],
						                               $sanitizedCertPhone,
						                               'l10n_label_certphone');
						$validationError = true;
					}

					if ($_POST['cert_email'] != $sanitizedCertEmail) {
						$this->displayInvalidCharError($_POST['cert_email'],
						                               $sanitizedCertEmail,
						                               'l10n_label_certmail');
						$validationError = true;
					}

					if ($_POST['url'] != $sanitizedURL) {
						$this->displayInvalidCharError($_POST['url'],
						                               $sanitizedURL,
						                               'l10n_label_nrenurl');
						$validationError = true;
					}

					if ($_POST['wayf_url'] != $sanitizedWAYF) {
						$this->displayInvalidCharError($_POST['wayf_url'],
						                               $sanitizedWAYF,
						                               'l10n_label_wayfurl');
						$validationError = true;
					}

					/* don't continue if information has been stripped */
					if ($validationError) {
						return;
					}

					$this->person->getNREN()->setContactEmail( Input::sanitizeEmail($_POST['contact_email']));
					$this->person->getNREN()->setContactPhone( $sanitizedContactPhone);
					$this->person->getNREN()->setCertPhone(    $sanitizedCertPhone);
					$this->person->getNREN()->setCertEmail(    $sanitizedCertEmail);
					$this->person->getNREN()->setURL(          $sanitizedURL);
					$this->person->getNREN()->setLang(         Input::sanitizeLangCode($_POST['language']));
					if (!$this->person->getNREN()->setWAYFURL( $sanitizedWAYF)) {
						Framework::error_output($this->translateTag('l10n_error_wayf_url', 'contactinfo'));
					}
					$this->person->getNREN()->setReauthTimeout(Input::sanitizeNumeric($_POST['reauth_timeout']));

					$nren = $this->person->getNREN();

					if (isset($_POST['enable_email'])) {
						if (Config::get_config('cert_product') == PRD_PERSONAL) {
							if (array_key_exists($_POST['enable_email'], $this->PERSONAL_MAILOPTIONS)) {
								$nren->setEnableEmail($_POST['enable_email']);
							}
						} else {
							if (array_key_exists($_POST['enable_email'], $this->ESCIENCE_MAILOPTIONS)) {
								$nren->setEnableEmail($_POST['enable_email']);
							}
						}
					}

					if (isset($_POST['cert_validity']) &&
					    array_search($_POST['cert_validity'], ConfusaConstants::$CAPI_VALID_PERSONAL) !== FALSE) {
						$this->person->getNREN()->setCertValidity($_POST['cert_validity']);
					}

					if ($nren->saveNREN()) {
						Framework::success_output($this->translateTag('l10n_suc_updatenren', 'contactinfo') . " " .
						                          $nren->getName());
					}
				}
				break;
			case 'subscriber_contact':
				if ($this->person->isSubscriberAdmin()) {
					$sanitizedMail =
						Input::sanitizeEmail($_POST['contact_email']);
					$sanitizedPhone =
						Input::sanitizePhone($_POST['contact_phone']);
					$sanitizedRespName =
						Input::sanitizePersonName($_POST['resp_name']);
					$sanitizedRespMail =
						Input::sanitizeEmail($_POST['resp_email']);
					$sanitizedHelpdeskURL =
						Input::sanitizeURL($_POST['helpdesk_url']);
					$sanitizedHelpdeskMail =
						Input::sanitizeEmail($_POST['helpdesk_email']);

					$validationError = false;

					if ($_POST['contact_email'] != $sanitizedMail) {
						$this->displayInvalidCharError($_POST['contact_email'],
						                               $sanitizedMail,
						                               'l10n_label_contactemail');
						$validationError = true;
					}

					if ($_POST['contact_phone'] != $sanitizedPhone) {
						$this->displayInvalidCharError($_POST['contact_phone'],
						                              $sanitizedPhone,
						                              'l10n_label_contactphone');
						$validationError = true;
					}

					if ($_POST['resp_name'] != $sanitizedRespName) {
						$this->displayInvalidCharError($_POST['resp_name'],
						                               $sanitizedRespName,
						                               'l10n_label_respname');
						$validationError = true;
					}

					if ($_POST['resp_email'] != $sanitizedRespMail) {
						$this->displayInvalidCharError($_POST['resp_email'],
						                               $sanitizedRespMail,
						                               'l10n_label_respemail');
						$validationError = true;
					}

					if ($_POST['helpdesk_url'] != $sanitizedHelpdeskURL) {
						$this->displayInvalidCharError($_POST['helpdesk_url'],
						                               $sanitizedHelpdeskURL,
						                               'l10n_label_helpdeskurl');
						$validationError = true;
					}

					if ($_POST['helpdesk_email'] != $sanitizedHelpdeskMail) {
						$this->displayInvalidCharError($_POST['helpdesk_email'],
						                               $sanitizedHelpdeskMail,
						                               'l10n_label_helpemail');
						$validationError = true;
					}

					/*
					 * don't continue if data got stripped
					 */
					if ($validationError) {
						return;
					}

					$this->updateSubscriberContact($sanitizedMail,
					                               $sanitizedPhone,
					                               $sanitizedRespName,
					                               $sanitizedRespMail,
					                               $sanitizedHelpdeskURL,
					                               $sanitizedHelpdeskMail,
					               Input::sanitizeLangCode($_POST['language']));
				}
				break;
			default:
				Framework::error_output("Unknown action (" . htmlentities($_POST['setting']) . ")");
				break;
			}
		}
	}

	public function process()
	{
		if ($this->person->isNRENAdmin()) {
			$info = $this->person->getNREN()->getNRENInfo();
			$current_language = null;
			if (array_key_exists('lang', $info)) {
				$current_language = $info['lang'];
			}
			$this->tpl->assign('nrenInfo', $info);
		} else {
			$info = $this->person->getSubscriber()->getInfo();
			$this->tpl->assign('subscriberInfo', $info);
			$current_language = $info['lang'];
		}

		if (empty($current_language)) {
			$current_language = Config::get_config('language.default');
		}

		/* export the different subjectAltName email-settings */
		if (Config::get_config('cert_product') === PRD_PERSONAL) {
			$this->tpl->assign('enable_options', $this->PERSONAL_MAILOPTIONS);
			$this->tpl->assign('validity_options', array('365' => ' 365 days',
		                                             '730' => ' 730 days',
		                                            '1095' => ' 1095 days'));
			$this->tpl->assign('personal', true);
		} else {
			$this->tpl->assign('enable_options', $this->ESCIENCE_MAILOPTIONS);
			$this->tpl->assign('personal', false);
		}

		$this->tpl->assign('languages', $this->full_names);
		$this->tpl->assign('current_language', $current_language);
		$this->tpl->assign('language_codes', array_keys($this->full_names));
		$this->tpl->assign('content', $this->tpl->fetch('nren_subs_settings.tpl'));
	}

	/**
	 * Update the contact information for a subscriber to a new value
	 *
	 * @param $contact_email string A general subscriber-mail address
	 * @param $contact_phone string The (main) phone number of the subscriber
	 * @param $resp_name string The name of a responsible person at the subscr.
	 * @param $resp_email string e-mail address of a responsible person
	 * @param $help_url string URL of the subscriber's helpdesk
	 * @param $help_email string e-mail address of the subscriber's helpdesk
	 * @param $language string the language code for the subscriber's preferred
	 *                         language
	 */
	private function updateSubscriberContact($contact_email,
	                                         $contact_phone,
	                                         $resp_name,
	                                         $resp_email,
	                                         $help_url,
	                                         $help_email,
	                                         $language)
	{
		$subscriber = $this->person->getSubscriber();
		$subscriber->setEmail($contact_email);
		$subscriber->setPhone($contact_phone);
		$subscriber->setRespName($resp_name);
		$subscriber->setRespEmail($resp_email);
		$subscriber->setHelpURL($help_url);
		$subscriber->setHelpEmail($help_email);
		$subscriber->setLanguage($language);

		try {
			$subscriber->save();
		} catch (ConfusaGenException $cge) {
			Framework::error_output($this->translateTag('l10n_err_updatesubscr', 'contactinfo') . " " .
									htmlentities($cge->getMessage()));
			Logger::log_event(LOG_INFO, "[sadm] Could not update " .
							"contact of subscriber $subscriber: " .
							$cge->getMessage());
		}

		Framework::success_output($this->translateTag('l10n_suc_updatesubscr', 'contactinfo') . " " .
		                          htmlentities($subscriber->getIdPName()) . ".");
		Logger::log_event(LOG_DEBUG, "[sadm] Updated contact for subscriber $subscriber.");
	} /* end updateSubscriberContact */
}

$fw = new Framework(new CP_NREN_Subs_Settings());
$fw->start();
?>
