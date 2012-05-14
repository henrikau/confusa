<?php
require_once 'confusa_include.php';
require_once 'MDB2Wrapper.php';
require_once 'CGE_CriticalAttributeException.php';
require_once 'classTextile.php';

/**
 * Placeholder for an NREN
 *
 * This class contains information about, and operation on this information for
 * an NREN. The class gives the framework a nice, clean way of retrieving
 * per-NREN information¸ and also for storing this information.
 *
 * @author	Henrik Austad <henrik.austad@uninett.no>
 * @license	http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @since	File available since Confusa v0.4-rc0
 * @package	resources
 */
class NREN
{
	private $idp_name;
	private $map;
	private $hasMap;
	private $isValid;

	private $data;
	private $pendingChanges;
	private $maint_msg;

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
	 * Get the IdP from which the NREN was constructed.
	 * @since v0.6-rc0
	 * @return String the URL of the IdP from which this NREN was constructed
	 */
	public function getIdP()
	{
		return $this->idp_name;
	} /* end getIdP() */

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
	 * getMaintMode()
	 *
	 * FIXME
	 */
	public function getMaintMode()
	{
		return Input::sanitizeMaintMode($this->data['maint_mode']);
	}

	public function inMaintMode() { return $this->getMaintMode() === "y"; }

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
	 * getReauthTimeout()
	 *
	 * @param  void
	 * @return integer the timeout before the portal will force reauth
	 *                 upon sensitive actions
	 */
	public function getReauthTimeout()
	{
		if (isset($this->data) && isset($this->data['reauth_timeout'])) {
			return $this->data['reauth_timeout'];
		} else {
			ConfusaConstants::$DEFAULT_REAUTH_TIMEOUT;
		}
	} /* end getReauthTimeout() */

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
	 * set WAYFURL() Set the WAYF-service URL for the NREN
	 *
	 * @param String $url the address of the WAYF service
	 * @return Boolean false if URL is malformed
	 * @access public
	 * @since v0.6-rc0
	 */
	public function setWAYFURL($url)
	{
		if (!is_null($url)) {
			if ($this->data['wayf_url'] != $url) {
				if (empty($url) || preg_match("/^http[s]?/",$url, $matches)) {
					$this->data['wayf_url'] = Input::sanitizeURL($url);
					$this->pendingChanges = true;
				} else {
					return false;
				}
			}
		}
		return true;
	} /* end setWAYFURL() */

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
				$this->data['url'] = Input::sanitizeURL($url);
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
				$this->data['contact_email'] = Input::sanitizeEmail($contact_email);
				$this->pendingChanges = true;
			}
		}
	}
	/**
	 * getContactEmail()
	 *
	 * You can choose if only the contact-address or address + name should be returned
	 *
	 *			foo@example.org
	 *			Foo B. Ar <foo@example.org>
	 *
	 * @param Boolean formatted if the address should be nicely formatted for page-inclusion
	 * @return String the address
	 * @access public
	 */
	public function getContactEmail($formatted=false)
	{
		if (!is_null($this->data) && array_key_exists('contact_email', $this->data)) {
			if ($formatted && array_key_exists('name', $this->data)) {
				return $this->data['name'] . " <" . $this->data['contact_email'] . ">";
			}
			return $this->data['contact_email'];
		}
		return false;
	}

	/**
	 * setContactPhone()
	 *
	 * @see setContactEmail
	 */
	public function setContactPhone($contact_phone)
	{
		if (!is_null($contact_phone)) {
			if ($this->data['contact_phone'] != $contact_phone) {
				$this->data['contact_phone'] = Input::sanitizePhone($contact_phone);
				$this->pendingChanges = true;
			}
		}
	}

	/**
	 * setCertEmail() set the NREN's CERT-team emailaddress.
	 *
	 * @param	String $cert_email
	 * @return	void
	 * @access	public
	 */
	public function setCertEmail($cert_email)
	{
		if (!is_null($cert_email)) {
			if ($this->data['cert_email'] != $cert_email) {
				$this->data['cert_email'] = Input::sanitizeEmail($cert_email);
				$this->pendingChanges = true;
			}
		}
	}

	/**
	 * setReauthTimeout() set the NREN's reauth-timeout for sensitive actions
	 *
	 * @param	integer $reauth_timeout
	 * @return	void
	 * @access	public
	 */
	public function setReauthTimeout($reauth_timeout)
	{
		if (isset($reauth_timeout)) {
			if ($this->data['reauth_timeout'] != $reauth_timeout) {
				$this->data['reauth_timeout'] = $reauth_timeout;
				$this->pendingChanges = true;
			}
		}
	} /* end setReauthTimeout() */

	/* setCertPhone()
	 *
	 * @see setCertEmail
	 */
	public function setCertPhone($cert_phone)
	{
		if (!is_null($cert_phone)) {
			if ($this->data['cert_phone'] != $cert_phone) {
				$this->data['cert_phone'] = $cert_phone;
				$this->pendingChanges = true;
			}
		}
	}

	/**
	 * setEnableEmail() store the value to how emails shold be let into the
	 * SAN.
	 *
	 * A certificate can store 0 to multiple addresses in the SAN (Subject
	 * Alternative Name). This is configurable on an NREN-basis.
	 *
	 * It can have the values
	 * '0'	: No certiticates are allowed in the SAN *at all*
	 * '1'	: One, and only one. if multiple addresses are returned from the
	 *	  IdP, a selection where the user must pick *one* is created.
	 * 'n'	: Multiple addresses, or 0, the  decision is left to the user.
	 * 'm'	: Multiple addressed, but *at least* one.
	 *
	 * @param	String $enable_email
	 * @return	void
	 * @access	public
	 */
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

	/**
	 * setCertValidity() set the limit for how long a certificate should be valid.
	 *
	 * If in eScience, the  only value is 395 days, and the function will
	 * not store the number in this mode.
	 *
	 * In personal, you can have 3 (and it is for personal this function is necessary):
	 * - 365 days
	 * - 730 days
	 * - 1095 days
	 *
	 * Also not, if the portal is in test-mode ('capi_test'), the
	 * certificate validity is *always* 14 days regardless of mode and
	 * configured days.
	 *
	 * @param	String $validity number of days
	 * @return	void
	 * @access	public
	 */
	public function setCertValidity($validity)
	{
		if (isset($validity) &&
		    (Config::get_config('cert_product') === PRD_PERSONAL)) {
			if (!array_key_exists('cert_validity', $this->data) ||
			    ($this->data['cert_validity'] != $validity)) {

				$this->data['cert_validity'] = $validity;
				$this->pendingChanges = true;
				return;
			}
		}
	}

	/**
	 * setShowPortalTitle() set the flag to indicate whether or not the
	 * portal title should be shown.
	 *
	 * @param	Boolean $showPortalTitle
	 * @return	void
	 * @access	public
	 */
	public function setShowPortalTitle($showPortalTitle)
	{
		if (isset($showPortalTitle)) {
			if (!array_key_exists('show_portal_title', $this->data) ||
			   ($this->data['show_portal_title'] != $showPortalTitle)) {
				$this->data['show_portal_title'] = $showPortalTitle;
				$this->pendingChanges = true;
			}
		}
	}

	/**
	 * setCustomPortalTitle() Set a customized portal title for the NREN
	 *
	 * An NREN can set the title to whatever it likes.
	 *
	 * @param	String $portalTitle the new title
	 * @return	void
	 * @access	public
	 */
	public function setCustomPortalTitle($portalTitle)
	{
		if (isset($portalTitle)) {
			if (!array_key_exists('portal_title', $this->data) ||
			($this->data['portal_title'] != $portalTitle)) {
				$this->data['portal_title'] = $portalTitle;
				$this->pendingChanges = true;
			}
		}
	}

	/**
	 * setMaintMode() update the maintenance-mode of the portal
	 *
	 * @param String $mode the new mode
	 */
	public function setMaintMode($mode)
	{
		if (!isset($mode) || $this->data['maint_mode'] === $mode) {
			return false;
		}

		unset($this->data['maint_mode']);
		$this->data['maint_mode'] = $mode;
		$this->pendingChanges = true;
		return $this->saveNREN();
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
			$query .= " show_portal_title=?, portal_title=?, wayf_url=?, reauth_timeout=?, maint_mode=? ";
			$query .= "WHERE nren_id=?";
			$params	= array('text','text', 'text', 'text', 'text', 'text', 'text',
			                'text', 'text', 'text', 'text', 'text', 'text');
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
					$this->data['wayf_url'],
					$this->data['reauth_timeout'],
					$this->data['maint_mode'],
					$this->getID());
			try {
				MDB2Wrapper::update($query, $params, $data);
			} catch (DBQueryException $dqe) {
				Framework::error_output("Could not change NREN-information! Maybe something is " .
							"wrong with the data that you supplied? Server said: " .
							htmlentities($dqe->getMessage()));
				Logger::log_event(LOG_INFO, "[nadm] Could not update " .
						  "information of NREN " . $this->data['name'] . ": " .
						  $dqe->getMessage());
				return false;
			} catch (DBStatementException $dse) {
				Framework::error_output("Could not change NREN-information! Confusa " .
							"seems to be misconfigured. Server said: " .
							htmlentities($dse->getMessage()));
				Logger::log_event(LOG_WARNING, "[nadm] Could not update " .
						  "information of NREN " . $this->data['name'] . ": " .
								  $dse->getMessage());
				return false;
			}

			Logger::log_event(LOG_INFO, "[nadm] Updated NREN (".$this->getName().") information ");
			$this->pendingChanges = false;
			return true;
		}
		return false;
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
	 * | cert_phone    | varchar(16)       | YES  |     | NULL    |                |
	 * | enable_email  | enum('0','1','n') | YES  |     | NULL    |                |
	 * | maint_msg     | text              | YES  |     | NULL    |                |
	 * | maint_mode    | enum('y','n')     | YES  |     | n       |                |
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
		$query .= "		n.country,	n.maint_mode,	idp.idp_url as idp_url, ";
		$query .= "		n.enable_email,	n.cert_validity, ";
		$query .= "		n.show_portal_title, n.portal_title, n.wayf_url, n.reauth_timeout ";
		$query .= "FROM idp_map idp LEFT JOIN ";
		$query .= "nrens n on idp.nren_id = n.nren_id WHERE idp.idp_url=?";
		try {
			$res = MDB2Wrapper::execute($query, array('text'), array($this->idp_name));
			switch (count($res)) {
			case 0:
				if (Config::get_config('debug')) {
					echo "no IdP with name (".$this->idp_name.") found in db!<br />\n";
				}
				Logger::log_event(LOG_NOTICE, "Could not find NREN-map for idp " . $this->idp_name .
						  ". Is the NREN bootstrapped properly?");
				return false;
			case 1:
				/* decorate NREN */
				foreach ($res[0] as $k => $value) {
					$key = strtolower(Input::sanitizeText($k));
					$this->data[$key] = Input::sanitizeText($value);
				}

				/* hack to work around MySQLs very limited view on the difference
				 * between 0 and NULL
				 */
				if (is_null($this->data['enable_email'])) {
					$this->data['enable_email'] = 0;
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
		}
		return "No about-NREN text has been defined for your NREN (" .
			$this->getName(). ")";
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
		return "No Help-text for your NREN (" .
			$this->getName(). ") can be found in the system.";
	} /* end getHelpText() */


	/**
	 * Set/update maintenance message for a given NREN.
	 *
	 * @param Person $person
	 * @param String $msg the new NREN maint-mode message
	 * @returns Boolean true if update was successful
	 * @access public
	 */
	public function setMaintMsg($person, $msg)
	{
		if (!isset($msg)||!isset($person))
			return false;

		try {
			MDB2Wrapper::update("UPDATE nrens SET maint_msg=?  WHERE nren_id=?",
								array('text', 'text'),
								array($msg, $this->getID()));
		} catch (DBQueryException $dbqe) {
			/* FIXME */
			;
		} catch (DBStatementException $dbse) {
			/* FIXME */
			;
		}

		unset($this->maint_msg);
		$this->getMaintMsg();
		if ($this->maint_msg !== $msg) {
			Logger::log_event(LOG_ERR, "Could not save NREN-maintenance-message for $nname to DB.");
			return false;
		}
		Logger::log_event(LOG_NOTICE, $person->getEPPN() . "(".
						  $person->getName().
						  ") updated maintenance-message for " .
						  $this->getName());
		return true;
	}

	/**
	 * getMaintMsg() read the maintenance-mode message for this given NREN from DB.
	 *
	 * @param void
	 * @returns String the message from the database
	 * @throws ConfusaGenException if the message could not be retrieved from the database.
	 */
	public function getMaintMsg()
	{
		if (!is_null($this->maint_msg))
			return $this->maint_msg;

		try {
			$res =  MDB2Wrapper::execute("SELECT maint_msg FROM nrens WHERE nren_id=?",
						     array('text'),
						     array($this->getID()));
		} catch (DBQueryException $dbqe) {
			/* FIXME */
			;
		} catch (DBStatementException $dbse) {
			/* FIXME */
			;
		}
		if (count($res) == 1) {
			if (array_key_exists('maint_msg', $res[0])) {
				$this->maint_msg = $res[0]['maint_msg'];
			}
		}
		if (!isset($this->maint_msg)) {
			/* log error */
			Logger::log_event(LOG_INFO, "Could not retrieve maint-mode msg for nren " .
							  $this->getID() . "(".$this->getName() . "). This is probably because it is not set.");
			/* return empty string (should be set by admin) */
			return "";
		}
		return $this->maint_msg;
	} /* end getMaintMsg() */

	/**
	 * Get the list of IdPs stored in the DB for this NREN.
	 *
	 * @param	void
	 * @return	array|null an array with all IdP URLs or null if not found
	 * @access	public
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

	/**
	 * replaceTags() take the texdt and replace known tags with
	 * corresponding value
	 *
	 * We use tags in a lot of the texts to allow the admins to create
	 * dynamic pages. When we display the page, these tags must be replaced
	 * with updated values.
	 *
	 * @param	String $text the text containing the tags to replace
	 * @param	Person $person the current person
	 * @return	String $text the text with the tags replaced.
	 * @access	private
	 */
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
} /* end class NREN */
?>
