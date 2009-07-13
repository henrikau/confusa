<?php
abstract class ContentPage
{
	private $title;
	private $protected;


	/**
	 * Constructor - create the Content Page.
	 *
	 * @title	- the title to display in the header of the page.
	 * @protected	- If the page required an AuthN' user or not.
	 */
	function __construct($title = "", $protected = true)
	{
		$this->title = $title;
		$this->protected = $protected;
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
	abstract function pre_process($person);


	/**
	 * process()	- the main content-page processingfunction. This is
	 *		  where you want to do the "main business".
	 */
	abstract function process($person);


	/**
	 * post_process() - the last function to run before framework exits, but
	 *		  before framework runs the internal cleanups.
	 * 
	 */
	abstract function post_render($person);
}
?>