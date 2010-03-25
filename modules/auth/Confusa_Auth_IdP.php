<?php
require_once 'Config.php';
require_once 'Logger.php';
require_once 'Person.php';
require_once 'Person.php';
require_once 'Confusa_Auth.php';

/**
 * Confusa_Auth_IdP - authenticate user via an identity provider
 *
 * This is the traditional and somewhat standard authentication method for
 * Confusa. The user logs in with an IdP and the authN status and the attributes
 * are consumed from a SAML response. The authN status of the user is
 * tagged to a session.
 *
 * @package auth
 */
class Confusa_Auth_IdP extends Confusa_Auth
{
	/* hold the Auth_Simple object from SimpleSAMLphp */
	private $as;
	/* hold the simplesamlphp-session */
	private $session;
	/* hold the simplesamlphp-configuration */
	private $samlConfig;
	/* state variable keeping the status of the backend authentication */
	private $isAuthenticated;

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
		$this->session = SimpleSAML_Session::getInstance();
		$this->samlConfig = SimpleSAML_Configuration::getConfig();

		/*
		 * authority is normally default-sp, but in case we/someone want
		 * to extend this, use the current authority without reverting
		 * to hard-coded values.
		 */
		$idp = $this->session->getIdP();
		/* If no idp isset, then problem the user is authenticated using a non-SAML
		 * method, e.g. as simplesamlphp admin. The user should not be auth,
		 * if no IdP is set (as no NREN can be constructed in that case) */
		if (is_null($this->session->getAuthority()) || empty($idp)) {
			$this->person->setAuth(false);
			$this->isAuthenticated = false;
		} else {
			$this->isAuthenticated = $this->session->isValid($this->session->getAuthority());
		}
	}

	/**
	 * authenticate() run the current user through authN-hoops
	 *
	 * This function will make sure that the user is authenticated. Once
	 * done, the person will be authenticated and decorated.
	 *
	 * Depending on state, do one of the following:
	 *		- Use the subsystem to perform an IdP authN
	 *		- Decorate the person object with attributes
	 *
	 */
	public function authenticate($authRequired)
	{
		/* is the user authNed according to simplesamlphp */
		if ($this->isAuthenticated) {
			$idp = $this->session->getIdP();
			$this->session->setAttribute('idp', array($idp));
			$attributes = $this->as->getAttributes();
			$this->person->setAuth(TRUE);
			$this->decoratePerson($attributes, $idp);
		} else if (!$this->isAuthenticated && $authRequired) {
			$this->as->requireAuth();
		}
	}

	/**
	 * @return the attributes as they are stored in the session
	 */
	public function getAttributes()
	{
		return $this->as->getAttributes();
	} /* getAttributes */

	/**
	 * @see Confusa_Auth::getAttributeValue()
	 */
	public function getAttributeValue($key)
	{
		$attributes = $this->as->getAttributes();

		if (isset($attributes[$key])) {
			return $attributes[$key];
		} else {
			return "";
		}
	} /* end getAttributeForKey */

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

	public function reAuthenticate()
	{
		if ($this->isAuthenticated) {
			$totalTime = $this->samlConfig->getValue('session.duration');
			$remainingTime = $this->session->remainingTime();
			$passedTime = $totalTime - $remainingTime;

			$nren = $this->person->getNREN();

			if (isset($nren)) {
				$timeout = $nren->getReauthTimeout();
			} else {
				$timeout = ConfusaConstants::$DEFAULT_REAUTH_TIMEOUT;
			}

			$timeout = $timeout*60;

			if ($passedTime > $timeout) {
				/* logout redirects to the current page by default */
				$this->as->logout();
			}
		}
	}

	/**
	 * deAuthentcateUser() - Use the subsystem to logout
	 *
	 * @param String $logout_loc the location to which the user will be redirected after logout
	 * @return void
	 */
	public function deAuthenticate($logout_loc = 'logout.php')
	{
		if ($this->isAuthenticated) {
			$this->person->isAuth(false);
			$this->person->clearAttributes();
			$this->as->logout(Config::get_config('server_url') . "$logout_loc");
		}
	} /* end deAuthenticateUser */

} /* end class IdP */
?>
