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
		if (!isset($attributes)) {
			throw new CrititicalAttributeException("Cannot find <b>any</b> attributes!");
		}

		/* Get the map
		 * Warning: this may throw the MapNotFoundException if the nren
		 * is new.
		 */
		$nren = $attributes['nren'][0];
		$subscr = $attributes['subscriber'][0];
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

			/* slow down and parse the name properly */
			$parsed = $attributes[$map['epodn']][0];
			$orgname= split(',', $parsed);
			if (isset($orgname)) {
				$parsed = "";
				foreach ($orgname as $key => $value) {
					$tmp = split('=', $value);
					if (isset($tmp[1])) {
						$parsed .= strtolower(str_replace(' ', '', $tmp[1])) . ".";
					} else {
						$parsed .= strtolower(str_replace(' ', '', $tmp[0])) . ".";
					}
				}
				if ($parsed[strlen($parsed)-1] == ".") {
					$parsed = substr($parsed, 0, strlen($parsed)-1);
				}
			}
			$this->person->setSubscriberOrgName($parsed);

			$this->person->setName($attributes[$map['cn']][0]);
			$this->person->setEmail($attributes[$map['mail']][0]);


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
				$msg  = "No map for your NREN (".$attributes['nren'][0].") is set <br />\n";
				$msg .= "You need to do this <b>now</b> so the normal users can utilize Confusa's functionality.<br />\n";
				$msg .= "<br /><center>Go <a href=\"stylist.php?mode=admin&show=map\">here</a> to update the map.</center><br />\n";
				if (Config::get_config('debug')) {
					$msg .= "Raw-dump of supplied attrributes:<br />\n";
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
		/* in the attributes, but not exported by the nrens (we
		 * deduce this in the NREN/Country map */
		$this->person->setCountry($attributes['country'][0]);
		$this->person->setNREN($attributes['nren'][0]);
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
	 * getAttributes() - return the attribute-keys found in attributes
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

		if (isset($subscriber) && $subscriber != "") {
			$map = AuthHandler::getSubscriberMap($nren, $subscriber);
			if (isset($map)) {
				return $map;
			}
		}
		return AuthHandler::getNrenMap($nren);
	} /* end getMap() */

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

	static function getNRENMap($nren)
	{
			$query  = "SELECT a.eppn, a.epodn, a.cn, a.mail, a.entitlement";
			$query .= " FROM attribute_mapping a, nrens n WHERE n.nren_id=a.nren_id AND n.name=? AND subscriber_id IS NULL";
			try {
				$map = MDB2Wrapper::execute($query,
							    array('text'),
							    array($nren));
			} catch (Exception $e) {
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
