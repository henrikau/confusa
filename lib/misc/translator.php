<?php
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
	}

	/**
	 * Return the currently set language
	 *
	 * @return The language that is currently set
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Decorate a given template with the tags from the dictornary in the
	 * right language. This is nothing more than repeated consulation of a
	 * LUT. Don't decorate the template if the passed dictionary is null or
	 * the file does not exists.
	 *
	 * @param $template The template that is to be decorated
	 * @param $dictionary The dictionary from which the texts are taken
	 *
	 * @return The decorated template
	 */
	public function decorateTemplate($template, $dictionary)
	{
		/* if the dictionary is null or does not exist, don't decorate the template */
		if (is_null($dictionary)) {
			return $template;
		}

		$dictionaryPath = Config::get_config('install_path') . "/dictionaries/" . $dictionary;

		if (file_exists($dictionaryPath) === FALSE) {
			return $template;
		}

		/* warn only *once* if dictionary entries are missing */
		$warn_missing=FALSE;
		include($dictionaryPath);
		foreach($lang as $tag => $entry) {
			if (isset($entry[$this->language])) {
				$template->assign($tag, $entry[$this->language]);
			} else {
				$template->assign($tag, $entry[$this->defaultLanguage]);

				if (!isset($entry[$this->defaultLanguage])) {
					Logger::log_event(LOG_WARNING, "Missing tranlsation entry for $tag in " . __FILE__);

					if ($warn_missing === FALSE) {
						Framework::warning_output("Translation problem: The dictionary " .
												"for this page does not contain any entry for " .
												"certain texts! Some parts of the page may appear blank.");
						$warn_missing = TRUE;
					}
				}
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
		if (isset($_SESSION['language'])) {
			return $_SESSION['language'];
		}

		if ($this->person->isAuth()) {

			try {
				$query = "SELECT lang FROM subscribers WHERE name=?";
				$res = MDB2Wrapper::execute($query,
							    array('text'),
							    array($this->person->getSubscriber()->getIdPName()));

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
			} catch (DBQueryException $dbqe) {
				Logger::log_event(LOG_WARNING, "Could not query subscriber/NREN default language. " .
								  "Falling back to system language default! " . $dbqe->getMessage());
			} catch (DBStatementException $dbse) {
				Logger::log_event(LOG_WARNING, "Could not query subscriber/NREN default language. " .
								  "Falling back to system default! " . $dbse->getMessage());
			}
		}

		$sspdir = Config::get_config('simplesaml_path');
		/* turn off warnings to keep the page header tidy */
		$level = error_reporting(E_ERROR);

		/* poll the accept languages only, if we can load simplesamlphp
		 * simplesamlphp *should* always be enabled (otherwise no authN :)),
		 * But there can be configurations in bypass auth-mode without a working
		 * simplesamlphp instance
		 */
		if (include_once $sspdir . '/lib/_autoload.php') {
			$accept_languages = SimpleSAML_Utilities::getAcceptLanguage();
			$available_languages = Config::get_config('language.available');
			Logger::log_event(LOG_DEBUG, "Simplesamlphp instance seems to be not " .
									"configured, or not configured properly. Translator " .
									"will not use the browser's accept-header to determine " .
									"language settings.");

			foreach($accept_languages as $key => $value) {
				if (array_search($key, $available_languages) === FALSE) {
					continue;
				} else {
					return $key;
				}
			}
		}

		/* turn on warnings again */
		error_reporting($level);

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
