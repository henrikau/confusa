<?php
require_once 'mdb2_wrapper.php';

class Subscriber
{
	private $dn_name;
	private $idp_name;
	private $db_id;
	private $email;
	private $phone;
	private $responsible_name;
	private $responsible_email;

	/* Subscriber-map */
	private $hasMap;
	private $map;

	/* is the subscriber valid (i.e. found in the database)? */
	private $valid;

	/* Reference to the NREN */
	private $nren;
	/**
	 * __construct() create the subscriber
	 *
	 * @param String $idp_name The name of the subscriber returned by the IdP.
	 * @return void
	 */
	function __construct($idp_name, $nren)
	{
		if (is_null($nren)) {
			$errorCode = create_pw(8);
			$msg  = "[$errorCode] " . __FILE__. ":" . __LINE__;
			$msg .= "Subscriber must be given a reference to an NREN. Cannot continue.";
			Logger::log_event(LOG_NOTICE, $msg);
			throw new ConfusaGenException($msg);
		}
		$this->nren	= $nren;
		$this->idp_name = trim(stripslashes($idp_name));
		$this->valid	= $this->updateFromDb();
		if ($this->valid) {
			$this->getMap();
		}
	}

	/**
	 * toString() Return a plain textlist (html-formatted) of a subscriber
	 * for quick and dirty dumping to stdout.
	 */
	function __toString()
	{
		$res = "";
		if (Config::get_config('debug')) {
			$res .= "db_id: "	. $this->db_id		. "<br />\n";
			$res .= "idp_name: "	. $this->idp_name	. "<br />\n";
			$res .= "dn_name: "	. $this->dn_name	. "<br />\n";
			$res .= "email: "	. $this->email		. "<br />\n";
			$res .= "phone: "	. $this->phone		. "<br />\n";
			$res .= "responsible_email: "	. $this->responsible_email	. "<br />\n";
			$res .= "responsible_name: "	. $this->responsible_name	. "<br />\n";
		} else {
			$res = $this->idp_name;
		}
		return $res;
	}

	/**
	 * isValid() Return status for the subscriber if it is valid
	 *
	 * If the subscriber is valid, i.e. if it is registred in the database
	 */
	public function isValid()
	{
		return $this->valid;
	}

	/**
	 * Get the contact information for a NREN
	 *
	 * @param void
	 * @return Array The contact-details for the NREN
	 */
	public function getInfo()
	{
		$res = array();
		$res['name'] = $this->idp_name;
		$res['dn_name'] = $this->dn_name;
		$res['subscr_email'] = $this->email;
		$res['subscr_phone'] = $this->phone;
		$res['subscr_resp_email'] = $this->responsible_email;
		$res['subscr_resp_name'] = $this->responsible_name;
		return $res;
	}
	public function getMap()
	{
		if ($this->hasMap)
			return $this->map;
		return null;
	}


	/**
	 * getOrgName() The name of the person's subscriber organization name.
	 *
	 * This is a name of the home-institution, e.g. 'ntnu',  'uio'.
	 *
	 * @return String The subscriber's name.
	 */
	public function getOrgName()
	{
		return $this->dn_name;
	}

	public function getIdPName()
	{
		return $this->idp_name;
	}

	/**
	 * updateFromDB() update the current subscriber-object with fresh data
	 * from the database.
	 *
	 * @param void
	 * @return Boolean true on sucess.
	 */
	private function updateFromDB()
	{
		$query = "SELECT * FROM subscribers WHERE name = ?";
		try {
			$res = MDB2Wrapper::execute($query,
						    array('text'),
						    array($this->idp_name));
			if (count($res) != 1) {
				/* Could not find the subscriber. Aborting. */
				return false;
			}
		} catch (DBStatementException $dbse) {
			$msg  = "Cannot connect properly to database, some internal error. ";
			$msg .= "Make sure the DB is configured correctly.";
			throw new ConfusaGenException($msg);
		} catch (DBQueryException $dbqe) {
			$msg  = "Cannot connect properly to database, ";
			$msg .= "errors with supplied data.";
			throw new ConfusaGenException($msg);
		}

		/* Update all subscriber-data */
		$this->setDBID(		$res[0]['subscriber_id']);
		$this->setEmail(	$res[0]['subscr_email']);
		$this->setPhone(	$res[0]['subscr_phone']);
		$this->setRespName(	$res[0]['subscr_resp_name']);
		$this->setRespEmail(	$res[0]['subscr_resp_email']);
		$this->setDNName(	$res[0]['dn_name']);

		/* Find the map from the database */
		return true;
	} /* end updateFromDB() */


	private function setDBID($dbID)
	{
		if(!is_null($dbID)) {
			$this->db_id = Input::sanitizeText($dbID);
		}
	}
	private function setEmail($email)
	{
		if(!is_null($email)) {
			$this->email = Input::sanitizeText($email);
		}
	}
	private function setPhone($phone)
	{
		if(!is_null($phone)) {
			$this->phone = Input::sanitizeText($phone);
		}
	}
	private function setRespName($respName)
	{
		if(!is_null($respName)) {
			$this->responsible_name = Input::sanitizeText($respName);
		}
	}
	private function setRespEmail($respEmail)
	{
		if(!is_null($respEmail)) {
			$this->responsible_email = Input::sanitizeText($respEmail);
		}
	}
	private function setDNName($DNName)
	{
		if(!is_null($DNName)) {
			$this->dn_name = Input::sanitizeText($DNName);

			/**
			 * set the test prefix, if confusa is in 'capi_test' mode
			 */
			if (Config::get_config('capi_test') &&
			    Config::get_config('ca_mode') === CA_ONLINE) {
					$this->dn_name = ConfusaConstants::$CAPI_TEST_O_PREFIX .
					                 $this->dn_name;
			}
		}
	}
	private function retrieveMap()
	{
		if (is_null($this->nren->getID())) {
			throw new ConfusaGenException("Cannot find map for subscriber when NREN-ID is not set!");
		}
		if (is_null($this->db_id)) {
			throw new ConfusaGenException("Cannot find map for subscriber when Subscriber-ID is not set!");
		}
		$this->hasMap	= false;
		$query		= "SELECT * FROM attribute_mapping WHERE subscriber_id=? AND nren_id=?";
		$params		= array('text', 'text');
		$data		= array($this->db_id, $this->nren->getID());

		try {
			$res = MDB2Wrapper::execute($query, $params, $data);
			switch(count($res)) {
			case 0:
				return false;
			case 1:
				$this->hasMap	= true;
				$this->map	= $map[0];
				break;
			default:
				$msg  = "Too many hits (" . count($res) . ") were found in the database. ";
				$msg .= __CLASS__ . __FUNCTION__ . " gets confused. Aborting.";
				Logger::log_event(LOG_NOTICE, $msg);
				Framework::error_output($msg);
				return false;
			}
		} catch (ConfusaGenException $e) {
			/* FIXME */
			Framework::error_output($e->getMessage());
			return false;
		}
	} /* end retrieveMap() */
}
?>
