<?php
require_once 'confusa_include.php';
require_once 'Confusa_Auth_OAuth.php';
require_once 'Person.php';

/**
 * Abstract base class for all other API classes. Includes common functionality
 * such as error handlers and OAuth usage authorization.
 *
 * @since v0.6-rc0
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
abstract class API
{
	/* OAuth authentication class */
	protected $auth;
	/* decorated person object from the auth-handler */
	protected $person;
	/* the parameters that are parsed from the API-request */
	protected $parameters;

	function __construct()
	{
		/* will not allow OAuth operations with magic_quotes_gpc! */
		if (ini_get('magic_quotes_gpc') == '1') {
			$this->errorInternal("magic_quotes_gpc is activated on this " .
			                     "server. As this will lead to problems " .
			                     "with OAuth signature verification, please " .
			                     "tell an administrator to switch it off!\n");
			exit(1);
		}

		$this->person = new Person();

		try {
			$this->auth = new Confusa_Auth_OAuth($this->person);
			$this->auth->authenticate(TRUE);
		} catch (Exception $e) {
			$this->errorAuth();
			exit(0);
		}

		$this->parameters = array();
		set_exception_handler(array("API_Certificates", "errorUncaughtException"));
	} /* end Constructor */

	/** here the actual API processing will happen */
	public abstract function processRequest();

	/**
	 * tell the REST client that either on its side or on server side something
	 * went wrong
	 */
	protected function errorBadRequest($msg = "")
	{
		header("HTTP/1.1 400 Bad request");
		echo "What you have supplied does not look like a legal request.\n";
		echo $msg;
		exit(1);
	} /* end errorBadRequest */

	/**
	 * tell the REST client that it did not authorize itself correctly or at all
	 */
	protected function errorAuth()
	{
		header("HTTP/1.1 403 Forbidden");
		echo "You need a valid access token to perform API requests.\n";
		echo "Either you did not have that or your access token expired.\n";
		echo "Note that depending on NREN settings, token expiry can happen\n";
		echo "within a rather short time-period.\n";
		exit(1);
	} /* end errorAuth */

	protected function errorNotAuthorized($permission)
	{
		header("HTTP/1.1 412 Precondition failed");
		echo "You may not perform any operations on the certificate endpoint,";
		echo "because: " . $permission->getStringReasons() . "\n";
		exit(1);
	} /* end errorNotAuthorized */

	protected function errorInternal($msg = "")
	{
		header("HTTP/1.1 500 Internal server error");
		echo "An unforeseen problem occured when processing your request.\n";
		echo "Maybe something is misconfigured. Contact the server\n";
		echo "administrators\n";
		echo $msg;
		exit(1);
	}

	public static function errorUncaughtException(Exception $e)
	{
		header("HTTP/1.1 500 Internal server error");
		echo "An uncaught exception was thrown while processing your request:\n";
		echo $e->getMessage() . "\n";
		exit(1);
	} /* end errorUncaughtException */
} /* end class API */
?>
