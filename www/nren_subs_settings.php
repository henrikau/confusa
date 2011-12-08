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
	private $form_data;
	private $validation_error;
	function __construct()
	{
		parent::__construct("NREN/subscriber settings", true, "contactinfo");
		$available_languages = Config::get_config('language.available');
		$this->full_names = Translator::getFullNamesForISOCodes($available_languages);

		$this->form_data['contact_email'] = "";
		$this->form_data['contact_phone'] = "";
		$this->form_data['sanitizedCertPhone'] = "";
		$this->form_data['sanitizedCertEmail'] = "";
		$this->form_data['sanitizedURL'] = "";
		$this->form_data['sanitizedWAYF'] = "";
		$this->form_data['enable_email'] = "";
		$this->form_data['cert_validity'] = "";
		$this->validation_error = false;
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		/* IF user is not subscirber- or nren-admin, we stop here */
		if (!($this->person->isSubscriberAdmin() || $this->person->isNRENAdmin())) {
			return false;
		}

		if (isset($_POST['setting'])) {
			switch($_POST['setting']) {
			case 'nren_contact':
				if ($this->person->isNRENAdmin()) {

					if (array_key_exists('contact_email', $_POST)) {
						$this->form_data['contact_email'] = Input::sanitizeEmail($_POST['contact_email']);
						if ($_POST['contact_email'] !== $this->form_data['contact_email']) {
							$this->displayInvalidCharError($_POST['contact_email'],
														   $_POST['contact_email'],
														   'l10n_label_contactemail');
							$this->form_data['contact_email'] = "";
							$this->validationError = true;
						}
					}

					if (array_key_exists('contact_phone', $_POST)) {
						$this->form_data['contact_phone'] = Input::sanitizePhone($_POST['contact_phone']);
						if ($_POST['contact_phone'] !== $this->form_data['contact_phone']) {
							$this->displayInvalidCharError($_POST['contact_phone'],
														   $this->form_data['contact_phone'],
														   'l10n_label_contactphone');
							$this->form_data['contact_phone'] = "";
							$this->validationError = true;
						}
					}

					if (array_key_exists('cert_phone', $_POST)) {
						$this->form_data['sanitizedCertPhone'] =Input::sanitizePhone($_POST['cert_phone']);
						if ($_POST['cert_phone'] != $this->form_data['sanitizedCertPhone']) {
							$this->displayInvalidCharError($_POST['cert_phone'],
														   $this->form_data['sanitizedCertPhone'],
														   'l10n_label_certphone');
							$this->form_data['sanitizedCertPhone'] = "";
							$this->validationError = true;
						}
					}

					if (array_key_exists('cert_email', $_POST)) {
						$this->form_data['sanitizedCertEmail'] = Input::sanitizeEmail($_POST['cert_email']);
						if ($_POST['cert_email'] != $this->form_data['sanitizedCertEmail']) {
							$this->displayInvalidCharError($_POST['cert_email'],
														   $this->form_data['sanitizedCertEmail'],
														   'l10n_label_certmail');
							$this->form_data['sanitizedCertEmail'] = "";
							$this->validationError = true;
						}
					}

					if (array_key_exists('url', $_POST)) {
						$this->form_data['sanitizedURL'] = Input::sanitizeURL($_POST['url']);
						if ($_POST['url'] != $this->form_data['sanitizedURL']) {
							$this->displayInvalidCharError($_POST['url'],
														   $this->form_data['sanitizedURL'],
														   'l10n_label_nrenurl');
							$this->form_data['sanitizedURL'] = "";
							$this->validationError = true;
						}
					}

					if (array_key_exists('wayf_url', $_POST)) {
						$this->form_data['sanitizedWAYF'] = Input::sanitizeURL($_POST['wayf_url']);
						if ($_POST['wayf_url'] != $this->form_data['sanitizedWAYF']) {
							$this->displayInvalidCharError($_POST['wayf_url'],
														   $this->form_data['sanitizedWAYF'],
														   'l10n_label_wayfurl');
							$this->form_data['sanitizedWAYF'] = "";
							$this->validationError = true;
						}
					}

					if (array_key_exists('enable_email', $_POST) && isset($_POST['enable_email'])) {
						if (Config::get_config('cert_product') == PRD_PERSONAL) {
							if (array_key_exists($_POST['enable_email'], $this->PERSONAL_MAILOPTIONS)) {
								$this->form_data['enable_email'] = $_POST['enable_email'];
							}
						} else {
							if (array_key_exists($_POST['enable_email'], $this->ESCIENCE_MAILOPTIONS)) {
								$this->form_data['enable_email'] = $_POST['enable_email'];
							}
						}
					}

					if (array_key_exists('reauth_timeout', $_POST) && isset($_POST['reauth_timeout'])) {
						$this->form_data['reauth_timeout'] = Input::sanitizeNumeric($_POST['reauth_timeout']);
					}
					if (isset($_POST['cert_validity']) &&
					    array_search($_POST['cert_validity'], ConfusaConstants::$CAPI_VALID_PERSONAL) !== FALSE) {
						$this->form_data['cert_validity'] = $_POST['cert_validity'];
					}


					/* don't continue if information has been stripped */
					if ($this->validation_error) {
						return;
					}
					if ($this->updateNRENContact()) {
						Framework::success_output($this->translateTag('l10n_suc_updatenren', 'contactinfo') . " " .
						                          $this->person->getNREN()->getName());
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

					$this->validationError = false;

					if ($_POST['contact_email'] != $sanitizedMail) {
						$this->displayInvalidCharError($_POST['contact_email'],
						                               $sanitizedMail,
						                               'l10n_label_contactemail');
						$this->validationError = true;
					}

					if ($_POST['contact_phone'] != $sanitizedPhone) {
						$this->displayInvalidCharError($_POST['contact_phone'],
						                              $sanitizedPhone,
						                              'l10n_label_contactphone');
						$this->validationError = true;
					}

					if ($_POST['resp_name'] != $sanitizedRespName) {
						$this->displayInvalidCharError($_POST['resp_name'],
						                               $sanitizedRespName,
						                               'l10n_label_respname');
						$this->validationError = true;
					}

					if ($_POST['resp_email'] != $sanitizedRespMail) {
						$this->displayInvalidCharError($_POST['resp_email'],
						                               $sanitizedRespMail,
						                               'l10n_label_respemail');
						$this->validationError = true;
					}

					if ($_POST['helpdesk_url'] != $sanitizedHelpdeskURL) {
						$this->displayInvalidCharError($_POST['helpdesk_url'],
						                               $sanitizedHelpdeskURL,
						                               'l10n_label_helpdeskurl');
						$this->validationError = true;
					}

					if ($_POST['helpdesk_email'] != $sanitizedHelpdeskMail) {
						$this->displayInvalidCharError($_POST['helpdesk_email'],
						                               $sanitizedHelpdeskMail,
						                               'l10n_label_helpemail');
						$this->validationError = true;
					}

					/*
					 * don't continue if data got stripped
					 */
					if ($this->validationError) {
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

	private function updateNRENContact()
	{
		$nren = $this->person->getNREN();
		if (!isset($nren) || $this->validation_error) {
			return false;
		}
		if (isset($this->form_data['contact_email']) &&
			$this->form_data['contact_email'] !== "") {
			$nren->setContactEmail($this->form_data['contact_email']);
		}

		if (isset($this->form_data['contact_phone']) &&
			$this->form_data['contact_phone'] !== "") {
			$nren->setContactPhone($this->form_data['contact_phone']);
		}
		if (isset($this->form_data['sanitizedCertPhone']) &&
			$this->form_data['sanitizedCertPhone'] !== "") {
			$nren->setCertPhone($this->form_data['sanitizedCertPhone']);
		}
		if (isset($this->form_data['sanitizedCertEmail']) &&
			$this->form_data['sanitizedCertEmail'] !== "") {
			$nren->setCertEmail($this->form_data['sanitizedCertEmail']);
		}
		if (isset($this->form_data['sanitizedURL']) &&
			$this->form_data['sanitizedURL'] !== "") {
			$nren->setURL($this->form_data['sanitizedURL']);
		}
		if (isset($this->form_data['sanitizedWAYF']) &&
			$this->form_data['sanitizedWAYF'] !== "") {
			$nren->setWAYFURL($this->form_data['sanitizedWAYF']);
		}
		if (isset($this->form_data['enable_email']) &&
			$this->form_data['enable_email'] !== "") {
			$nren->setEnableEmail($this->form_data['enable_email']);
		}
		if (isset($this->form_data['cert_validity']) &&
			$this->form_data['cert_validity'] !== "") {
			$nren->setCertValidity($this->form_data['cert_validity']);
		}
		if (isset($this->form_data['reauth_timeout']) &&
			$this->form_data['reauth_timeout'] !== "") {
			$this->person->getNREN()->setReauthTimeout($this->form_data['reauth_timeout']);
		}

		return $nren->saveNREN();
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
	private function updateSubscriberContact($language)
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
