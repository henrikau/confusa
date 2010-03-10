<?php
require_once 'Config.php';
require_once 'logger.php';
require_once 'Person.php';
require_once 'Person.php';
require_once 'Confusa_Auth.php';

/**
 * Confusa_Auth_OAuth - authenticate user via an OAuth datastore
 *
 * This Auth-Manager uses simplesamlphp's OAuth module to decorate the person
 * object via an OAuth-datastore
 *
 * @package auth
 */
class Confusa_Auth_OAuth extends Confusa_Auth
{
	/* hold the OAuth data-store from simplesamlphp */
	private $oauthStore;
	/* hold the OAuth server controller class */
	private $oauthServer;
	/* the oauth access token */
	private $accessToken;

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

		$this->oauthStore = new sspmod_oauth_OAuthStore();
		$this->oauthServer = new sspmod_oauth_OAuthServer($this->oauthStore);
		$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();

		$this->oauthServer->add_signature_method($hmac_method);

		$req = OAuthRequest::from_request();
		list($consumer, $this->accessToken) = $this->oauthServer->verify_request($req);
		$this->validAuth = isset($this->accessToken);
	} /* end Constructor */

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
	public function authenticate($isRequired)
	{
		if ($this->validAuth) {
			$attributes = $this->oauthStore->getAuthorizedData($this->accessToken->key);

			if (isset($attributes['entityID'])) {
				$idp = $attributes['entityID'][0];
			} else {
				throw new CGE_AuthException("Can not authenticate via OAuth, because the " .
				                            "entityID attribute is missing! We have no way " .
				                            "of finding out the IdP.");
			}

			$this->decoratePerson($attributes, $idp);
			$this->person->setAuth($this->validAuth);
		}
	} /* end authenticate */

	/**
	 * @return the attributes as they are stored in the OAuth data store
	 */
	public function getAttributes()
	{
		return $this->store->getAuthorizedData($this->accessToken->key);
	} /* getAttributes */

	/**
	 * @see Confusa_Auth::getAttributeValue()
	 */
	public function getAttributeValue($key)
	{
		$attributes = $this->store->getAuthorizedData($this->accessToken->key);

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

	/*
	 * reAuthenticate - simplesamlphp currently does not support disabling or
	 * expiring an OAuth token.
	 */
	public function reAuthenticate()
	{
		if ($this->validAuth) {
			echo "OAuth does currently not support reAuthentication\n";
		} else {
			$this->authenticate(TRUE);
		}
	}

	/**
	 * deAuthentcateUser() - with current simplesamlphp, there is no real
	 * logging out with OAuth.
	 * Access tokens are not expired by simplesamlphp and there are no API
	 * calls for deleting them.
	 *
	 * @param String $logout_loc the location to which the user will be redirected after logout
	 * @return void
	 */
	public function deAuthenticate($logout_loc = 'logout.php')
	{
		if ($this->validAuth) {
			$this->person->isAuth(FALSE);
			echo "OAuth does currently not support deauthenticating users\n";
		}
	} /* end deAuthenticateUser */

} /* end class IdP */
?>
