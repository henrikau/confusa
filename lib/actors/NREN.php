<?php
require_once 'confusa_include.php';
require_once 'mdb2_wrapper.php';
require_once 'CriticalAttributeException.php';
require_once 'classTextile.php';

/**
 * Placeholder for an NREN
 *
 * This class contains information about, and operation on this information for
 * an NREN. The class gives the framework a nice, clean way of retrieving
 * per-NREN information¸ and also for storing this information.
 *
 * @author	Henrik Austad <henrik.austad@uninett.no>
 * @license	http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @since	File available since Confusa v0.4-rc0
 */
class NREN
{
	private $idp_name;
	private $map;
	private $hasMap;
	private $isValid;

	private $data;
	private $pendingChanges;

	function __construct($idp_name)
	{
		$this->data = array();

		if (isset($idp_name)) {
			$this->idp_name = Input::sanitizeText($idp_name);
			$this->pendingChanges = false;

			$this->isValid = $this->decorateNREN();
			if (!$this->isValid) {
				Logger::log_event(LOG_ALERT,
						  __FILE__ .":".__LINE__." could not decorate NREN (".
						  $this->idp_name . ")\n");
				return;
			}
			$this->retrieveMap();
		}
	}

	/**
	 * __toString() Return a string-representation (the name) of the NREN
	 *
	 * @param	void
	 * @return	String the name of the NREN
	 * @access	public
	 */
	public function __toString()
	{
		return $this->data['name'];
	}

	/**
	 * isValid() returns a flag indicating whether or not the current NREN
	 * is valid.
	 *
	 * The flag is based on how decorateNREN() fares, i.e. whether or not
	 * the NREN was properly decorated from the database.
	 *
	 * @param	void
	 * @return	Boolean flag indicating if the NREN is properly populated from the DB
	 * @access	public
	 */
	public function isValid()
	{
		return $this->isValid;
	}

	/**
	 * getName() Return the stored name for the NREN.
	 *
	 * @param	void
	 * @return	String Name of the NREN
	 * @throws	CriticialAttributeException When the name is not set, we
	 *		have stumbled accross internal inconsistency.
	 * @access	public
	 */
	public function getName()
	{
		return $this->data['name'];
	}

	/**
	 * getCountry() Return the nationality of the NREN
	 *
	 * The country is stored and returned in ISO-3166-1-A2 format
	 * (two-letter country code).
	 *
	 * @param	void
	 * @return	String ISO-3166 country code
	 * @access	public
	 */
	public function getCountry()
	{
		return $this->data['country'];
	}

	/**
	 * getID() return the database ID for the NREN
	 *
	 * If the NREN is stored in the database (which it pretty much must be),
	 * this function will return the Database-ID
	 *
	 * @param	void
	 * @return	Integer|null the ID of the NREN
	 * @access	public
	 */
	public function getID()
	{
		return $this->data['nren_id'];
	}

	/**
	 * getHelp() get the help-text from the database and return.
	 *
	 * The help-text is a large chunk of text, and we do not want to
	 * retrieve this every time we create an NREN-object. Only when we
	 * *need* it should we query the database.
	 *
	 * @param	void
	 * @return	String|null the help-text stored in the database for the NREN
	 * @access	public
	 */
	public function getHelp()
	{
		if (is_null($this->data['help'])) {
			$help = MDB2Wrapper::execute("SELECT help FROM nrens WHERE nren_id =?",
						     array('text'),
						     array($this->nren_id));
			if (count($help) == 1 && !is_null($help[0]['help'])) {
				$this->data['help'] = $help[0]['help'];
			}
		}
		return $this->data['help'];
	}

	/**
	 * getMap() Return the attribute-map associated with the NREN
	 *
	 * The map is used to find the relationship between the attributes and
	 * the content we need. The map is retrieved from the database via
	 * decorateNREN().
	 *
	 * @param	void
	 * @return	Array|null the map
	 * @access	public
	 */
	public function getMap()
	{
		if ($this->hasMap) {
			return $this->map;
		}
		return null;
	}

