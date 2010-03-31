<?php
require_once 'Person.php';
require_once 'Confusa_Auth.php';

/**
 * AuthHandler - return the right authentication manager for the configuration
 *
 * The handler should abstract that decision away from the calling functions
 * and consult on its own on the configuration or environment.
 *
 * @package auth
 */

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
				require_once 'Confusa_Auth_Bypass.php';
				AuthHandler::$auth = new Confusa_Auth_Bypass($person);
			} else {
				/* Start the IdP and create the handler */
				require_once 'Confusa_Auth_IdP.php';
				AuthHandler::$auth = new Confusa_Auth_IdP($person);
			}
		}
		return AuthHandler::$auth;
	}
} /* end class AuthHandler */
?>
