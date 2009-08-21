<?php
require_once 'translator.php';

abstract class FW_Content_Page
{
	private $title;
	private $protected;
	protected $tpl;
	protected $certManager;
	protected $person;
	protected $dictionary;
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
		$this->certManager = null;
		$this->dictionary = $dictionary;
	}

	function __destruct()
	{
		unset($this->title);
		unset($this->protected);
		unset($this->certManager);
		unset($this->person);
	}

	public function setManager()
	{
		if (!isset($this->person)) {
			Framework::error_output("You are trying to set the certManager before person is set!");
			return;
		}
		$this->certManager = CertManagerHandler::getManager($this->person);
	}

	public function getManager()
	{
		return $this->certManager;
	}
	
	public function setTpl(Smarty $tpl)
	{
		$this->tpl = $tpl;
	}

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
		$this->setManager();

		/* show the available languages in the template */
		$available_languages = Config::get_config('language.available');
		$this->tpl->assign('available_languages',
							Translator::getFullNamesForISOCodes($available_languages));

		if (isset($_GET['lang'])) {
			$lang = Input::sanitize($_GET['lang']);
			$_SESSION['language'] = $lang;
		}

		/* If we have a dictionary for this component, translate the entries */
		if (isset($this->dictionary)) {
			/* Get the translation in place */
			$trans = new Translator($person);
			$this->tpl = $trans->decorateTemplate($this->tpl, $this->dictionary);

		}

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
