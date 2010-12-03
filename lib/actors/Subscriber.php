<?php
require_once 'MDB2Wrapper.php';
/**
 * Subscriber
 *
 * Stateful class for a subscriber.
 *
 * @author	Henrik Austad <henrik.austad@uninett.no>
 * @license	http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @since	File available since Confusa v0.4-rc0
 * @package	resources
 */
class Subscriber
{
	private $org_name;
	private $idp_name;
	private $db_id;
	private $email;
	private $phone;
	private $responsible_name;
	private $responsible_email;
	private $state;
	private $comment;
	private $preferredLanguage;
	private $help_url;
	private $help_email;

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
			$errorCode = PW::create(8);
			$msg  = "[$errorCode] " . __FILE__. ":" . __LINE__;
			$msg .= "Subscriber must be given a reference to an NREN. Cannot continue.";
			Logger::log_event(LOG_NOTICE, $msg);
			throw new ConfusaGenException($msg);
		}

		/* ugly hack to circumvent the missing constructor overloading of PHP5 */
		if (isset($dn_name) && isset($org_state)) {
			$this->nren = $nren;
			$this->idp_name = $idp_name;
			$this->org_name = $dn_name;
			$this->state = $org_state;
			$this->db_id = $db_id;
		} else {
			$this->nren	= $nren;
			$this->idp_name = trim($idp_name);
			$this->valid	= $this->updateFromDB();
			if ($this->valid) {
				$this->retrieveMap();
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
			$res .= "org_name: "	. $this->org_name	. "<br />\n";
			$res .= "email: "	. $this->email		. "<br />\n";
			$res .= "phone: "	. $this->phone		. "<br />\n";
			$res .= "responsible_email: "	. $this->responsible_email	. "<br />\n";
			$res .= "responsible_name: "	. $this->responsible_name	. "<br />\n";
			$res .= "subscriber_help_url: "	. $this->help_url	. "<br />\n";
			$res .= "subscriber_help_email: "	. $this->help_email	. "<br />\n";
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
		$res['org_name'] = $this->getOrgName();
		$res['subscr_email'] = $this->email;
		$res['subscr_phone'] = $this->phone;
		$res['subscr_resp_email'] = $this->responsible_email;
		$res['subscr_resp_name'] = $this->responsible_name;
		$res['subscr_help_url'] = $this->help_url;
		$res['subscr_help_email'] = $this->help_email;
		$res['subscr_comment'] = $this->getComment();
		$res['lang'] = $this->preferredLanguage;
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
		$res = stripslashes($this->org_name);
		/**
		 * set the test prefix, if confusa is in 'capi_test' mode
		 */
		if (Config::get_config('capi_test') &&
		    Config::get_config('ca_mode') === CA_COMODO) {
			$res = ConfusaConstants::$CAPI_TEST_O_PREFIX . $res;
		}
		return $res;
	}

	public function setOrgName($org_name)
	{
		if(!is_null($org_name)) {
			if ($org_name === $this->org_name) {
				return false;
			}
			$this->org_name = mysql_real_escape_string($org_name);
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
		$query = "SELECT * FROM subscribers WHERE name=:subscriber_name AND nren_id=:nren_id";
		$data = array();
		$data['subscriber_name'] = $this->idp_name;
		$data['nren_id'] = $this->nren->getID();
		try {
			$res = MDB2Wrapper::execute($query, null, $data);
			if (count($res) != 1) {
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
		$this->setOrgName(	$res[0]['dn_name']);
		$this->setState(	$res[0]['org_state'],		false);
		$this->setComment(	$res[0]['subscr_comment'],	false);
		$this->setLanguage(	$res[0]['lang'],		false);
		$this->setHelpURL(	$res[0]['subscr_help_url'],	false);
		$this->setHelpEmail(	$res[0]['subscr_help_email'],	false);
		return true;
	} /* end updateFromDB() */

	/**
	 * hasDBID() Test to see if the subscriber has the provided db-id
	 *
	 * @param	Integer $id the guessed ID
	 * @return	Boolean correctness of guess.
	 * @access	public
	 */
	public function hasDBID($id)
	{
		if (is_null($id))
			return false;
		return $id === $this->db_id;
	}
	/**
	 * getDBID() return the database-id
	 *
	 * @param	void
	 * @return	Int $db_id the database-id
	 * @access	public
	 */
	public function getDBID()
	{
		return $this->db_id;
	}

	/**
	 * setDBID() Set the database-id to use for the subscriber.
	 *
	 * @param	Int $dbID the ID to use
	 * @return	boolean flag indicating if the operation was successful
	 * @access	private
	 */
	private function setDBID($dbID)
	{
		if(!is_null($dbID)) {
			$this->db_id = Input::sanitizeText($dbID);
		}
	}

	/**
	 * setEMail() update the subscriber-email address
	 *
	 * @param	String $email new subscriber-email address
	 * @param	Boolean $external if set to false, the change will not
	 *		be written to the database (unless other changes  made
	 *		to the Subscriber requires a database-update).
	 * @return	Boolean flag indicating if the email was successfully updated.
	 * @access	public
	 */
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

	/**
	 * getEmail() return the subscriber-email address
	 *
	 * @param	void
	 * @return	String $email the subscriber contact email
	 * @access	public
	 */
	public function getEmail()
	{
		if (!is_null($this->email)) {
			return $this->email;
		}
		return null;
	}

	/**
	 * setPhone() Set the subscriber contact phone
	 *
	 * @param	String $phone contact-phone for the subscriber
	 * @param	Boolean $external flag to avoid immediate database-update
	 * @return	Boolean flag indicating if the number was updated
	 * @access	public
	 */
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

	/**
	 * getPhone() return the subscriber (contact) phone
	 *
	 * @param	void
	 * @return	String the phonenumber
	 * @access	public
	 */
	public function getPhone()
	{
		if (!is_null($this->phone)) {
			return $this->phone;
		}
		return null;
	}

	/**
	 * seRespName() Set the name of the responsible person.
	 *
	 * The responsible person is typically an administrative person
	 * responsible for he service agreement.
	 *
	 * @param	String $resPname the name of the person
	 * @param	Boolean $external flag to indicate if the change should
	 *		trigger a database-update upon next save()
	 * @return	Boolean flag indicating if the name was updated
	 * @access	public
	 */
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

	/**
	 * getRespName() return the name of the responsible person
	 *
	 * @param	void
	 * @return	String name of the responsible person
	 * @access	public
	 */
	public function getRespName()
	{
		if (!is_null($this->responsible_name)) {
			return $this->responsible_name;
		}
		return null;
	}

	/**
	 * setRespEmail() Set the email-address to the responsible person
	 *
	 * @param	String $resEmail
	 * @param	Boolean $external wheter or not to let save() trigger
	 * @return	Boolean
	 * @access	public
	 */
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

	/**
	 * getRespEmail() Return the responsible person's email
	 *
	 * @param	void
	 * @return	Sring the email-address
	 * @access	public
	 */
	public function getRespEmail()
	{
		if (!is_null($this->responsible_email)) {
			return $this->responsible_email;
		}
		return null;
	}

	/**
	 * setComment() Set the comment for the subscriber.
	 *
	 * This will not append the comment to any exising comment. If you want
	 * to append, this must be done prior to calling this function.
	 *
	 * @param	String $comment
	 * @param	Boolean external
	 * @return	Boolean flag indicating if the operation succeeded.
	 */
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
	 * getComment() return the Subscriber's comment
	 *
	 * @param	void
	 * @return	String|null
	 * @access	public
	 */
	public function getComment()
	{
		if (isset($this->comment)) {
			return $this->comment;
		}
		return null;
	}

	/**
	 * setHelpURL() Set a new help-url for the subscriber.
	 *
	 * The help-URL is meant to be given to the users when they need
	 * help. In most cases, the portal will not run locally at each
	 * subscriber's sites.
	 *
	 * @param String $url the URL to the helpdesk
	 * @param boolean $external external update (trigger pendingChanges)
	 * @return boolean true if update was successful (and requires save())
	 * @access public
	 */
	public function setHelpURL($url, $external=true)
	{
		if (is_null($url)) {
			return false;
		}
		$url = Input::sanitizeText($url);
		if ($this->help_url === $url) {
			return false;
		}
		$this->help_url = $url;
		if ($external) {
			$this->pendingChanges = true;
		}
		return true;
	}

	/**
	 * getHelpURL() Return the help-url for the subscriber
	 *
	 * @param	void
	 * @return	String|null
	 * @access	public
	 */
	public function getHelpURL()
	{
		if (!is_null($this->help_url)) {
			return $this->help_url;
		}
		return null;
	}

	/**
	 * setHelpEmail() set the email for the Subscriber's  HelpDesk
	 *
	 * @param	String $email the helpdesk URL
	 * @param	Boolean $external
	 * @return	Boolean
	 * @access	public
	 */
	public function setHelpEmail($email, $external=true)
	{
		if (is_null($email)) {
			return false;
		}
		$email = Input::sanitizeText($email);
		if ($this->help_email === $email) {
			return false;
		}
		$this->help_email = $email;
		if ($external) {
			$this->pendingChanges = true;
		}
		return true;
	}

	/**
	 * getHelpEmail() return the helpdesk's email-address
	 *
	 * @param	void
	 * @return	String the address
	 * @access	void
	 */
	public function getHelpEmail()
	{
		if (!is_null($this->help_email)) {
			return $this->help_email;
		}
		return null;
	}
	/**
	 * setState() Set new state for the subscriber
	 *
	 * @param	String $s the new state
	 * @param	Boolean $external if it is an external update and not a
	 *		call that just decorates the subscriber with values from
	 *		the database.
	 * @return	Boolean
	 * @access	public
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

	/**
	 * getState() return the current state for the Subscriber
	 *
	 * @param	void
	 * @return	String|null the Subscriber's state
	 * @access	public
	 */
	public function getState()
	{
		if (is_null($this->state)) {
			return null;
		}
		return $this->state;
	}

	/**
	 * isSubscribed() test if the subscriber has state 'subscribed'
	 *
	 * @param	void
	 * @return	Boolean
	 * @access	public
	 */
	public function isSubscribed()
	{
		return $this->getState() == "subscribed";
	}

	/**
	 * setLanguage() set the default language to use for the subscriber's users
	 *
	 * @param	String $lang the language
	 * @param	Boolean $external
	 * @return	void
	 * @access	public
	 */
	public function setLanguage($lang, $external=true)
	{
		$this->preferredLanguage = $lang;
		$this->pendingChanges = $external;
	}

	/**
	 * getLanguage() return the registred language for the Subscriber
	 *
	 * @param	void
	 * @return	String the preffered language
	 * @access	public
	 */
	public function getLanguage()
	{
		return $this->preferredLanguage;
	}

	/**
	 * retrieveMap() return the map for the subscriber
	 *
	 * @param	void
	 * @return	Array|null the array for the subscriber or null if not set
	 * @access	private
	 */
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
	 * @access	public
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
			$query = substr($query, 0, -2) . " WHERE subscriber_id=:subscriber_id";
			$data['subscriber_id'] = $this->getDBID();

			try {
				MDB2Wrapper::update($query, null, $data);
				Logger::log_event(LOG_NOTICE,
						  "Updated data for subscriber (".$this->getDBID().") ".
						  $this->getOrgName());
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
			$this->pendingChanges = false;
			return true;
		}
		return false;
	} /* end save() */

	/**
	 * Save a subscriber attribute-map which overrides the map of the respective
	 * NREN. Take all map keys which can't be overriden on subscriber-side from
	 * the referenced NREN object (epodn, eppn, etc.). Update the map if it
	 * exists and insert it newly otherwise.
	 *
	 * DBQueryException and DBStatementException are bubbled up (library classes
	 * should remain unaware of Framework and thus Framework::error_output and
	 * just rethrowing a new exception does not make sense, does it).
	 *
	 * @param string $eppn The map-key for the unique identifiers of persons
	 *                     that log on
	 * @param string $cn The map-key for the common-name of persons that log on
	 * @param string $mail The map-key for the mail address of persons that log
	 *                     on
	 * @param unknown_type $entitlement The map key for the person's entitlement
	 * @return true upon success, nothing otherwise
	 *
	 * @throws DBQueryException If something goes wrong updating the DB,
	 *                          probably related to the data
	 * @throws DBStatementException If something goes wrong in contacting the DB,
	 *                              probably due to a configuration error
	 */
	public function saveMap($eppn, $cn, $mail)
	{
		$doUpdate = false;
		$nrenMap = $this->nren->getMap();
		$nrenID = $this->nren->getID();

		if ($this->hasMap) {
			if ( ($cn != $this->map['cn']) ||
			     ($mail != $this->map['mail'])) {
				$doUpdate = true;
				$statement = "UPDATE attribute_mapping " .
				          "SET cn = ?, mail = ? " .
				          "WHERE nren_id = ? AND subscriber_id = ?";
				$types = array('text', 'text', 'text', 'text', 'text', 'text');
				$data = array($cn, $mail, $nrenID, $this->db_id);
			}
		} else {
			$doUpdate = true;
			$statement = "INSERT INTO attribute_mapping";
			$statement .= "(nren_id, subscriber_id, eppn, epodn, cn, mail, entitlement) ";
			$statement .= "VALUES(?,?,?,?,?,?,?)";
			$types = array('text', 'text', 'text', 'text', 'text', 'text');
			$data = array($nrenID, $this->db_id, $eppn,
			              $nrenMap['epodn'], $cn, $mail, $nrenMap['entitlement']);
		}

		if ($doUpdate) {
			MDB2Wrapper::update($statement, $types, $data);
			$this->retrieveMap();
		}

		return true;
	}

	/**
	 * create() add a new subscriber to the database.
	 *
	 * This function will create a new entry in the subscribers-table and
	 * add the uploaded values to it.
	 *
	 * If the subscriber is valid, it means it has a db-entry, and thus we
	 * cannot create a new one.
	 *
	 * The function is a skeleton, it will create a skeleton subscriber and
	 * then call save() to decorate it. This is so we can handle arbitrary
	 * number of arguments. Requried attributes (such as idp_name) must be
	 * set as we sue this in order to create the entry.
	 *
	 * @param: void
	 * @return Boolean true|false indication success or failure.
	 */
	public function create()
	{
		if ($this->isValid()) {
			return false;
		}

		if (is_null($this->getIdPName()) ||$this->getIdPName() == "") {
			throw new ConfusaGenException("Cannot add subscriber without an IdP-name ".
						      "(Attribute Name). ".
						      "This is requried for all subscribers.");
		}
		if (is_null($this->org_name) || $this->org_name == "") {
			throw new ConfusaGenException("Cannot add subscriber without an org-name".
						      "(DN Organization Name) .".
						      "This value cannot be changed after the subscriber has been added ".
						      "and must be provided at creation.");

		}
		$query  = "INSERT INTO subscribers (name, dn_name, nren_id) VALUES(?, ?, ?)";
		$params = array('text', 'text', 'text');

		$data = array($this->getIdPName(),
			      $this->org_name,
			      $this->nren->getID());
		try {
			MDB2Wrapper::update($query, $params, $data);
			$id_res = MDB2Wrapper::execute("SELECT subscriber_id FROM subscribers where name=? AND nren_id=?",
						       array('text', 'text'),
						       array($this->getIdPName(), $this->nren->getID()));
			if (count($id_res) == 1) {
				$this->setDBID($id_res[0]['subscriber_id']);
				$this->valid = true;
				$this->save();
			} else {
				throw new ConfusaGenException("Could not add subscriber to database for unknown reason.");
			}
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

	/**
	 * getSubscriberByIO() find a subscriber in the database and decoraate a
	 *		Subscriber-object
	 *
	 * @param	int $id the db-id for the subscriber
	 * @param	NREN $nren
	 * @return	Subscriber|null
	 * @access	public
	 */
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
