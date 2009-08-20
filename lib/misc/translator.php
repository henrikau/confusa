<?php
$sspdir = Config::get_config('simplesaml_path');
require_once $sspdir . '/lib/_autoload.php';
/**
 * Translator - lookup dictionary entries for a page and decorate the template
 * with the right texts
 *
 * The dictionaries are supposed to be in directory dictionaries. The translator
 * guesses the "optimal" language for the user and decorates the template with
 * texts from that language. If texts in that language are not available, it
 * falls back to the default language defined in Confusa's configuration.
 */
class Translator {
	private $language;
	private $defaultLanguage;
	private $person;

	/**
	 * Construct a new translator. Guess the best language
	 */
	public function __construct($person)
	{
		$this->person = $person;
		$this->defaultLanguage = Config::get_config('language.default');
		$this->language = $this->getBestLanguage();
		Framework::message_output("Language: " . $this->language);
	}

	/**
	 * Decorate a given template with the tags from the dictornary in the
	 * right language. This is nothing more than repeated consulation of a
	 * LUT:
	 *
	 * @param $template The template that is to be decorated
	 * @param $dictionary The dictionary from which the texts are taken
	 *
	 * @return The decorated template
	 */
	public function decorateTemplate($template, $dictionary)
	{
		include(Config::get_config('install_path') . "/dictionaries/" . $dictionary);
		foreach($lang as $tag => $entry) {
			if (isset($entry[$this->language])) {
				$template->assign($tag, $entry[$this->language]);
			} else {
				$template->assign($tag, $entry[$this->defaultLanguage]);
			}
		}

		return $template;
	}

	/**
	 * Get the "best" language for a user. The "best" language is determined by
	 * the following order of steps:
	 *
	 * 1.) The language stored in the session of the user dominates over everything else
	 *		Thus, manually changing the language only means setting a session variable.
	 * 2.) Try to take the language set by the subscriber, if the user is logged in
	 * 3.) If the subscriber-language is NULL, take the language set by the NREN,
	 *		if the user is logged in
	 * 4.) If the user is not logged in and no session variable is set, take the
	 *		first available language from the user's language accept-headers
	 * 5.) If none of the languages in the user's accept header is available,
	 *		take the default language of the Confusa instance (usually but not necessarily English)
	 */
	private function getBestLanguage()
	{
		if ($_SESSION['language']) {
			return $_SESSION['language'];
		}

		if ($this->person->isAuth()) {
			$query = "SELECT lang FROM subscribers WHERE name=?";
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($this->person->getSubscriberOrgName()));

			if (isset($res[0]['lang'])) {
				$_SESSION['language'] = $res[0]['lang'];
				return $res[0]['lang'];
			}

			$query = "SELECT lang FROM nrens WHERE name=?";
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($this->person->getNREN()));

			if (isset($res[0]['lang'])) {
				$_SESSION['language'] = $res[0]['lang'];
				return $res[0]['lang'];
			}
		}

		$accept_languages = SimpleSAML_Utilities::getAcceptLanguage();
		$available_languages = Config::get_config('language.available');

		foreach($accept_languages as $key => $value) {
			if (array_search($key, $available_languages) === FALSE) {
				continue;
			} else {
				return $key;
			}
		}

		return $this->defaultLanguage;
	}
}

?>