	/**
	 * getEnableEmail() number of emails to include in certs.
	 *
	 * The NREN can be configured to allow 0, 1, multiple (at least 1) and
	 * multiple (including none) emails in the certificate.
	 *
	 * @param	void
	 * @return	String|null 0,1 or multiple addresses to store in the certs.
	 * @access	public
	 */
	public function getEnableEmail()
	{
		if ($this->data && array_key_exists('enable_email', $this->data)) {
			return $this->data['enable_email'];
		}
		return null;
	}

	/**
	 * getCertValidity() stored validity period for certificates
	 *
	 * If Confusa operates in eScience mode, the value is always 395.
	 *
	 * If Confusa operates in personal certificates mode, the value is NREN-
	 * setting dependant and one of:
	 * - 365
	 * - 730
	 * - 1065.
	 *
	 * Note: if Confusa is placed in test-mode ('capi_test'), the returned
	 * value is ignored by the CA-manager and 14 is used instead.
	 *
	 * @param	void
	 * @return	String 365, 395, 730 or 1065
	 * @access	public
	 */
	public function getCertValidity()
	{
		if (Config::get_config('cert_product') == PRD_ESCIENCE) {
			return ConfusaConstants::$CAPI_VALID_ESCIENCE;
		} else {
			if ($this->data && isset($this->data['cert_validity'])) {
				return $this->data['cert_validity'];
			}

			return min(ConfusaConstants::$CAPI_VALID_PERSONAL);
		}
	}

	/**
	 * getShowPortalTitle() flag to show if the portal title be shown
	 *
	 * This returns a flag indicating whether or not the portal-title should
	 * be part of the NREN-branding. It might be desirable not to show the
	 * title if there are large logos in the header.
	 *
	 * @param	void
	 * @return	Boolean	Whether the portal title is to be shown or not
	 * @access	public
	 */
	public function getShowPortalTitle()
	{
		if (isset($this->data) && isset($this->data['show_portal_title'])) {
			return ($this->data['show_portal_title'] == 1);
		} else {
			return false;
		}
	}

	/**
	 * getCustomPortalTitle()
	 *
	 * @param	void
	 * @return	String  the title that is configured for the portal to
	 *			show for the given NREN
	 * @access	public
	 */
	public function getCustomPortalTitle()
	{
		if (isset($this->data) && isset($this->data['portal_title'])) {
			return $this->data['portal_title'];
		} else {
			return Config::get_config('system_name');
		}
	}

	/**
	 * saveMap()	save the current map to database
	 *
	 * When the map is updated, this will handle the interaction with the
	 * database.
	 *
	 * @param	String $eppnkey key to use for finding the ePPN
	 * @param	String $epodn	key to find eduPersonOrgDN
	 * @param	String $cn	Common Name (full name of user)
	 * @param	String $mail	E-mail
	 * @param	String $entitlement entitlement-key
	 * @return	Boolean	true if map was successfully saved to database.
	 * @access	public
	 */
	public function saveMap($eppnkey, $epodn, $cn, $mail, $entitlement)
	{
		$doUpdate = false;
		$eppnkey  = Input::sanitizeText($eppnkey);
		if ($this->hasMap) {
			/* compare value */
			if ($epodn	!= Input::sanitizeText($this->map['epodn']) ||
			    $cn		!= Input::sanitizeText($this->map['cn']) ||
			    $mail	!= Input::sanitizeText($this->map['mail']) ||
			    $entitlement!= Input::sanitizeText($this->map['entitlement'])) {
				$doUpdate = true;
				$update = "UPDATE attribute_mapping SET epodn=?, cn=?, ".
					" mail=?, entitlement=? WHERE nren_id=? ".
					" AND subscriber_id IS NULL";
				$params = array('text', 'text', 'text', 'text', 'text');
				$data = array(Input::sanitizeText($epodn),
					      Input::sanitizeText($cn),
					      Input::sanitizeText($mail),
					      Input::sanitizeText($entitlement),
					      $this->getID());
			}
		} else {
			$doUpdate = true;
			$update = "INSERT INTO attribute_mapping(nren_id, eppn, epodn, cn, mail, entitlement) VALUES(?, ?, ?, ?, ?, ?)";
			$params = array('text', 'text', 'text', 'text', 'text', 'text');
			$data = array($this->getID(), $eppnkey,
				      $epodn, $cn, $mail, $entitlement);
		}
		if ($doUpdate) {
			try {
				MDB2Wrapper::update($update, $params, $data);
				$this->retrieveMap();
			}catch (DBStatementException $dbse) {
				/* FIXME */
				Framework::error_output(__FILE__ . ":" . __LINE__ . " " . htmlentities($dbse->getMessage()));
				return false;
			} catch (DBQueryException $dbqe) {
				/* FIXME */
				Framework::error_output(__FILE__ . ":" . __LINE__ . " " . htmlentities($dbqe->getMessage()));
				return false;
			}
		}

		return true;
	}
	/**
	 * getNRENInfo() Get the contact information for a NREN
	 *
	 * This returns *all* information retrieved from the database, thus it
	 * should be used with a grain of caution.
	 *
	 * @param	void
	 * @return	Array The contact-details for the NREN
	 * @access	public
	 */
	public function getNRENInfo()
	{
		if ($this->data) {
			return $this->data;
		}
		return null;
	}

