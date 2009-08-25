<?php
require_once 'confusa_auth.php';
require_once 'MapNotFoundException.php';
/**
 * Confusa_Auth_Bypass - The dummy authentication source.
 *
 * Should be active only in test configurations when the respective "auth_bypass"
 * configuration flag is set.
 * Decorates the person object without asking too many questions :)
 */
class Confusa_Auth_Bypass extends Confusa_Auth
{
	private $attributes;

	function __construct($person)
	{
		parent::__construct($person);
		$this->attributes = array(
			'cn2'				=> array('Ola Nordmann'),
			'eduPersonPrincipalName'	=> array('ola.nordmann@norge.no'),
			'mail2'				=> array('ola.nordmann@norge.no'),
			'country'			=> array('NO'),
			'organization'			=> array('test_subscriber'),
			'nren'				=> array('testnren'),
			'eduPersonEntitlement2'		=> array('confusaAdmin')
			);
	}

	/**
	 * Decorate the person object with dummy attributes
	 */
	public function authenticateUser()
	{
		$this->person->setAuth(true);
		$this->decoratePerson($this->attributes);
		return $this->person->isAuth();
	}

	/**
	 * Decorate the person object with dummy attributes and return always true
	 */
	public function checkAuthentication()
	{
		$this->person->setAuth(true);
		$this->authenticateUser();
		return $this->person->isAuth();
	}

	/**
	 * no operation
	 *
	 * @param $logout_loc The location to which the user is redirected
	 */
	public function deAuthenticateUser($logout_loc = 'logout.php')
	{
		Framework::error_output("Cannot log out, you're in bypass-mode!");
	}
	public function softLogout()
	{
		;
	}
}

?>
