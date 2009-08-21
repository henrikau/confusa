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

	private static $code_language_map = array(
								'bg' => 'Български език (Bulgarian)',
								'ca' => 'Català (Catalan)',
								'cs' => 'Česky (Czech)',
								'de' => 'Deutsch (German)',
								'de-AT' => 'Deutsch Österreich (German)',
								'de-CH' => 'Deutsch Schweiz (German)',
								'de-DE' => 'Deutsch Deutschland (German)',
								'dk' => 'Dansk (Danish)',
								'el' => 'Ελληνικά (Greek)',
								'en' => 'English',
								'en-GB' => 'British English',
								'en-US' => 'US English',
								'es' => 'Castellano (Spanish)',
								'et' => 'Eesti keel (Estonian)',
								'eu' => 'Euskara (Basque)',
								'fi' => 'Suomi (Finnish)',
								'fr' => 'Français (French)',
								'fr-BE' => 'Français Belgique (French)',
								'fr-FR' => 'Français France (French)',
								'ga' => 'Gaeilge (Irish)',
								'hu' => 'Magyar (Hungarian)',
								'is' => 'Íslenska (Icelandic)',
								'it' => 'Italiano (Italian)',
								'lb' => 'Lëtzebuergesch (Luxembourgish)',
								'lt' => 'Lietuvių kalba (Lithuanian)',
								'lv' => 'Latviešu valoda (Latvian)',
								'mt' => 'Malti (Maltese)',
								'nb' => 'Norsk bokmål (Norwegian)',
								'nl' => 'Nederlands (Dutch)',
								'nn' => 'Norsk nynorsk (Norwegian)',
								'no' => 'Norsk (Norwegian)',
								'pl' => 'Polski (Polish)',
								'pt' => 'Português (Portuguese)',
								'ro' => 'Română (Romanian)',
								'ru' => 'Русский язык (Russian)',
								'sk' => 'Slovenčina (Slovak)',
								'sl' => 'Slovenščina (Slovenian)',
								'sv' => 'Svenska (Swedish)'
		);

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

	/**
	 * Return the name of a ISO 639-1 language code in verbose, human-readable
	 * form.
	 *
	 * The current list is not comprehensive, but should contain the
	 * codes we are expecting to need in the foreseeable future.
	 *
	 * @param $iso_codes An array with the ISO-codes that should be mapped
	 * @return Array with full description of the passed ISO-codes
	 */
	public static function getFullNamesForISOCodes($iso_codes)
	{
		$full_names = array();

		foreach($iso_codes as $code) {
			$language = Translator::$code_language_map[$code];

			if (isset($language)) {
				$full_names[$code] = $language;
			} else {
				$full_names[$code] = '[' . $code . ']';
			}
		}

		return $full_names;
	}
}

?>
