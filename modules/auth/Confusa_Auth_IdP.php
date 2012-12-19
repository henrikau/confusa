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
			Logger::logEvent(LOG_ALERT, "Confusa_Auth_IdP", "__construct()",
			                 "Trying to instantiate SimpleSAMLphp without a configured path.");
			exit(0);
		}
		require_once $sspdir . '/lib/_autoload.php';
		SimpleSAML_Configuration::setConfigDir($sspdir . '/config');

		/* start a session needed for the IdP-based AuthN approach */
		$this->as = new SimpleSAML_Auth_Simple('default-sp');
		$this->session = SimpleSAML_Session::getInstance();
	}

	/**
	 * authenticate() run the current user through authN-hoops
	 *
	 * This function will make sure that the user is authenticated. Once
	 * done, the person will be authenticated and decorated. Only if the
	 * authRequired parameter is set, authentication will be forced. If
	 * authRequired is false, the person will only be decorated if the user
	 * already has an authN session.
	 *
	 * Depending on state, do one of the following:
	 *		- Use the subsystem to perform an IdP authN
	 *		- Decorate the person object with attributes
	 *
	 * @param $authRequired boolean If true, requireAuth, if the person does
	 *                              not have a valid authN state. If false, only
	 *                              reuse existing authN state, do not require
	 *                              auth.
	 *
	 */
	public function authenticate($authRequired)
	{
		/* is the user authNed according to simplesamlphp */
		if ($this->as->isAuthenticated()) {
			$idp = $this->session->getIdP();
			$this->session->setAttribute('idp', array($idp));
			$attributes = $this->as->getAttributes();
			$this->person->setAuth(TRUE);
			$this->decoratePerson($attributes, $idp);
		} else if (!$this->as->isAuthenticated() && $authRequired) {
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

	/**
	 * If the user is authenticated, check the per NREN timeout to see if there
	 * is need for reauntication.
	 * If so, log the user out, which will force a reauthentication if the
	 * user is on a protected page.
	 *
	 * @return void
	 */
	public function reAuthenticate()
	{
		if ($this->as->isAuthenticated()) {
			$samlConfig = SimpleSAML_Configuration::getInstance();
			$totalTime = $samlConfig->getValue('session.duration');
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
	 * deAuthenticateUser() - Use the subsystem to logout
	 *
	 * @param String $logout_loc the location to which the user will be redirected after logout
	 * @return void
	 */
	public function deAuthenticate($logout_loc = 'logout.php')
	{
		if ($this->as->isAuthenticated()) {
			$this->person->isAuth(false);
			$this->person->clearAttributes();
			$this->as->logout(Config::get_config('server_url') . "$logout_loc");
		}
	} /* end deAuthenticateUser */

	public function getDiscoPath()
	{
		$sspdir		 = Config::get_config('simplesaml_path');
		require_once $sspdir . 'lib/_autoload.php';
		SimpleSAML_Configuration::setConfigDir($sspdir . '/config');
		$sspConfig	 = SimpleSAML_Configuration::getInstance();
		$discoPath = "https://" . $_SERVER['SERVER_NAME'] . "/" .
			$sspConfig->getString('baseurlpath') .
			"module.php/saml/disco.php?" .
			$_SERVER['QUERY_STRING'];
		return $discoPath;
	}
} /* end class IdP */
?>
