<?php
abstract class ContentPage
{

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