	/**
	 * getWAYFURL() get the URL to the NREN's WAYF
	 *
	 * @param	void
	 * @return	String the URL to the NREN's Where are you from service
	 * @access	public
	 */
	public function getWAYFURL()
	{
		if (isset($this->data) && array_key_exists('wayf_url', $this->data)) {
			return $this->data['wayf_url'];
		}
	}

	/**
	 * setLang() Set the preferred language for the NREN
	 *
	 * @param	String @lang the language to use for the NREN
	 * @return	void
	 * @access	public
	 */
	public function setLang($lang)
	{
		if (!is_null($lang)) {
			if ($this->data['lang'] != $lang) {
				$this->data['lang'] = Input::sanitizeText($lang);
				$this->pendingChanges = true;
			}
		}
	}

	/**
	 * setURL() set the NREN-portal URL
	 *
	 * The URL is used to "pre-brand" the portal. An NREN can define which
	 * URL the portal be hosted under, and Confusa will then look at the
	 * access-url of an un-AuthN user, and if a match is found in the DB,
	 * appropriate branding is applied.
	 *
	 * @param	String $url the URL to use
	 * @return	void
	 * @access	public
	 */
	public function setURL($url)
	{
		if (!is_null($url)) {
			if ($this->data['url'] != $url) {
				$this->data['url'] = Input::sanitizeText($url);
				$this->pendingChanges = true;
			}
		}
	}

	/**
	 * setContactEmail() set the address to NREN-contact
	 *
	 * This email is where administrative contact to the NREN can be
	 * placed. In some areas, users and subscriber-admins need to find this
	 * quickly, and the NREN may want to use a dedicated address for
	 * portal-inquiries.
	 *
	 * @param	String $contact_email
	 * @return	void
	 * @access	public
	 */
	public function setContactEmail($contact_email)
	{
		if (!is_null($contact_email)) {
			if ($this->data['contact_email'] != $contact_email) {
				$this->data['contact_email'] = Input::sanitizeText($contact_email);
				$this->pendingChanges = true;
			}
		}
	}

