<?php
require_once 'config.php';
require_once 'logger.php';
require_once 'person.php';
require_once 'auth.php';
require_once 'confusa_auth.php';

/**
 * Confusa_Auth_IdP - authenticate user via an identity provider
 *
 * This is the traditional and somewhat standard authentication method for
 * Confusa. The user logs in with an IdP and the authN status and the attributes
 * are consumed from a SAML response. The authN status of the user is
 * tagged to a session.
 */
class Confusa_Auth_IdP extends Confusa_Auth
{
	/* hold the Auth_Simple object from SimpleSAMLphp */
	private $as;

	/**
	 * Constructor
	 *
	 * Note that the person is tied to a session and a simplesaml configuration
	 * here
	 */
	function __construct($person = NULL)
	{
		parent::__construct($person);

		/* Find the path to simpelsamlphp and run the autoloader */
		try {
			$sspdir = Config::get_config('simplesaml_path');
		} catch (KeyNotFoundException $knfe) {
			echo "Cannot find path to simplesaml. This install is not valid. Aborting.<br />\n";
			Logger::log_event(LOG_ALERT, "Tryging to instansiate SimpleSAMLphp without a configured path.");
			exit(0);
		}
		require_once $sspdir . '/lib/_autoload.php';
		SimpleSAML_Configuration::setConfigDir($sspdir . '/config');

		/* start a session needed for the IdP-based AuthN approach */
		$this->as = new SimpleSAML_Auth_Simple('default-sp');
		$session = SimpleSAML_Session::getInstance();
		$this->person->setSession($session);
	}

	/**
	 * authenticateUser() run the current user through authN-hoops
	 *
	 * This function will make sure that the user is authenticated. Once
	 * done, the person will be authenticated and decorated.
	 *
	 * Depending on state, do one of the following:
	 *		- Use the subsystem to perform an IdP authN
	 *		- Decorate the person object with attributes
	 */
	public function authenticateUser()
	{
		/* is the user authNed according to simplesamlphp */
		if (!$this->person->isAuth()) {
			$this->as->requireAuth();
		} else if ($this->validAuth) {
			$idp = $this->session->getIdP();
			$attributes = $this->as->getAttributes();
			$this->session->setAttribute('idp', array($idp));
			$this->person->setAuth($this->validAuth);
			$this->decoratePerson($attributes, $idp);
		}
		$attributes = $this->as->getAttributes();
		if (!isset($attributes['eduPersonPrincipalName'])) {
			Logger::log_event(LOG_ERROR, "IdP did not send any eduPersonPrincipalName. " .
							 "The rest of the attributes are " . implode(" ", $attributes));
			throw new AuthException("Required attribute eduPersonPrincipalName not set!");
		}

		$this->person->setAuth($this->checkAuthentication());
	}

	/**
	 * @return the attributes as they are stored in the session
	 */
	public function getAttributes()
	{
		return $this->as->getAttributes();
	}

	/**
	 * getAttributeKeys() get the keys used to index the attributes
	 *
	 * This will return all the keys for the current attributes except a
	 * few (those that we create internally in Confusa and ePPN).
	 * 
	 * @param void
	 * @return Array the list of keys used to index the attributes.
	 */
	public function getAttributeKeys()
	{
		$res = array();
		$attrs = $this->getAttributes();
		foreach ($attrs as $key => $value) {
			switch ($key) {
			case "country":
			case "nren":
			case "eduPersonPrincipalName":
				break;
			default:
				$res[] = $key;
				break;
			}
		}
		return $res;
	}

	/**
	 * deAuthentcateUser() - Use the subsystem to logout
	 *
	 * @param String $logout_loc the location to which the user will be redirected after logout
	 * @return void
	 */
	public function deAuthenticateUser($logout_loc = 'logout.php')
	{
		if ($this->checkAuthentication()) {
			$this->person->isAuth(false);
			$this->person->clearAttributes();
			$this->as->logout(Config::get_config('server_url') . "$logout_loc");
		}
	} /* end deAuthenticateUser */

	/**
	 * Poll the subsystem for user authentication
	 * Decorate the person object with the attributes received from the subsystem.
	 *
	 * @return True, if person is authenticated, false if not.
	 */
	public function checkAuthentication()
	{
		if (is_null($this->person)) {
			return false; /* anonymous cannot be AuthN */
		}
		if (is_null($this->person->getSession())) {
			return false; /* no session, thus, we *cannot* be authN */
		}

		$session = $this->person->getSession();
		$this->person->setSession($session);
		$this->person->setSAMLConfiguration(SimpleSAML_Configuration::getInstance());

                /*
                 * authority is normally default-sp, but in case we/someone want
                 * to extend this, use the current authority without reverting
                 * to hard-coded values.
				*/
		$idp = $session->getIdP();

				/* If no idp isset, then problem the user is authenticated using a non-SAML
				 * method, e.g. as simplesamlphp admin. The user should not be auth,
				 * if no IdP is set (as no NREN can be constructed in that case) */
                if (is_null($session->getAuthority()) || empty($idp)) {
                        return false; /* cannot get authority for session, thus
                                       * we cannot be authenticated. */
                }
		$this->person->setAuth($session->isValid($session->getAuthority()));


		if ($this->person->isAuth()) {
			$this->decoratePerson($this->as->getAttributes(), $idp);
			return true;
		}
		/* Session is invalid, thus user is not authN */
		return false;
	} /* end checkAuthentication() */

} /* end class IdP */
?>
