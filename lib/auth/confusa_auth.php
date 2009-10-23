<?php

require_once 'person.php';
require_once 'confusa_config.php';
require_once 'config.php';

if (!Config::get_config('auth_bypass')) {
	$sspdir = Config::get_config('simplesaml_path');
	require_once $sspdir . '/lib/_autoload.php';
	SimpleSAML_Configuration::setConfigDir($sspdir . '/config');
}

require_once 'MapNotFoundException.php';

/**
 * Confusa_Auth - base class for all authentication managers
 *
 * Classes providing authN are supposed to implement:
 * 		- authenticateUser()
 *
 * 		- checkAuthentication()
 *
 *		- getAttributeKeys()
 *
 * 		- deAuthenticateUser()
 *
 *		- softLogout()
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
		if (Config::get_config('capi_test')) {
			$cnPrefix = ConfusaConstants::$CAPI_TEST_CN_PREFIX;
			$oPrefix = ConfusaConstants::$CAPI_TEST_O_PREFIX;
		} else {
			$cnPrefix = "";
			$oPrefix = "";
		}

		if (!isset($attributes)) {
			throw new CrititicalAttributeException("Cannot find <b>any</b> attributes!");
		}

		/* first deduce non-exported attributes from the NREN/Country map */
		$nren = $attributes['nren'][0];
		$country = $attributes['country'][0];

		if (is_null($nren)) {
			$msg = "Could not map from the identity provider to the NREN. ";
			$msg .= "Probably the NRENMap is not configured. ";
			$msg .= "Please tell an administrator about that problem!";
			throw new CriticalAttributeException($msg);
		}

		$this->person->setNREN($nren);

		if (is_null($country)) {
			$msg = "Could not map from the identity provider to the country. ";
			$msg .= "Probably the CountryMap is not configured. ";
			$msg .= "Please tell an administrator about that problem!";
			throw new CriticalAttributeException($msg);
		}

		/* in the attributes, but not exported by the nrens (we
		 * deduce this in the NREN/Country map */
		$this->person->setCountry($country);
		/* Get the map
		 * Warning: this may throw the MapNotFoundException if the nren
		 * is new.
		 */
		$map = "";
		$subscr = "";
		if (isset($attributes) && isset($attributes['subscriber'])) {
			$subscr = $attributes['subscriber'][0];
		}

		try {
			$map = AuthHandler::getMap($nren, $subscr);
		} catch (DBStatementException $dbse) {
			$msg  = "Your confusa installation is not properly configured. <br />\n";
			$msg .= "The attribute_mapping table is either missing or malformed.<br />\n";
			$msg .= "You need to create all tables needed by in order to find the correct attribute-mapping.<br />\n";
			throw new CriticalAttributeException($msg);
		}

		/* Normal mapping, this is what we want. */
		if (isset($map) && is_array($map)) {
			$this->person->setEPPN($attributes[$map['eppn']][0]);
			$this->person->setEPPNKey($map['eppn']);

			$this->person->setSubscriberIdPName(trim(stripslashes($attributes[$map['epodn']][0])));
			try {
				$query  = "SELECT s.dn_name FROM subscribers s ";
				$query .= "LEFT JOIN nrens n ON n.nren_id = s.nren_id ";
				$query .= "WHERE n.name=? AND s.name = ?";
				$res = MDB2Wrapper::execute($query,
							    array('text', 'text'),
							    array($nren, $this->person->getSubscriberIdPName()));
				if (count($res) == 1) {
					$this->person->setSubscriberOrgName($oPrefix . $res[0]['dn_name']);
				} else {
					$msg  = "Cannot find subscriberOrgName in the database. Cannot continue.<br />";
					$msg .= "This normally indicates that your subscriber (raw_name: ";
					$msg .= $this->person->getSubscriberIdPName() . ") ";
					$msg .= "is not properly configured or does not participate in Confusa ";
					$msg .= "certificate issuing. Contact your NREN-administrator to resolve this.<br />\n";
					throw new CriticalAttributeException($msg);
				}
			} catch (DBStatementException $dbse) {
				throw new ConfusaGenException("Cannot connect properly to database, some internal error. Make sure the DB is configured correctly.");
			} catch (DBQueryException $dbqe) {
				throw new ConfusaGenException("Cannot connect properly to database, errors with supplied data.");
			}

			/* Decorate the person with the mapped subscriber and a possible test prefix */
			$this->person->setName($cnPrefix . $attributes[$map['cn']][0]);

			/* if mail is not set, we cannot send notifications etc
			 * to the user. This means that we cannot sign
			 * certificates (or revoke them) as the user *requires*
			 * a receipt. */
			if (!isset($attributes[$map['mail']][0]) || $attributes[$map['mail']][0] == "") {
				$msg  = "Troubles with attributes. No mail address available. ";
				$msg .=" You will not be able to sign new certificates until this attribute is available.<br />\n";
				Framework::error_output($msg);
			} else {
				$this->person->setEmail($attributes[$map['mail']][0]);
			}

			/* test namespace
			 *
			 * we are looking for (atm)
			 * urn:mace:feide.no:sigma.uninett.no:<attribute>
			 */
			$entitlements = $attributes[$map['entitlement']];
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
							Framework::error_output("Error with namespace, too many objects in namespace (" . count($val) . ")");
							continue;
						}
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
			if (isset($attributes['eduPersonPrincipalName'][0])) {
				$this->person->setEPPN($attributes['eduPersonPrincipalName'][0]);
				$this->person->setEPPNKey('eduPersonPrincipalName');
			} else if (isset($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0])) {
				/* EduGAIN, Surfnet */
				$this->person->setEPPN($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0]);
				$this->person->setEPPNKey('urn:mace:dir:attribute-def:eduPersonPrincipalName');
			} else if (isset($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'][0])) {
				/* HAKA */
				$this->person->setEPPN($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'][0]);
				$this->person->setEPPNKey('urn:oid:1.3.6.1.4.1.5923.1.1.1.6');
			}
			/* is ePPN registred as NREN admin (from bootstrap) */
			if ($this->person->isNRENAdmin()) {
				$msg  = "No map for your NREN (".$nren.") is set <br />\n";
				$msg .= "You need to do this <b>now</b> so the normal users can utilize Confusa's functionality.<br />\n";
				$msg .= "<br /><center>Go <a href=\"stylist.php?mode=admin&show=map\">here</a> to update the map.</center><br />\n";
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
		$eppn = $this->person->getEPPN();
		if (!isset($eppn) || $eppn == "") {
			/* couldn't decorate person */
			$msg  = "Could not retrieve the config for you subscriber.<br />";
			$msg .= "Please contact your local IT department and forward the request to the NREN administrators.<br /><br />";
			$msg .= "Configure NREN Attribute Map for Your NREN.<br /><br />";
			throw new MapNotFoundException($msg);
		}
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
				require_once 'idp.php';
				AuthHandler::$auth = new Confusa_Auth_IdP($person);
			}
		}
		return AuthHandler::$auth;
	}

	/**
	 * getMap() Return the map used for this nren/subscriber
	 *
	 * @param String nren the name identifying the NREN
	 * @param String subscriber|null the name identifying the subscriber (dn_name)
	 *
	 * @retun Array|null the map for the given nren/subscriber
	 */
	static function getMap($nren, $subscriber = null)
	{
		if (!isset($nren) || $nren == "")
			throw new MapNotFoundException("No NREN supplied to AuthHandler::getMap(). This is a required value.");

		if (isset($subscriber) && $subscriber != "") {
			$map = AuthHandler::getSubscriberMap($nren, $subscriber);
			if (isset($map)) {
				return $map;
			}
		}
		return AuthHandler::getNrenMap($nren);
	} /* end getMap() */


	/**
	 * getSubscriberMap() Get the map for the subscriber so we can retrieve
	 * the correct attributes.
	 *
	 * @param String nren		The name identifying the NREN
	 * @param String subscriber	The name used to identify the
	 *				subscriber. This is the db_name, and is
	 *				the raw-string exported by the IdP's to
	 *				identify a given subscriber.
	 *
	 * @return Array|null		The resulting map for the subscriber.
	 */
	static function getSubscriberMap($nren, $subscriber)
	{
		$query  = "SELECT a.eppn, a.epodn, a.cn, a.mail, a.entitlement";
		$query .= " FROM subscribers s, nrens n, attribute_mapping a";
		$query .= " WHERE s.nren_id=n.nren_id AND s.subscriber_id=a.subscriber_id";
		$query .= " AND n.name=? AND s.name=? ";
		$values = array('text', 'text');
		$data	= array($nren, $subscriber);
		try {
			$map	= MDB2Wrapper::execute($query, $values, $data);
		} catch (Exception $e) {
			echo $query . "<br />\n";
			print_r($values);
			print_r($data);
			return null;
		}
		if (count($map) > 0) {
			if (count($map) == 1) {
				$map['type'] = 'subscriber';
				return $map[0];
			}
			throw new ConfusaGenException("Got " . count($map) . " hits when looking for the subscriber-map ($subscriber for nren $nren)");
		}
		return null;
	}

	/**
	 * getNRENMap()	Get the NREN-map (the most general map available).
	 *
	 * @param String		The name of the NREN as it is stored in
	 *				the database.
	 *
	 * @return Array|null		The map for the NREN's attributes.
	 */
	static function getNRENMap($nren)
	{
		if (!isset($nren) || $nren == "") {
			throw new ConfusaGenException("Cannot find the nren-map when the NREN is not set.");
		}
		$query  = "SELECT a.eppn, a.epodn, a.cn, a.mail, a.entitlement ";
		$query .= "FROM attribute_mapping a, nrens n ";
		$query .= "WHERE n.nren_id=a.nren_id AND n.name=? AND subscriber_id IS NULL";
		try {
			$map = MDB2Wrapper::execute($query,
						    array('text'),
						    array($nren));
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_INFO, __FILE__ . ":" . __LINE__ .
					  " check the table 'attribute_mapping' and make sure all columns are present.");
			return null;
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_INFO,  __FILE__ . ":" . __LINE__ .
					  "cannot find nren, something wrong with supplied nren-data ($nren)");
			return null;
		}
		if (count($map) > 0) {
			if (count($map) == 1) {
				$map['type'] = 'nren';
				return $map[0];
			}
			throw new ConfusaGenException("Got " . count($map) . " hits when looking for the map for NREN $nren");
		}
		return null;
	}
}
?>
