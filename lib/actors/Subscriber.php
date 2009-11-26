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
	private $state;
	private $comment;
	private $preferredLanguage;

	private $pendingChanges = false;

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
	function __construct($idp_name, $nren, $dn_name=null, $org_state=null, $db_id=null)
	{
		if (is_null($nren)) {
			$errorCode = create_pw(8);
			$msg  = "[$errorCode] " . __FILE__. ":" . __LINE__;
			$msg .= "Subscriber must be given a reference to an NREN. Cannot continue.";
			Logger::log_event(LOG_NOTICE, $msg);
			throw new ConfusaGenException($msg);
		}

		/* ugly hack to circumvent the missing constructor overloading of PHP5 */
		if (isset($dn_name) && isset($org_state)) {
			$this->nren = $nren;
			$this->idp_name = $idp_name;
			$this->dn_name = $dn_name;
			$this->state = $org_state;
			$this->db_id = $db_id;
		} else {
			$this->nren	= $nren;
			$this->idp_name = trim(stripslashes($idp_name));
			$this->valid	= $this->updateFromDB();
			if ($this->valid) {
				$this->getMap();
			}
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

	/**
	 * getMap() return the Subscriber-map.
	 *
	 * @param	void
	 * @return	array $map
	 * @access	public
	 */
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
	 * @param	void
	 * @return	String The subscriber's name.
	 * @access	public
	 */
	public function getOrgName()
	{
		$dn_name = $this->dn_name;
		/**
		 * set the test prefix, if confusa is in 'capi_test' mode
		 */
		if (Config::get_config('capi_test') &&
			Config::get_config('ca_mode') === CA_COMODO) {
				$dn_name = ConfusaConstants::$CAPI_TEST_O_PREFIX .
				           $this->dn_name;
		}
		return $dn_name;
	}

	public function setOrgName($org_name)
	{
		if(!is_null($org_name)) {
			if ($org_name === $this->org_name) {
				return false;
			}
			$this->org_name = Input::sanitizeText($org_name);
			return true;
		}
		return false;
	} /* end setOrgName() */

	/**
	 * getIdPName() Return the name of the Subscriber given by the IdP
	 *
	 * @param	void
	 * @return	String $idp_name
	 * @access	public
	 */
	public function getIdPName()
	{
		return $this->idp_name;
	}

	/**
	 * updateFromDB() update the current subscriber-object with fresh data
	 * from the database.
	 *
	 * @param	void
	 * @return	Boolean true on sucess.
	 * @access	private
	 */
	private function updateFromDB()
	{
		if ($this->pendingChanges) {
			/* WARNING, we may get corrupted data
			 * Should never be here, but even so?
			 *
			 * FIXME: decide: error-handling, or ignore?
			 */
			if (Config::get_config('debug')) {
				echo __CLASS__ . "::" . __FUNCTION__ .
					" Warning! updating values from DB while ".
					"there are uncommited messages in Subscriber";
			}
		}
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
		$this->setEmail(	$res[0]['subscr_email'],	false);
		$this->setPhone(	$res[0]['subscr_phone'],	false);
		$this->setRespName(	$res[0]['subscr_resp_name'],	false);
		$this->setRespEmail(	$res[0]['subscr_resp_email'],	false);
		$this->setDNName(	$res[0]['dn_name']);
		$this->setState(	$res[0]['org_state'],		false);
		$this->setComment(	$res[0]['subscr_comment'],	false);
		$this->setLanguage(	$res[0]['lang'],			false);

		return true;
	} /* end updateFromDB() */

	/**
	 * hasDBID() Test to see if the subscriber has the provided db-id
	 *
	 * @param	Integer $id the guessed ID
	 * @return	Boolean correctness of guess.
	 */
	public function hasDBID($id)
	{
		if (is_null($id))
			return false;
		return $id === $this->db_id;
	}

	public function getDBID()
	{
		return $this->db_id;
	}

	private function setDBID($dbID)
	{
		if(!is_null($dbID)) {
			$this->db_id = Input::sanitizeText($dbID);
		}
	}

	public function setEmail($email, $external = true)
	{
		if(!is_null($email)) {
			if ($email === $this->email) {
				return false;
			}
			if ($external) {
				$this->pendingChanges = true;
			}
			$this->email = Input::sanitizeText($email);
			return true;
		}
	}

	public function getEmail()
	{
		if (!is_null($this->email)) {
			return $this->email;
		}
		return null;
	}

	public function setPhone($phone, $external = true)
	{
		if(!is_null($phone)) {
			if ($phone === $this->phone) {
				return false;
			}
			if ($external) {
				$this->pendingChanges = true;
			}
			$this->phone = Input::sanitizeText($phone);
			return true;
		}
	}

	public function getPhone()
	{
		if (!is_null($this->phone)) {
			return $this->phone;
		}
		return null;
	}

	public function setRespName($respName, $external = true)
	{
		if(!is_null($respName)) {
			if ($respName === $this->responsible_name) {
				return false;
			}
			if ($external) {
				$this->pendingChanges = true;
			}
			$this->responsible_name = Input::sanitizeText($respName);
			return true;
		}
	}

	public function getRespName()
	{
		if (!is_null($this->responsible_name)) {
			return $this->responsible_name;
		}
		return null;
	}


	public function setRespEmail($respEmail, $external = true)
	{
		if(!is_null($respEmail)) {
			if ($respEmail === $this->responsible_email) {
				return false;
			}
			if ($external) {
				$this->pendingChanges = true;
			}
			$this->responsible_email = Input::sanitizeText($respEmail);
			return true;
		}
	} /* end setRespEmail() */

	public function getRespEmail()
	{
		if (!is_null($this->responsible_email)) {
			return $this->responsible_email;
		}
		return null;
	}

	public function setComment($comment, $external = true)
	{
		if (!is_null($comment)) {
			$com = Input::sanitizeText($comment);
			if ($this->comment !== $com) {
				$this->comment = $com;
				if ($external) {
					$this->pendingChanges = true;
				}
				return true;
			}
		}
		return false;
	} /* end setComment() */

	/**
	 * getComment()
	 */
	public function getComment()
	{
		if (isset($this->comment)) {
			return $this->comment;
		}
		return null;
	}
	/**
	 * setState() Set new state for the subscriber
	 *
	 * @param String $s the new state
	 * @param boolean $external if it is an external update and not a call
	 * that just decorates the subscriber with values from the database.
	 * @access public
	 */
	public function setState($s, $external = true)
	{
		if (is_null($s)) {
			return false;
		}

		$state = Input::sanitizeText($s);
		if ($state === $this->state) {
			return false;
		}

		if ($external) {
			$this->pendingChanges = true;
		}
		$this->state = $state;
		return true;
	}

	public function getState()
	{
		if (is_null($this->state)) {
			return null;
		}
		return $this->state;
	}
	private function setDNName($DNName)
	{
		if(!is_null($DNName)) {
			$this->dn_name = Input::sanitizeText($DNName);
		}
	}
	public function setLanguage($lang)
	{
		$this->preferredLanguage = $lang;
	}
	public function getLanguage()
	{
		return $this->preferredLanguage;
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
				$this->hasMap	= false;
				return false;
			case 1:
				$this->hasMap	= true;
				$this->map	= $res[0];
				return true;
			default:
				$this->hasMap	= false;
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

	/**
	 * Synchronize the changes in the subscriber object to the database or
	 * freshly store the subscriber in the DB
	 *
	 * @param $forcedSynch boolean if true, UPDATE the db even if no the object
	 *                             is not explicitly marked as having changed
	 * @throws ConfusaGenException INSERT/UPDATE of the subscriber failed for
	 *                             some reason
	 */
	public function save($forcedSynch = false)
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->pendingChanges || $forcedSynch) {
			$query = "UPDATE subscribers SET ";
			if (!is_null($this->getEmail())) {
				$query .= " subscr_email=:subscr_email, ";
				$data['subscr_email'] = $this->getEmail();
			}
			if (!is_null($this->getPhone())) {
				$query .= " subscr_phone=:subscr_phone, ";
				$data['subscr_phone'] = $this->getPhone();
			}
			if (!is_null($this->getRespName())) {
				$query .= "subscr_resp_name=:subscr_resp_name, ";
				$data['subscr_resp_name'] = $this->getRespName();
			}

			if (!is_null($this->getRespEmail())) {
				$query .= "subscr_resp_email=:subscr_resp_email, ";
				$data['subscr_resp_email'] = $this->getRespEmail();
			}
			if (!is_null($this->getState())) {
				$query .= "org_state=:org_state, ";
				$data['org_state'] = $this->getState();

			}
			if (!is_null($this->getComment())) {
				$query .= "subscr_comment=:subscr_comment, ";
				$data['subscr_comment'] = $this->getComment();
			}
			if (!is_null($this->getLanguage())) {
				$query .= "lang=:lang, ";
				$data['lang'] = $this->getLanguage();
			}
			if (!is_null($this->getHelpURL())) {
				$query .= "subscr_help_url=:subscr_help_url, ";
				$data['subscr_help_url'] = $this->getHelpURL();
			}
			if (!is_null($this->getHelpEmail())) {
				$query .= "subscr_help_email=:subscr_help_email, ";
				$data['subscr_help_email'] = $this->getHelpEmail();
			}
			$query = substr($query, 0, -2);
			$query .= " WHERE subscriber_id=:subscriber_id";
			$data['subscriber_id'] = $this->getDBID();

			try {
				MDB2Wrapper::update($query, null, $data);
			} catch (DBStatementException $dbse) {
				/* FIXME, better error-msg */
				$msg  = "Cannot connect properly to database, some internal error. ";
				$msg .= "Make sure the DB is configured correctly." . $dbse->getMessage();
				throw new ConfusaGenException($msg);
			} catch (DBQueryException $dbqe) {
				/* FIXME, better error-msg */
				$msg  = "Cannot connect properly to database, ";
				$msg .= "errors with supplied data.";
				throw new ConfusaGenException($msg);
			}
			$this->pendingChanges = false;
			return true;
		}
		return false;
	} /* end save() */

	public function create()
	{
		if ($this->isValid()) {
			return false;
		}
		$query  = "INSERT INTO subscribers (name, dn_name, nren_id, ";
		$query .= "org_state, subscr_email, subscr_phone, subscr_resp_email, ";
		$query .= "subscr_resp_name, lang, subscr_comment, subscr_help_url, subscr_help_email) ";
		$query .= "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params = array('text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text');

		$data = array($this->getIdPName(),
			      $this->org_name,
			      $this->nren->getID(),
			      $this->getState(),
			      $this->getEmail(),
			      $this->getPhone(),
			      $this->getRespEmail(),
			      $this->getRespName(),
			      $this->getlanguage(),
			      $this->getComment(),
			      $this->getHelpURL(),
			      $this->getHelpEmail());
		try {
			MDB2Wrapper::update($query, $params, $data);
			return true;
		} catch (DBStatementException $dbse) {
			$msg  = __CLASS__ . "::" . __FUNCTION__ . "(" . __LINE__ . ") ";
			$msg .= "Cannot connect properly to database, some internal error. ";
			$msg .= "Make sure the DB is configured correctly." . $dbse->getMessage();
			throw new ConfusaGenException($msg);
		} catch (DBQueryException $dbqe) {
			$msg  = __CLASS__ . "::" . __FUNCTION__ . "(" . __LINE__ . ") ";
			$msg .= "Cannot connect properly to database, ";
			$msg .= "errors with supplied data.";
			throw new ConfusaGenException($msg);
		}
		return false;
	}

	static function getSubscriberByID($id, $nren)
	{
		if (is_null($nren)) {
			return null;
		}
		if (is_null($id)) {
			return null;
		}

		try {
			$res = MDB2Wrapper::execute("SELECT name FROM subscribers WHERE subscriber_id=?",
						    array('text'),
						    array(Input::sanitizeText($id)));
		} catch (ConfusaGenException $cge) {
			echo $cge->getMessage();
			return null;
		}
		if (count($res) != 1) {
			echo "wrong count";
			return null;
		}
		return new Subscriber($res[0]['name'], $nren);
	}
} /* end class Subscriber */
?>
