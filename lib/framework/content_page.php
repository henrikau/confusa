<?php
require_once 'translator.php';

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
