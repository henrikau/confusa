<?php

require_once 'person.php';
require_once 'confusa_config.php';
require_once 'config.php';
$sspdir = Config::get_config('simplesaml_path');
require_once $sspdir . '/lib/_autoload.php';
SimpleSAML_Configuration::setConfigDir($sspdir . '/config');

/**
 * Confusa_Auth - base class for all authentication managers
 *
 * classes providing authN are supposed to implement all three of
 * 		- authenticateUser()
 * 		- checkAuthentication()
 * 		- deAuthenticateUser()
 */
abstract class Confusa_Auth
{
	/* the person that is authenticated by Confusa */
	protected $person;

	function __construct($person = NULL)
	{
		if (is_null($person)) {
			$this->person = new Person();
		} else {
			$this->person = $person;
		}
	}

	function __destruct()
	{
		unset($this->person);
	}

	/**
	 * Get the person object associated with this authN class
	 *
	 * @return the person member of this class
	 */
	public function getPerson()
	{
		return $this->person;
	}

	/**
	 * Authenticate the idenitity of a user, using a free-of-choice method to be
	 * implemented by subclasses
	 */
	public abstract function authenticateUser();
	/**
	 * Check (possibly by polling a subsystem), if a user is still authN.
	 * @return True or false, reflecting the authN status
	 */
	public abstract function checkAuthentication();
	/**
	 * "Logout" the user, possibly using the subsystem. To be implemented by
	 * subclasses
	 */
	public abstract function deAuthenticateUser();
}

/**
 * AuthHandler - return the right authentication manager for the configuration
 *
 * The handler should abstract that decision away from the calling functions
 * and consult on its own on the configuration or environment
 */
require_once 'idp.php';
require_once 'bypass.php';
class AuthHandler
{
	private static $auth;
	/**
	 * Get the auth manager based on the request
	 *
	 * @param $person The person for which the auth_manager should be created
	 * @return an instance of Confusa_Auth
	 */
	public static function getAuthManager($person)
	{
		if (!isset(AuthHandler::$auth)) {

			if (Config::get_config('auth_bypass') === TRUE) {
				AuthHandler::$auth = new Confusa_Auth_Bypass($person);
			} else {
				AuthHandler::$auth = new Confusa_Auth_IdP($person);
			}
		}

		return AuthHandler::$auth;
	}
}

?>
