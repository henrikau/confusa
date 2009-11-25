<?php
require_once 'confusa_include.php';
require_once 'person.php';
require_once 'config.php';

require_once 'MapNotFoundException.php';

/**
 * Confusa_Auth - base class for all authentication managers
 *
 * Classes providing authN are supposed to implement:
 *
 * 		- authenticateUser()
 * 		- checkAuthentication()
 *		- getAttributeKeys()
 * 		- deAuthenticateUser()
 *		- softLogout()
 *
 * Subclasses should also use decoratePerson() when a new user has been
 * Authenticated.
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
	 * decoratePerson - get the supplied attributes and add to the correct
	 * fields in person
	 *
	 * This function is a bit fragile. The reason for this, is that it needs
	 * to 'bootstrap' the map for person-identifier (eduPersonPrincipalName)
	 * through various encodings.
	 *
	 * One way would be to add a specific mapping for all known NRENs, but
	 * we'd rather add a generic approach and just try the known encodings
	 * and see if we find something there.
	 *
	 * If, for some reason, a new NREN/IdP fails to correctly decorate the
	 * person-object, the problem most likely starts here.
	 *
	 * @author Henrik Austad <henrik.austad@uninett.no>
	 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
	 *
	 * @param array $attributes
	 * @throws MapNotFoundException
	 */
	public function decoratePerson($attributes)
	{
		$cnPrefix = "";
		$oPrefix  = "";
		if (Config::get_config('capi_test')) {
			$cnPrefix = ConfusaConstants::$CAPI_TEST_CN_PREFIX;
			$oPrefix  = ConfusaConstants::$CAPI_TEST_O_PREFIX;
		}

		if (!isset($attributes)) {
			throw new CrititicalAttributeException("Cannot find <b>any</b> attributes!");
		}


		if (is_null($attributes['nren'][0])) {
			$msg  = "Could not map from the identity provider to the NREN. ";
			$msg .= "Probably the NRENMap is not configured. ";
			$msg .= "Please tell an administrator about that problem!";
			throw new CriticalAttributeException($msg);
		}
		$this->person->setNREN($attributes['nren'][0]);


		if (is_null($attributes['country'][0])) {
			$msg = "Could not map from the identity provider to the country. ";
			$msg .= "Probably the CountryMap is not configured. ";
			$msg .= "Please tell an administrator about that problem!";
			throw new CriticalAttributeException($msg);
		}
		$this->person->setCountry($attributes['country'][0]);

		$map = $this->person->getMap();
		/* Normal mapping, this is what we want. */
		if (isset($map) && is_array($map)) {

			/* Now that we have the NREN-map, reiterate getMap() in
			 * case we can find the subscriber-map. */
			$this->person->addSubscriber($attributes[$map['epodn']][0]);
			$map = $this->person->getMap();

			$this->person->setEPPN($attributes[$map['eppn']][0]);
			if (!is_null($map['eppn'])) {
				$this->person->setEPPNKey($map['eppn']);
			}
			if(!is_null($map['cn'])) {
				$this->person->setName($cnPrefix . $attributes[$map['cn']][0]);
			}
			if (!is_null($map['mail'])) {
				$this->person->setEmail($attributes[$map['mail']][0]);
			}

			/* go through and add the relevant entitlement-parts.
			 * TODO: cleanup this and move to person::setEntitlement()
			 */
			if (!is_null($map['entitlement'])) {
				$entitlements = $attributes[$map['entitlement']];
			}
			if (isset($entitlements)) {
				$namespace = Config::get_config('entitlement_namespace');
				foreach ($entitlements as $key => $entitlementValue) {
					$pos = strpos($entitlementValue, $namespace);
					/* Note: we *must* check for both false *and*
					 * type, as we want pos to be 0 */
					if ($pos === false || (int)$pos != 0) {
						continue;
					} else {
						$val = explode(":", $entitlementValue);
						if (count($val) !== (count(explode(":", $namespace))+1)) {
							Framework::error_output("Error with namespace, too many objects in namespace ("
										. count($val) . ")");
							continue;
						}
						/* only set the part *after*
						 * entitlement-namespace */
						$this->person->setEntitlement($val[count($val)-1]);
					}
				}
			}
		} else {
			/* At this point we're on shaky ground as we have to
			 * 'see if we can find anything'
			 * 
			 *		no map is set, can we find the ePPN in there?
			 */
			$eppnKey = $this->findEPPN($attributes);
			if (!is_null($eppnKey)) {
				$this->person->setEPPN($eppnKey['value']);
				$this->person->setEPPNKey($eppnKey['key']);
			}

			/* is ePPN registred as NREN admin (from bootstrap) */
			if ($this->person->isNRENAdmin()) {
				$msg  = "No map for your NREN (".$nren.") is set <br />\n";
				$msg .= "You need to do this <b>now</b> so the normal users can utilize Confusa's functionality.<br />\n";
				$msg .= "<br /><center>Go <a href=\"attributes.php?mode=admin\">here</a> to update the map.</center><br />\n";
				if (Config::get_config('debug')) {
					$msg .= "Raw-dump of supplied attributes:<br />\n";
					$msg .= "<br /><pre>\n";
					foreach ($attributes as $key => $val) {
						$tabs = "\t";
						if (strlen($key) < 8)
							$tabs .= "\t\t";
						else if (strlen($key) < 16)
							$tabs .= "\t";
						$msg .= "$key$tabs{$val[0]}\n";
					}
					$msg .= "</pre><br />\n";
				}
				Framework::error_output($msg);
			}
		}
	} /* end decoratePerson() */

	/**
	 * findEPPN() find the eppn-value in the attributes.
	 *
	 * This function will search through the attributes and try to figure
	 * out where the ePPN is stored.
	 *
	 * It takes the formatting of the known federations into consideration
	 * and returns an array with name of key and content.
	 *
	 * @param array $attributes
	 * @return array key and value of ePPN
	 * @access private
	 */
	private function findEPPN($attributes)
	{
		if (is_null($attributes))
			return null;
		$result = array();
		/* Feide */
		if (isset($attributes['eduPersonPrincipalName'][0])) {
			$result['key'] = 'eduPersonPrincipalName';
		} else if (isset($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0])) {
			/* EduGAIN, Surfnet */
			$result['key'] = 'urn:mace:dir:attribute-def:eduPersonPrincipalName';
		} else if (isset($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'][0])) {
			/* HAKA */
			$result['key'] = 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6';
		} else {
			/* nothing found */
			return null;
		}
		$result['value']	= $attributes[$result['key']][0];
		return $result;
	}
	/**
	 * Authenticate the idenitity of a user, using a free-of-choice method to be
	 * implemented by subclasses
	 *
	 * @return boolean $authN indicating if the user was successfully authenticated
	 */
	public abstract function authenticateUser();

	/**
	 * Check (possibly by polling a subsystem), if a user is still authN.
	 *
	 * @return boolean $authN describing whether user is authenticated or not.
	 */
	public abstract function checkAuthentication();

	/**
	 * getAttributeKeys() - return the attribute-keys found in attributes
	 *
	 * This function is created solely to help the
	 * attribute-mapping. Instead of exposing *all* attributes, we return
	 * the relevant keys found.
	 *
	 * The function shall perform rudimentary filtering, keys suchs as
	 * 'country' and 'nren' should not be exposed. Neither should any other
	 * Confusa-specific keys be exported.
	 *
	 * @return array of attribute-keys.
	 */
	public abstract function getAttributeKeys();

	/**
	 * Get the currently assigned attributes from the authentication class.
	 *
	 * This can be practical whenever a more raw form of the attributes is
	 * needed, for instance for verbose logging and debugging messages or for
	 * informational display.
	 *
	 * @return array Raw attributes
	 */
	public abstract function getAttributes();

	/**
	 * "Logout" the user, possibly using the subsystem. To be implemented by
	 * subclasses
	 *
	 * @return void
	 */
	public abstract function deAuthenticateUser($logout_loc='logout.php');
}

/**
 * AuthHandler - return the right authentication manager for the configuration
 *
 * The handler should abstract that decision away from the calling functions
 * and consult on its own on the configuration or environment
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
				require_once 'bypass.php';
				AuthHandler::$auth = new Confusa_Auth_Bypass($person);
			} else {
				/* Start the IdP and create the handler */
				require_once 'idp.php';
				AuthHandler::$auth = new Confusa_Auth_IdP($person);
			}
		}
		return AuthHandler::$auth;
	}
} /* end class AuthHandler */
?>
