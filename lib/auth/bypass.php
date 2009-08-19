<?php
require_once 'confusa_auth.php';

/**
 * Confusa_Auth_Bypass - The dummy authentication source.
 *
 * Should be active only in test configurations when the respective "auth_bypass"
 * configuration flag is set.
 * Decorates the person object without asking too many questions :)
 */
class Confusa_Auth_Bypass extends Confusa_Auth
{

	function __construct($person)
	{
		parent::__construct($person);
	}

	/**
	 * Decorate the person object with dummy attributes
	 */
	public function authenticateUser()
	{
		$this->person->setName('Ola Nordmann');
		$this->person->setEPPN('ola.nordmann@norge.no');
		$this->person->setEmail('ola.nordmann@norge.no');
		$this->person->setCountry('NO');
		$this->person->setSubscriberOrgName('Test');
		$this->person->setIdP('test-idp');
		$this->person->setNREN('TEST-NREN');
		$this->person->setEduPersonEntitlement('confusaAdmin');
		$this->person->setAuth(true);

	}

	/**
	 * Decorate the person object with dummy attributes and return always true
	 */
	public function checkAuthentication()
	{
		$this->authenticateUser();
		return true;
	}

	/**
	 * no operation
	 *
	 * @param $logout_loc The location to which the user is redirected
	 */
	public function deAuthenticateUser($logout_loc = 'logout.php')
	{
	}
}

?>
