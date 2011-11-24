<?php
require_once 'Translator.php';

abstract class Content_Page
{
	private $title;
	private $protected;
	protected $tpl;
	protected $ca;
	protected $person;
	protected $dictionary;
	protected $translator;

	/**
	 * Constructor - create the Content Page.
	 *
	 * @title	- the title to display in the header of the page.
	 * @protected	- If the page required an AuthN' user or not.
	 *
	 */
	function __construct($title = "", $protected = true, $dictionary = NULL)
	{
		$this->title = $title;
		$this->protected = $protected;
		$this->ca = null;
		$this->dictionary = $dictionary;
		$this->translator = new Translator();
	}

	function __destruct()
	{
		unset($this->title);
		unset($this->protected);
		unset($this->ca);
		unset($this->person);
		unset($this->translator);
	}

	public function setCA()
	{
		if (!isset($this->person)) {
			Framework::error_output("You are trying to set the CA before person is set!");
			return;
		}
		$this->ca = CAHandler::getCA($this->person);
		if (!$this->ca) {
			throw new ConfusaGenException("Could not instantiate CA-object. ".
						      "Notify operational support and check the logs.");
		}
	}

	public function getCA()
	{
		return $this->ca;
	}

	public function setTpl(Smarty $tpl)
	{
		$this->tpl = $tpl;
	}

	/**
	 * Get the translator member of this content_page
	 * @return Translator that was constructed with this content_page
	 */
	public function getTranslator()
	{
		return $this->translator;
	} /* end getTranslator */

	/**
	 * Translate a tag output by Framework::*_output. All of these
	 * messages should be translated in messages.definition.json.
	 *
	 * @param $tag string The tag that should be translated
	 * @return string the translated tag
	 */
	public function translateMessageTag($tag)
	{
		return $this->translator->getTextForTag($tag, 'messages');
	} /* end translateMessageTag */

	/**
	 * Translate a tag using a specified dictionary.
	 *
	 * @param $tag string The tag that should be translated
	 * @param $dictionaryName string The name of the dictionary in which to
	 *                               look the tag up
	 * @return string The translated tag
	 */
	public function translateTag($tag, $dictionaryName)
	{
		return $this->translator->getTextForTag($tag, $dictionaryName);
	} /* end translateTag */

	public function setPerson($person)
	{
		if (!isset($person)) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . " Trying to set a non-existing person!");
			return;
		}
		$this->person = $person;
	}

	public function get_title() { return $this->title; }
	public function is_protected() { return $this->protected; }

	/**
	 * pre_process()- function to call before render but *after*
	 *		  authentication has finished.
	 *
	 *		  This function will not be of any use if something must
	 *		  be done before authentication is run, in a forced
	 *		  authenticated setup. (You should do that by doing the
	 *		  business needed before calling framework into action).
	 *
	 * @person : the decorated person (if authenticated)
	 */
	function pre_process($person)
	{
		$this->setPerson($person);
		$this->setCA();

		/* if the nren of the useris in maint-mode, trigger a warning here */
		if ($this->person->getNREN()->inMaintMode()) {
			$this->tpl->assign('instance', Config::get_config('system_name'));
			$this->tpl->assign('maint_header', $this->translateTag('l10n_nren_maint_header', 'portal_config'));
			$this->tpl->assign('maint_msg', $this->person->getNREN()->getMaintMsg());
			if ($this->person->isNRENAdmin()) {
				$this->tpl->assign('mode_toggle', true);
				$this->tpl->assign('mode_toggle_text', $this->translateTag('l10n_nren_maint_mode_text', 'portal_config'));
				$this->tpl->assign('mode_toggle_button', $this->translateTag('l10n_nren_maint_mode_button', 'portal_config'));
			}
			$this->tpl->assign('maint', $this->tpl->fetch('nren_maint.tpl'));
			$this->tpl->display('site.tpl');
			exit(0);
		}



		/* show the available languages in the template */
		$available_languages = Config::get_config('language.available');
		$this->tpl->assign('available_languages',
							Translator::getFullNamesForISOCodes($available_languages));

		$this->translator->guessBestLanguage($person);

		$this->tpl = $this->translator->decorateTemplate($this->tpl, 'menu');
		$this->tpl = $this->translator->decorateTemplate($this->tpl, $this->dictionary);
		$this->tpl->assign('selected_language', $this->translator->getLanguage());
		return false;
	}

	/**
	 * Show an error (in the framework) about an invalid character found
	 * during sanitation.
	 *
	 * @param $original The original string, e.g. as it was received via the
	 *                  POST array
	 * @param $sanitized The string as it appeared after sanitizing it
	 * @param $dictEntry The dictionary entry to look up from the dictionary
	 *                   when referring to the input element that cause the
	 *                   sanitation.
	 * @param $dictionary The dictionary from which the entry should be looked
	 *                    up. If this is NULL, the current page's dictionary
	 *                    will be used by default.
	 */
	protected function displayInvalidCharError($original,
	                                        $sanitized,
	                                        $dictEntry = NULL,
											$dictionary = NULL) {
		$invalidChars = Input::findSanitizedCharacters($original, $sanitized);
		$errorMsg = "";

		if (empty($dictionary)) {
			$dictionary = $this->dictionary;
		}

		if (isset($dictEntry)) {
			$errorMsg .= $this->translateTag($dictEntry, $this->dictionary);
		}

		$errorMsg .= " ";
		$errorMsg .= $this->translateTag('l10n_err_sanitation',
					                     'messages');
		$errorMsg .= " $invalidChars";
		Framework::error_output($errorMsg);
	}


	/**
	 * process()	- the main content-page processingfunction. This is
	 *		  where you want to do the "main business".
	 */
	abstract function process();


	/**
	 * post_process() - the last function to run before framework exits, but
	 *		  before framework runs the internal cleanups.
	 *
	 */
	function post_process()
	{
		;
	}
}
?>
