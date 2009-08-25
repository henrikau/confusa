<?php

require_once 'person.php';
require_once 'confusa_config.php';
require_once 'config.php';

$sspdir = Config::get_config('simplesaml_path');
require_once $sspdir . '/lib/_autoload.php';
SimpleSAML_Configuration::setConfigDir($sspdir . '/config');

require_once 'MapNotFoundException.php';

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

	public function decoratePerson($attributes)
	{
		if (!isset($attributes)) {
			throw new CrititicalAttributeException("Cannot find <b>any</b> attributes!");
		}

		/* Get the map
		 * Warning: this may throw the MapNotFoundException if the nren
		 * is new.
		 */
		$nren = $attributes['nren'][0];
		$subscr = $attributes['subscriber'][0];
		$map = AuthHandler::getMap($nren, $subscr);

		/* has the subscriber-name been updated, i.e. can we find a new map? */
		if ($subscr != $attributes[$map['epodn']][0]) {
			try {
				$map = AuthHandler::getMap($attributes['nren'][0], $attributes[$map['epodn']][0]);
			} catch (MapNotFoundException $mnfe) {
				; /* ignore, if the subscriber-map is
				   * not found, we do not care */
				;
			}
		}

		/* Normal mapping, this is what we want. */
		if (isset($map) && is_array($map)) {
			$this->person->setEPPN($attributes[$map['eppn']][0]);
			$this->person->setSubscriberOrgName($attributes[$map['epodn']][0]);
			$this->person->setName($attributes[$map['cn']][0]);
			$this->person->setEmail($attributes[$map['mail']][0]);
			$this->person->setEduPersonEntitlement($attributes[$map['entitlement']][0]);
		} else {
			/* At this point we're on shaky ground as we have to
			 * 'see if we can find anything'
			 * 
			 *		no map is set, can we find the ePPN in there?
			 */
			if (isset($attributes['eduPersonPrincipalName'][0])) {
				$this->person->setEPPN($attributes['eduPersonPrincipalName'][0]);
			} else if (isset($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0])) {
				/* EduGAIN, Surfnet */
				$this->person->setEPPN($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0]);
			} else if (isset($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'][0])) {
				/* HAKA */
				$this->person->setEPPN($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'][0]);
			}
			/* is ePPN registred as NREN admin (from bootstrap) */
			if ($this->person->isNRENAdmin()) {
				$msg  = "No map for your NREN (".$attributes['nren'][0].") is set <br />\n";
				$msg .= "You need to do this <b>now</b> so the normal users can utilize Confusa's functionality.<br />\n";
				Framework::error_output($msg);
			}
		}
		$eppn = $this->person->getEPPN();
		if (!isset($eppn) || $eppn == "") {
			/* couldn't decorate person */
			$msg  = "Could not retrieve the config for you subscriber.<br />";
			$msg .= "Please contact your local IT department and forward the request to the NREN administrators.<br /><br />";
			$msg .= "Configure NREN Attribute Map for Your NREN.<br /><br />";
			throw new MapNotFoundException($msg);
		}
		/* in the attributes, but not exported by the nrens (we
		 * deduce this in the NREN/Country map */
		$this->person->setCountry($attributes['country'][0]);
		$this->person->setNREN($attributes['nren'][0]);
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

	/**
	 * softLogout() - try to bump the authenticated session to force re-authN.
	 */
	public abstract function softLogout();
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

	static function getMap($nren, $subscriber = null)
	{
		if (!isset($nren) || $nren == "")
			throw new MapNotFoundException("No NREN supplied to AuthHandler::getMap(). This is a required value.");

		if (!isset($subscriber) || $subscriber == "") {
			$fullID = MDB2Wrapper::execute("SELECT nren_id FROM nrens WHERE name=?",
						       array('text'),
						       array(strtolower($nren)));
		} else {
			$query  = "select subscribers.nren_id, subscribers.subscriber_id from subscribers left join nrens on ";
			$query .= "subscribers.nren_id = nrens.nren_id where subscribers.name=? and nrens.name=?";
			$fullID = MDB2Wrapper::execute($query,
						       array('text', 'text'),
						       array(strtolower($subscriber), strtolower($nren)));
		}

		if (count($fullID) != 1) {
			if (count($fullID) == 0) {
				$msg  = "Did not find subscriber/nren combination in the database! ";
				$msg .= "Are you sure the subscriber has been added?";
				throw new ConfusaGenException($msg);
			} else {
				throw new ConfusaGenException("Too many hits! (" . count($fullID) . ")");
			}
		}
		/* See if subscriber has set a dedicated map */
		$map = array();
		if (isset($fullID[0]['subscriber_id']) && $fullID[0]['subscriber_id'] != "")  {
			$query	= "SELECT eppn, epodn, cn, mail, entitlement ";
			$query .= "FROM attribute_mapping WHERE subscriber_id=? and nren_id=?";
			$map	= MDB2Wrapper::execute($query,
						       array('text', 'text'),
						       array($fullID[0]['subscriber_id'], $fullID[0]['nren_id']));
		}

		/* Did not find a map for the subscriber-id, or the
		 * subscriber-id was not set, so map is emtpy */
		if (count($map) != 1) {
			$query  = "SELECT eppn, epodn, cn, mail, entitlement ";
			$query .= "FROM attribute_mapping WHERE nren_id=? AND subscriber_id IS NULL";
			$map = MDB2Wrapper::execute($query,
						    array('text'),
						    array($fullID[0]['nren_id']));
		}
		if (count($map) > 0) {
			if (count($map) == 1) {
				return $map[0];
			}
			throw new ConfusaGenException("Too many maps found. Database inconsistency");
		}
		return null;
	} /* end getMap() */
}
?>