	public function set_cert_email($cert_email)
	{
		if (!is_null($cert_email)) {
			if ($this->data['cert_email'] != $cert_email) {
				$this->data['cert_email'] = Input::sanitizeText($cert_email);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_cert_phone($cert_phone)
	{
		if (!is_null($cert_phone)) {
			if ($this->data['cert_phone'] != $cert_phone) {
				$this->data['cert_phone'] = Input::sanitizeText($cert_phone);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_contact_phone($contact_phone)
	{
		if (!is_null($contact_phone)) {
			if ($this->data['contact_phone'] != $contact_phone) {
				$this->data['contact_phone'] = Input::sanitizeText($contact_phone);
				$this->pendingChanges = true;
			}
		}
	}

	public function setEnableEmail($enable_email)
	{
		if (!is_null($enable_email)) {
			if (!array_key_exists('enable_email', $this->data) ||
			    ($this->data['enable_email'] != $enable_email)) {
				$this->data['enable_email'] = $enable_email;
				$this->pendingChanges = true;
				return;
			}
		}
	}

	public function setCertValidity($validity)
	{
		if (isset($validity)) {
			if (!array_key_exists('cert_validity', $this->data) ||
			    ($this->data['cert_validity'] != $validity)) {

				$this->data['cert_validity'] = $validity;
				$this->pendingChanges = true;
				return;
			}
		}
	}

	public function setShowPortalTitle($showPortalTitle)
	{
		if (isset($showPortalTitle)) {
			if (!array_key_exists('show_portal_title', $this->data) ||
			   ($this->data['show_portal_title'] != $showPortalTitle)) {

				$this->data['show_portal_title'] = $showPortalTitle;
				$this->pendingChanges = true;
				return;
			}
		}
	}

	public function setCustomPortalTitle($portalTitle)
	{
		if (isset($portalTitle)) {
			if (!array_key_exists('portal_title', $this->data) ||
			($this->data['portal_title'] != $portalTitle)) {

				$this->data['portal_title'] = $portalTitle;
				$this->pendingChanges = true;
				return;
			}
		}
	}

	/**
	 * saveNREN() Save the current NREN to the database.
	 *
	 * This must be done after new values has been set.
	 *
	 * @param	String	$nren_name The name of the NREN
	 * @return	boolean false, if saving failed, true if saving suceeded
	 * @access	public
	 */
	public function saveNREN()
	{
		if ($this->pendingChanges) {
			$query  = "UPDATE nrens SET contact_email=?, contact_phone=?, ";
			$query .= " cert_phone=?, cert_email=?, url=?, lang=?, enable_email=?, cert_validity=?,";
			$query .= " show_portal_title=?, portal_title=? ";
			$query .= "WHERE nren_id=?";
			$params	= array('text','text', 'text', 'text', 'text', 'text', 'text',
			                'text', 'text', 'text', 'text');
			$data	= array($this->data['contact_email'],
					$this->data['contact_phone'],
					$this->data['cert_phone'],
					$this->data['cert_email'],
					$this->data['url'],
					$this->data['lang'],
					$this->data['enable_email'],
					$this->data['cert_validity'],
					$this->data['show_portal_title'],
					$this->data['portal_title'],
					$this->getID());
			try {
				MDB2Wrapper::update($query, $params, $data);
			} catch (DBQueryException $dqe) {
				Framework::error_output("Could not change the NREN contact! Maybe something is " .
							"wrong with the data that you supplied? Server said: " .
							htmlentities($dqe->getMessage()));
				Logger::log_event(LOG_INFO, "[nadm] Could not update " .
						  "contact of NREN " . $this->data['name'] . ": " .
						  $dqe->getMessage());
				return false;
			} catch (DBStatementException $dse) {
				Framework::error_output("Could not change the NREN contact! Confusa " .
							"seems to be misconfigured. Server said: " .
							htmlentities($dse->getMessage()));
				Logger::log_event(LOG_WARNING, "[nadm] Could not update " .
						  "contact of NREN " . $this->data['name'] . ": " .
						  $dse->getMessage());
				echo $query . "<br />\n";
				return false;
			}

			Logger::log_event(LOG_DEBUG, "[nadm] Updated contact for NREN " . $this->getName());
			$this->pendingChanges = false;
			return true;
		}
	} /* end saveNREN() */
	/**
	 * decorateNREN() Add information about the NREN to the object.
	 *
	 * This function will use the idp_name to find the NREN from the
	 * database.
	 *
	 * It will store all elements in the row in the object so it can be used
	 * at a later time.
	 *
	 * The database looks like the following:
	 *
	 * +---------------+-------------+------+-----+---------+----------------+
	 * | Field         | Type        | Null | Key | Default | Extra          |
	 * +---------------+-------------+------+-----+---------+----------------+
	 * | nren_id       | int(11)     | NO   | PRI | NULL    | auto_increment |
	 * | name          | varchar(30) | YES  |     | NULL    |                |
	 * | country       | char(2)     | NO   |     | NULL    |                |
	 * | login_account | int(11)     | YES  | MUL | NULL    |                |
	 * | about         | text        | YES  |     | NULL    |                |
	 * | help          | text        | YES  |     | NULL    |                |
	 * | lang          | varchar(5)  | NO   |     | NULL    |                |
	 * | contact_email | varchar(64) | NO   |     | NULL    |                |
	 * | contact_phone | varchar(24) | NO   |     | NULL    |                |
	 * | cert_email    | varchar(64) | YES  |     | NULL    |                |
	 * | cert_phone    | varchar(16) | YES  |     | NULL    |                |
	 * | enable_email  | enum('0','1','n') | YES  |     | NULL    |          |
	 * +---------------+-------------+------+-----+---------+----------------+
	 *
	 * We do not retrieve all fields, the large text-fields ('help' and
	 * 'about') are ignored, and will only be retrieved if specifically
	 * asked for.
	 *
	 * @param	void
	 * @return	void
	 * @access	private
	 */
	private function decorateNREN()
	{
		/* $query  = "SELECT nren_id, name, login_account, contact_email, ". */
		/* 	"contact_phone, cert_email, cert_phone, lang, url FROM nrens WHERE name = ?"; */
		$query  = "SELECT	n.nren_id,		n.name,		n.login_account, ";
		$query .= "		n.contact_email,	n.contact_phone,n.cert_email, ";
		$query .= "		n.cert_phone,		n.lang,		n.url, ";
		$query .= "		n.country,		idp.idp_url as idp_url, ";
		$query .= "		n.enable_email,	n.cert_validity, ";
		$query .= "		n.show_portal_title, n.portal_title, n.wayf_url ";
		$query .= "FROM idp_map idp LEFT JOIN ";
		$query .= "nrens n on idp.nren_id = n.nren_id WHERE idp.idp_url=?";
		try {
			$res = MDB2Wrapper::execute($query, array('text'), array($this->idp_name));
			switch (count($res)) {
			case 0:
				if (Config::get_config('debug')) {
					echo "no IdP with name (".$this->idp_name.") found in db!<br />\n";
				}
				return false;
			case 1:
				/* decorate NREN */
				foreach ($res[0] as $k => $value) {
					$key = strtolower(Input::sanitizeText($k));
					$this->data[$key] = Input::sanitizeText($value);
				}
				break;
			default:
				echo "too many nrens (" . count($res) . ") found in db. Aborting.<br />\n";
				return false;
			}
		} catch (ConfusaGenException $cge) {
			Framework::error_output("Cannot connect to DB. Server said:<br />"
						. $cge->getMessage());
			Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ . " error with db-connect. " . $cge->getMessage());
			return false;
		}

		return true;
	} /* end decorateNREN() */


	/**
	 * retrieveMap() Retrieve the map for the NREN from the database.
	 *
	 * This function will go to the database and retrieve the map.
	 *
	 * @param	void
	 * @return	void
	 * @access	private
	 */
	private function retrieveMap()
	{
		if (is_null($this->getID())) {
			throw new ConfusaGenException("Cannot find map for NREN when NREN-ID is not set!");
		}
		$this->hasMap = false;

		try {
			$res = MDB2Wrapper::execute("SELECT * FROM attribute_mapping WHERE nren_id=? AND subscriber_id IS NULL",
						    array('text'),
						    array($this->data['nren_id']));
			if (count($res) == 1) {
				$this->hasMap	= true;
				$this->map	= $res[0];
			}
		} catch (ConfusaGenException $e) {
			/* FIXME */
			Framework::error_output(__FILE__ . ":" . __LINE__ . " " . htmlentities($e->getMessage()));
		}

	} /* end retrievemap() */

	/**
	 * get the list of subscribers for that NREN
	 *
	 * @param void
	 * @return array<Subscriber> the subscribers signed up to this NREN
	 * @since Confusa v0.4-rc0
	 * @access public
	 */
	public function getSubscriberList()
	{
		$subscribers = null;
		$query  = "SELECT subscriber_id, name, org_state, lang, subscr_email, ";
		$query .= "subscr_phone, subscr_resp_name, subscr_resp_email, ";
		$query .= "subscr_comment, dn_name FROM subscribers WHERE nren_id=?";

		$res = MDB2Wrapper::execute($query,
		                            array('integer'),
		                            array($this->getID()));

		if (count($res) > 0) {
			foreach($res as $row) {
				$subs = new Subscriber($row['name'],
				                       $this->getName(),
				                       $row['dn_name'],
				                       $row['org_state'],
				                       $row['subscriber_id']);
				$subs->setEmail($row['subscr_email']);
				$subs->setPhone($row['subscr_phone']);
				$subs->setRespName($row['subscr_resp_name']);
				$subs->setRespEmail($row['subscr_resp_email']);
				$subs->setComment($row['subscr_comment']);
				$subscribers[] = $subs;
			}
		}

		return $subscribers;
	}

	/**
	 * getPrivacyNotice() - return the privacy-notice for the NREN
	 *
	 * @param Person $person the current person (for translating the tags)
	 */
	public function getPrivacyNotice($person)
	{
		$query = "SELECT privacy_notice FROM nrens WHERE nren_id = ?";
		$res = array();
		try {
			$res = MDB2Wrapper::execute($query,
						    array('text'),
						    array($this->getID()));
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_INFO, "[norm] Could not retrieve the privnotice " .
			                  "text of NREN $nren due to an error with the " .
			                  "statement. Server said " . $dbse->getMessage());
			return "";
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_INFO, "[norm] Could not retrieve the privnotice " .
			                  "text of NREN $nren due to an error in the " .
			                  "query. Server said " . $dbqe->getMessage());
			return "";
		}

		if (count($res) > 0) {
			$pn=$res[0]['privacy_notice'];

			$pn=stripslashes($pn);
			$pn=Input::br2nl($pn);
			$textile = new Textile();

			/* replalce tags */
			return $this->replaceTags($textile->TextileRestricted($pn,0), $person);

		}
		return "No privacy-notice has yet been set for your NREN (".
			$this->getName().")<br />";

	}

	/**
	 * getAboutText
	 * Get the about-text for a certain NREN, so it can be displayed in Confusa's
	 * about-section
	 *
	 * @param Person $person the current person for tag-replacement
	 */
	public function getAboutText($person)
	{
		$query = "SELECT about FROM nrens WHERE nren_id = ?";

		try {
			$res = MDB2Wrapper::execute($query,
						    array('text'),
						    array($this->getID()));
		} catch (DBStatementException $dbse) {
			Framework::error_output($this->translateMessageTag('abt_err_dbstat') . " " .
			                        htmlentities($dbse->getMessage()));
			return "";
		} catch (DBQueryException $dbqe) {
			Framework::error_output($this->translateMessageTag('abt_err_dbquery') .  " " .
			                        htmlentities($nren));
			return "";
		}

		if (count($res) > 0) {
			$at = $res[0]['about'];

			$at=stripslashes($at);
			$at=Input::br2nl($at);
			$textile = new Textile();

			return $this->replaceTags($textile->TextileRestricted($at,0), $person);
		} else {
			return "No about-NREN text has been defined for your NREN (" .
				$this->getName(). ")";
		}
	}


	/**
	 * getHelpText()
	 *
	 * Get the custom help text entered for/by a certain NREN
	 *
	 * @param Person $person the current person (for tag-replacement)
	 * @return String $help_text the parsed, replaced and textile-replaced text
	 */
	public function getHelpText($person)
	{
		$query = "SELECT help FROM nrens WHERE nren_id = ?";
		$res = array();
		try {
			$res = MDB2Wrapper::execute($query,
						    array('text'),
						    array($this->getID()));
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_INFO, "[norm] Could not retrieve the help " .
			                  "text of NREN $nren due to an error with the " .
			                  "statement. Server said " . $dbse->getMessage());
			return "";
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_INFO, "[norm] Could not retrieve the help " .
			                  "text of NREN $nren due to an error in the " .
			                  "query. Server said " . $dbqe->getMessage());
			return "";
		}

		if (count($res) > 0) {
			$help_text=$res[0]['help'];

			$help_text = Input::br2nl($help_text);
			$help_text = stripslashes($help_text);

			$textile = new Textile();
			$help_text = $textile->TextileRestricted($help_text,0);
			return $this->replaceTags($help_text, $person);
		}
	} /* end getHelpText() */


	private function replaceTags($text, $person)
	{
		/*
		 * {$subscriber}
		 * {$product_name}
		 * {$confusa_url}
		 * {$subscriber_support_email}
		 * {$subscriber_support_url}
		 */

		$orgName = '';
		$supportMail = '';
		$supportURL = '';

		$subscriber = $person->getSubscriber();

		if (isset($subscriber)) {
			$orgName = $subscriber->getOrgName();
			$supportMail = $subscriber->getHelpEmail();
			$supportURL = $subscriber->getHelpURL();
		}

		$text = str_ireplace('{$subscriber}',
					 $orgName,
					 $text);
		$text = str_ireplace('{$subscriber_support_email}',
					 $supportMail,
					 $text);
		$text = str_ireplace('{$subscriber_support_url}',
					 $supportURL,
					 $text);

		$productName = ConfusaConstants::$PERSONAL_PRODUCT;
		if (Config::get_config('cert_product') == PRD_ESCIENCE) {
			$productName = ConfusaConstants::$ESCIENCE_PRODUCT;
		}
		$text = str_ireplace('{$product_name}', $productName, $text);
		$text = str_ireplace('{$confusa_url}',
				     Config::get_config('server_url'),
				     $text);

		return $text;
	}

	/**
	 * Construct a NREN object from an URL. Sometimes a NREN object is needed
	 * without the user being authenticated and a decorated person object
	 * being available. Since NREN admins can define a custom-URL this function
	 * tries to construct the NREN from such an URL.
	 *
	 * @param nrenURL string the URL that was configured for the NREN
	 * @since v0.6-rc0
	 */
	static function getNRENByURL($nrenURL)
	{
		$query  = "SELECT n.nren_id,      n.name,           n.login_account, ";
		$query .= "       n.contact_email,n.contact_phone,  n.cert_email, ";
		$query .= "       n.cert_phone,   n.lang,           n.url, ";
		$query .= "       n.country,      n.enable_email,   n.cert_validity, ";
		$query .= "       n.show_portal_title,              n.portal_title, ";
		$query .= "       n.wayf_url ";
		$query .= "FROM nrens n WHERE n.url = ?";

		try {
			$res = MDB2Wrapper::execute($query,
			                            array('text'),
			                            array($nrenURL));
		} catch (ConfusaGenException $cge) {
			Framework::error_output("Cannot connect to DB. Server said:<br />"
			                        . $cge->getMessage());
			Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ .
			                  " error with db-connect. " . $cge->getMessage());
			return null;
		}

		if (count($res) > 0) {
			$nren = new NREN(null);

			foreach($res[0] as $k => $value) {
				/* no sanitation needed since the data *comes* from the DB anyways */
				$nren->data[$k] = $value;
			}

			return $nren;
		} else {
			return null;
		}
	} /* end getNRENByURL */

	/**
	 * Get the list of IdPs stored in the DB for this NREN.
	 *
	 * @return array|null an array with all IdP URLs or null if none found
	 */
	public function getIdPList()
	{
		$query = "SELECT m.idp_url FROM idp_map m " .
		         "WHERE m.nren_id = ?";

		try {
			$res = MDB2Wrapper::execute($query,
			                            array('text'),
			                            array($this->getID()));
		} catch (ConfusaGenException $cge) {
			Logger::log_event(LOG_NOTICE, __FILE__ . " " . __LINE__ .  ": Could not " .
			                  "get the IdP list for NREN with ID " .
			                  $this->getID() . ". All IdP scoping will fail!");
		}

		if (count($res) > 0) {
			$idpList = array();

			foreach($res as $row) {
				$idpList[] = $row['idp_url'];
			}
		} else {
			return null;
		}

		return $idpList;
	}

} /* end class NREN */
?>
