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
	/**
	 * Constructor
	 *
	 * Note that the person is tied to a session and a simplesaml configuration
	 * here
	 */
	function __construct($person = NULL)
	{
		parent::__construct($person);

		/* start a session needed for the IdP-based AuthN approach */
		$session = SimpleSAML_Session::getInstance();
		$authority = $session->getAuthority();

		/* if we don't get an authority from the session, just fallback to
		 * default */
		if (empty($authority)) {
			$authority = ConfusaConstants::$DEFAULT_SESSION_AUTHORITY;
		}

		if (!$session->isValid($authority)) {
			session_start();
		}

		$this->person->setSession($session);
		$this->person->setSAMLConfiguration(SimpleSAML_Configuration::getInstance());
	}

	/**
	 * Depending on state, do one of the following:
	 *		- Use the subsystem to perform an IdP authN
	 *		- Decorate the person object with attributes
	 */
	public function authenticateUser()
	{
		/* is the user authNed according to simplesamlphp */
		if (!$this->person->isAuth()) {
			$base_url = $this->person->getSAMLConfiguration()->getBaseURL();
			$relay = Config::get_config('server_url') . Config::get_config('post_login_page');
			SimpleSAML_Utilities::redirect('/' . $base_url . 'saml2/sp/initSSO.php',
										  array('RelayState' => $relay));
			exit(0);
		}

		$attributes = $this->person->getSession()->getAttributes();

		if (!isset($attributes['eduPersonPrincipalName'])) {
			Logger::log_event(LOG_ERROR, "IdP did not send any eduPersonPrincipalName. " .
							 "The rest of the attributes are " . implode(" ", $attributes));
			throw new AuthException("Required attribute eduPersonPrincipalName not set!");
		}

		$this->assertAttributes($attributes);
		$this->person->setAuth($this->person->getSession()->isValid());
	}

	/**
	 * @return the attributes as they are stored in the session
	 */
	protected function getAttributes()
	{
		return $this->person->getSession()->getAttributes();
	}

	public function getAttributeKeys()
	{
		$res = array();
		$attrs = $this->person->getSession()->getAttributes();
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
	 * Use the subsystem to perform a single logout (SLO).
	 *
	 * For those IdPs with which single logout does not work, try to use the
	 * IdP-specific mechanisms, but fallback on SingleLogout for all other
	 * scenarios
	 *
	 * @param $logout_loc string the location to which the user will be
	 * redirected after logout
	 * @return void
	 */
	public function deAuthenticateUser($logout_loc = 'logout.php')
	{

		$soft=false;
		$logout_url="";

	    if(isset($this->session)) {
		    /* adapt to HAKA */
		    $attribs = $this->session->getAttributes();
		    if (isset($attribs['urn:mace:funet.fi:haka:logout-url'])) {
				$soft=true;
			    $logout_url = $attribs['urn:mace:funet.fi:haka:logout-url'];
			    $safecounter = 10;
			    while ($safecounter > 0 && is_array($logout_url)) {
				    $safecounter -= 1;
				    $logout_url = $logout_url[0];
			    }
		    }
		    /* Find the redirect-link for surfnet/EduGAIN users */
	    }

		$this->person->isAuth(false);
		$base_url = $this->person->getSAMLConfiguration()->getBaseURL();
		$this->person->clearAttributes();

		/*
		 * If we are to perform soft logout, a logout_url must be set
		 */
		if (!$soft || !isempty($logout_url)) {
			$relay = Config::get_config('server_url') . $logout_loc;
			SimpleSAML_Utilities::redirect('/' . $base_url . 'saml2/sp/initSLO.php',
											array('RelayState' => $relay));
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
		$session = SimpleSAML_Session::getInstance();
		$this->person->setSession($session);
		$authority = $session->getAuthority();

		if (empty($authority)) {
			$authority = ConfusaConstants::$DEFAULT_SESSION_AUTHORITY;
		}

		$this->person->setAuth($session->isValid($authority));
		if ($this->person->isAuth()) {
			/* Do not add try-catch here as framework will trigger
			 * on that and adapt. */
			$this->decoratePerson($session->getAttributes());
		}
		return $this->person->isAuth();
	}
} /* end class IdP */
?>
