<?php
require_once 'input.php';
require_once 'output.php';
require_once 'CriticalAttributeException.php';
require_once 'permission.php';
require_once 'Subscriber.php';

/* Person
 *
 * Person is the object describing the user in the system
 *
 * This is a *passive* object, and by passive we mean that this object will
 * never act on it's own. By act, we mean actively change it's own state.
 *
 * During the authentication-phase, the user will
 * *be* authenticated, the user will never authenticate on it's own.
 *
 * When creating a certificate, the attributes will be retrieved *from* the
 * user, and the finished certificate will be handed *to* the user.
 *
 * Finally, when it's time to ship off the certificate, the system will retrieve
 * the appropriate data *from* the user and send it off.
 *
 * Thus, Person is little more than a convenient storage pool of related data.
 *
 * Author: Henrik Austad <henrik.austad@uninett.no>
 */
class Person{

    /* instance-variables: */
    private $given_name;

    /* eduPersonPrincipalName - unique name within the namespace for *all* users */
    private $eppn;
    private $eppnKey;

    private $email;
    private $country;

    /* The name of the subscriber, e.g. 'ntnu', 'uio', 'uninett' */
    private $subscriber;

    private $nren;

    private $session;
    private $saml_config;

    private $adminDBError;

    /* get variables for:
     * Region (i.e. Sor Trondelag)
     * City (i.e. Trondheim)
     * 
     */

    /* status variables (so we poll the subsystem as little as possible) */
    private $isAuthenticated;

    function __construct() {
	    $this->clearAttributes();
    } /* end constructor */

    function __destruct() {
	    $this->clearAttributes();
    }
    /**
     * clearAttributes() resets all attributes known to Person
     *
     * This function will effectively reset the person.
     *
     * @param void
     * @return void
     */
    public function clearAttributes()
    {
	    unset($this->given_name);
	    unset($this->eppn);
	    unset($this->eppnKey);
	    unset($this->email);
	    unset($this->country);

	    unset($this->nren);

	    $this->entitlement = null;


	    $this->session = null;
	    unset($this->session);

	    $this->saml_config = null;
	    unset($this->saml_config);

	    $this->isAuthenticated = false;
	    $this->adminDBError = false;
    }

    /**
     * setSession() sets a reference to the current sessionn
     *
     * @param array The current session for this (authN) user
     */
    function setSession($session)
    {
	    if (!isset($session)) {
		    return;
	    }
	    $this->session = $session;
    }

    /*
     * setSAMLConfiguration() adds a reference to the config
     *
     * The configuration contains a lot of useful information, some of which is
     * directly related to the lifespan of the session.
     *
     * @param array The configuration object/array for this session/instance.
     * @return void
     */
    function setSAMLConfiguration($config)
    {
	    if (!isset($config)) {
		    return;
	    }
	    $this->saml_config = $config;
    }

    /*
     * getSAMLConfiguration find the configuration for this session
     *
     * @param void
     * @return array the configuration items for the currently active session.
     */
    public function getSAMLConfiguration()
    {
	    return $this->saml_config;
    }

   /**
     * getTimeLeft() Time in seconds until the session expires.
     *
     * Each session has a pre-determined lifespan. Confusa (and in return,
     * SimpleSAMLphp) can ask for a particular timeframe, but it is the IdP that
     * decides this.
     *
     * This function returns the time in seconds until the session expires and
     * the user must re-AuthN.
     *
     * @param void
     * @return void
     */
    function getTimeLeft()
    {
	    if (!isset($this->session))
		    return null;
	    return $this->session->remainingTime();
    }

    /**
     * getTimeSinceStart() The seconds since the session started.
     *
     * @param void
     * @return Integer|null Number of seconds since the session started
     */
    function getTimeSinceStart()
    {
	    if (!isset($this->saml_config))
		    return null;
	    $start = $this->saml_config->getValue('session.duration');
	    if (!isset($start)) {
		    echo __FILE__  . ":" . __LINE__ . " Cannot find time of start.<BR />\n";
		    return null;
	    }
	    return $start - $this->getTimeLeft();
    }

    /**
     * getX509SubjectDN()  Complete /DN for a certificate/CSR
     *
     * @return: String The DN in an X.509 certificate
     */
    function getX509SubjectDN()
    {
	    $dn = "";
	    $country	= $this->getCountry();
	    $son	= Output::mapUTF8ToASCII($this->getSubscriberOrgName(false));

	    if (isset($country)) {
		    $dn .= "/C=$country";
	    }
	    if (isset($son)) {
		    $dn .= "/O=$son";
	    }
	    $dn .= "/CN=" . $this->getX509ValidCN();

	    return $dn;
    }

	/**
	 * Return the DN of the person, but in a more "browser-friendly" format,
	 * i.e. separated by commas in the form of C=SE, O=EvilMasterminds, CN= Dr. Evil
	 * instead of /C=SE/O=EvilMastermindes/CN=Dr. Evil
	 *
	 * This is needed for in-browser request signing
	 * @return string the DN in comma-separated format
	 */
    function getBrowserFriendlyDN()
    {
	$dn = "";
	$country	= $this->getCountry();
	$son		= Output::mapUTF8ToASCII($this->getSubscriberOrgName(false));

	if (isset($country)) {
	    $dn .= "C=$country, ";
	}

	if (isset($son)) {
	    $dn .= "O=$son, ";
	}

	$dn .= "CN=" . $this->getX509ValidCN();
	return $dn;

    }
    /**
     * isAuth() Indicating if the person is AuthN
     *
     * @param void
     * @return boolean Value indicating the AuthN-status for a person.
     */
    public function isAuth()
    {
	    return $this->isAuthenticated;
    }

    /**
     * setAuth() Sets the authN status of the person
     *
     * @param $auth a boolean describing the AuthN-status.
     * @return void
     */
    public function setAuth($auth = true)
    {
	    $this->isAuthenticated = $auth;
    }

    /**
     * setName() Sets the (full) name for the user.
     *
     * A full name, is the name on the form 'John Doe'
     *
     *		http://rnd.feide.no/content/cn
     *
     * The name is NOT sanitized due to the possible existance of UTF-8
     * characters such as ä or å which we want to preserve. Make sure to
     * sanitize it in all depending functions such as getX509SubjectDN()
     *
     * @param String given_name (the full name of the person)
     * @return void
     */
    public function setName($cn) {
	    if (isset($cn)) {
		    $this->given_name = trim($cn);
	    }
    }

    /**
     * getName() Get the full name of the person
     *
     * @return String the full given name for the person.
     */
    public function getName()
    {
	    if (isset($this->given_name)) {
			return $this->given_name;
	    } else {
		    return "";
	    }
	    return "";
    }

    /**
     * setEPPN() Sets the ePPN for the person
     *
     * The eduPersonPrincipalName is a guaranteed unique key, and is widely used
     * within in Confusa for drilling down the identity of the user.
     *
     * @param String the eduPersonPrincipalName for this person
     * @return void
     */
    public function setEPPN($eppn)
    {
        if (!isset($eppn)) {
		throw new CriticalAttributeException("eduPersonPrincipalName (or equvivalent token) not set for person!");
	}
	$this->eppn = Input::sanitize($eppn);
    }

    /**
     * getEPPN() returns the ePPN for the person.
     *
     * @return String The ePPN for the user
     */
    public function getEPPN()
    {
			return $this->eppn;
	}

    /**
     * setEPPNKey() set the key in the attributes where the ePPN is located
     *
     * @param String the key in the attribute array where ePPN can be found
     * @return void
     */
    public function setEPPNKey($eppnKey)
    {
	    if (isset($eppnKey)) {
		    $this->eppnKey = $eppnKey;
	    }
    }

    /**
     * getEPPNKey() find the key in the attribute-array holding the ePPN-value
     *
     * @param void
     * @return String the key for ePPN
     */
    public function getEPPNKey()
    {
	    return $this->eppnKey;
    }

    /** getX509ValidCN()  get a valid /CN for an X.509 /DN
     *
     * This will return the CommonName-attribute in an X.509 certificate
     * subject. As not all characters are printable, this function will also
     * strip these away, possibly altering the expected content slightly.
     *
     * @return String the X.509 printable /CN attribute (mapped to ASCII and sanitized)
     */
    public function getX509ValidCN()
    {
	    $name = $this->getName(false);
	    if ($name == "") {
		    return null;
	    }
	    /* note that mapping to ASCII will also sanitize */
	    $cn = Output::mapUTF8ToASCII($name) . " " . $this->getEPPN(false);
		return $cn;
    }

    /**
     * getSession() the session of a person
     *
     * @param void
     * @return array The session that is associated with the person
     */
    public function getSession()
    {
	    if (!isset($this->session)) {
		    return null;
	    }
	    return $this->session;
    }

    /**
     * setEmail() set a email-address for the person
     *
     * @param String the (new) email address for the person
     * @return void
     */
    public function setEmail($email)
    {
	    if (!is_null($email)) {
		    $this->email = Input::sanitize($email);
	    } else {
		    $msg  = "Troubles with attributes. No mail address available. ";
		    $msg .=" You will not be able to sign new certificates until this attribute is available.<br />\n";
		    Framework::error_output($msg);
	    }
    } /* end setEmail() */

    /**
     * getEmail() return the registred email-address
     *
     * @return: string containing the email-address
     */
    public function getEmail() {
	    if (!isset($this->email)) {
		    return null;
	    }

		return $this->email;
	}


    /**
     * getSubscriberOrgName() The name of the person's subscriber organization name.
     *
     * This is a name of the home-institution, e.g. 'ntnu',  'uio'.
     *
     * @return String The subscriber's name.
     * @deprecated
     */
    public function getSubscriberOrgName()
    {
	    if (Config::get_config('debug')) {
		    echo __CLASS__ . "::" . __FUNCTION__ . " " . __FILE__ .":".__LINE__. " <font color=\"red\"><b>Deprecated</b></font><br />\n";
	    }
	    Logger::log_event(LOG_DEBUG, __CLASS__ . ":" . __FUNCTION__ . " deprecated");
	    if (isset($this->subscriber)) {
		    return $this->subscriber->getOrgName($browserDisplay);
	    }
	    return "";
    }

    /**
     * setSubscriber()
     *
     * This name is used to find the correct row in the database, and from that,
     * get what we use in the certificate (the subscriberName).
     *
     * @param String the name to use for the subscriber in the database.
     * @return void
     */
    public function addSubscriber($db_name)
    {
	    if (isset($db_name) && $db_name != "") {
		    if (!isset($this->subscriber)) {
			    $this->subscriber = new Subscriber($db_name, $this->nren);
		    }
	    }
    }

    /**
     * getSubscrberIdPName() Return the name we use as key in the database.
     *
     * @param void
     * @return String the name of the subscriber used as key in the database.
     * @deprecated
     */
    public function getSubscriberIdPName()
    {
	    if (Config::get_config('debug')) {
		    echo __CLASS__ . "::" . __FUNCTION__ . " " . __FILE__ .":".__LINE__. " <font color=\"red\"><b>Deprecated</b></font><br />\n";
	    }
	    Logger::log_event(LOG_DEBUG, __CLASS__ . ":" . __FUNCTION__ . " deprecated");
	    return $this->subscriber->getIdPName();
    }

    /**
     * setEntitlement() store the entitlement
     *
     * The entitlement is set by the IdP for the user, and we use this to test
     * for admins and eligble users. This is not something Person should care
     * about, so all we do here is adding the entitlements into an associative
     * array so we can search for explicit attributes later.
     *
     * @param mixed $entitlement for the person.
     * @return void
     */
    public function setEntitlement($entitlement)
    {
	    if (isset($entitlement)) {
		    if (!isset($this->entitlement)) {
			    $this->entitlement = array();
		    }
		    if (is_array($entitlement)) {
			    foreach ($entitlement as $key => $value) {
				    $this->setEntitlement($value);
			    }
		    } else {
			    $val = Input::sanitize($entitlement);
			    $this->entitlement[strtolower($val)] = $val;
		    }
	    }
    }

    /**
     * getEntitlement()  Returns the (relevant) entitlement(s).
     *
     * The function will return either a string-represenatation of the
     * entitlement if $strigify is true, otherwise it will return the array as
     * it is stored internally by person.
     *
     * @param  Boolean True if we want a string-representation of the entitlement
     * @return mixed The entitlement if set. null otherwise
     */
    public function getEntitlement($stringify = true)
    {
	    if (!isset($this->entitlement) || !is_array($this->entitlement)) {
		    return null;
	    }
	    if ($stringify) {
		    $res = "";
		    foreach($this->entitlement as $key => $val) {
			    $res .= "$val, ";
		    }
		    $res = substr($res, 0, strlen($res)-2);
	    } else {
		    $res = $this->entitlement;
	    }
	    return $res;
    }

    /**
     * testEntitlementAttribute() If a given attribute is part of the entitlement field.
     *
     * @param String The attribute to test for in the supplied entitlement field
     * @return Boolean $hasAttribute indicating if the queried attribute has been set 
     */
    public function testEntitlementAttribute($attribute)
    {
	    $hasAttribute = false;
	    $attr = strtolower(Input::sanitize($attribute));
	    return isset($this->entitlement[$attr]);
    }

    /**
     * setCountry() Sets the country the user belongs to.
     *
     * This is actually a potential problem, as this country is the country
     * where the *NREN* is located. As most federations are national (hence the
     * name), it should be accurate most of the time. The country is sanitized
     *
     * @param String The country of the NREN (and in effect, person)
     * @return void
     */
    public function setCountry($country)
    {
	    if (isset($country)) {
		    $this->country = strtoupper(Input::sanitize(substr($country,0, 2)));
	    }
    }

    /**
     * getCountry() - return the country-code for the user's country.
     *
     * @return String two-letter country code
     */
    public function getCountry()
    {
		return $this->country;
    }


    /**
     * setNREN() set the National Research and Education Network for the user.
     *
     * the NREN is found via the IdP. One user can only belong to one IdP and
     * one IdP can only belong to one NREN.
     *
     *			NREN
     *                _/  | \_
     *            ___/    |   \__
     *         __/        |      \__
     *        /           |         \
     *   IdP(A)		IdP(B) ...  IdP(n)
     *          ______/  |  \_______
     *         /         |          \
     *   User_(a)      User_(b) .... User_(m)
     *
     * The nren will be stored as lowercase only to make sure things are
     * consistent all the way through confusa.
     *
     * @param String the name of the NREN the user ultimately belongs to.
     * @return void
     */
    public function setNREN($nren) {
	    if (isset($nren)) {
		    $this->nren = strtolower(Input::sanitize($nren));
	    }
    }

    /**
     * getNREN() return the NREN the user belongs to.
     *
     * @return String The name of the nren
     */
    public function getNREN()
    {
		return $this->nren;
    }


    public function setSubscriber($subscriber)
    {
	    if (is_null($subscriber)) {
		    return;
	    }
	    $this->subscriber = new Subscriber($subscriber, $this->nren);
    }

    /**
     * getMode() Gets the current modus for the user
     *
     * This returns the mode the user displays the page in. Even an
     * administrator (of any kind) can view the page as a normal user, and this
     * will be stored in the database for this particluar user.
     *
     * Note that *only* administrators will have a table-row in the
     * database. Any non-admin, normal users will not be stored in the database
     * (allthough data, such as CSRs and certificates will be stored).
     *
     * This function will look at the type of user and return the mode based on
     * this and information stored in the database (if admin)
     *
     * NORMAL_MODE: 0
     * ADMIN_MODE:  1
     *
     * @param void
     * @return Integer The mode of the user
     */
    public function getMode()
    {
	    /* If user is not admin, the mode is NORMAL_MODE either way */
	    if (!$this->isAdmin()) {
		    return NORMAL_MODE;
	    }
	    $res = MDB2Wrapper::execute("SELECT last_mode FROM admins WHERE admin=?",
					array('text'),
					array($this->getEPPN()));
	    db_array_debug($res);
	    if (count($res) != 1) {
		    return NORMAL_MODE;
	    }
	    /* We could just return $res['last_mode'][0] but in case the
	     * database schema is ever updated, we do not have to worry about
	     * potentional holes to plug.
	     *
	     * I.e. if new modes are to be added, this part must be updated.
	     */
	    if ($res[0]['last_mode'] == ADMIN_MODE) {
		    return ADMIN_MODE;
	    }

	    return NORMAL_MODE;
    }

    /**
     * inAdminMode() If person is currently in *any* admin-mode
     *
     * This function is intended as a convenient way of getting a yes/no answer
     * to whether or not we should show the user the admin-menu.
     *
     * @param void
     * @return boolean True if user is admin, false otherwise
     */
    public function inAdminMode()
    {
	    return $this->getMode() == ADMIN_MODE;
    }

    /**
     * setMode() - set the mode for a given person.
     *
     * Enable a user to switch between normal and admin-mode. The input-mode
     * must be a value recognized by confusa:
     *
     *		i.e. either ADMIN_MODE or NORMAL_MODE
     *
     * @param String the new mode for the user.
     * @return void
     */
    public function setMode($new_mode)
    {
	    $new = (int)$new_mode;
	    if ($new == NORMAL_MODE || $new == ADMIN_MODE) {
		    if ($this->isAdmin()) {
			    Logger::log_event(LOG_DEBUG, "Changing mode (-> $new_mode) for " . $this->getEPPN());
			    MDB2Wrapper::update("UPDATE admins SET last_mode=? WHERE admin=?",
						array('text', 'text'),
						array($new, $this->getEPPN()));
		    }
	    }
    }

    /**
     * isAadmin() Test to see if the user is an admin (of any kind)
     *
     * Test to see if the user is part of the admin-crowd. This will allow the
     * user to add news entries.
     *
     * @param void
     * @return boolean True if person has admin-privileges in the Confusa instance.
     */
    public function isAdmin()
    {
	    return (int)$this->getAdminStatus() != NORMAL_USER;
    } /* end function isAdmin() */


    /**
     * isNRENAdmin() Test to see if the user has NRENAdmin-rights
     *
     * @param void
     * @return boolean true when person is NREN-Administrator
     */
    public function isNRENAdmin()
    {
	    /* test attribute to see if the person is NREN-admin */
	    return $this->getAdminStatus() == NREN_ADMIN;
    }

    /**
     * isSubscriberAdmin() Test to see if the user has SubscriberAdmin-rights
     *
     * @param void
     * @return boolean true when person is the Subscriber-Admin
     */
    public function isSubscriberAdmin()
    {
	    if (!$this->testEntitlementAttribute(Config::get_config('entitlement_admin'))) {
		    return false;
	    }
	    /* If the user has no subscriber set, he/she *cannot* be a
	     * administrator */
	    $epodn = $this->subscriber->getOrgName();
	    if (!isset($epodn) || $epodn === "") {
		    return false;
	    }

	    return (int)$this->getAdminStatus() == SUBSCRIBER_ADMIN;
    }

    /**
     * isSubscriberSubAdmin() Test to see if the user is subadmin for a subscriber.
     *
     * @param void
     * @return boolean true when person is subscriber sub-admin
     */
    public function isSubscriberSubAdmin()
    {
	    if (!$this->testEntitlementAttribute(Config::get_config('entitlement_admin'))) {
		    return false;
	    }
	    $epodn = $this->subscriber->getOrgName();
	    if (!isset($epodn) || $epodn === "") {
		    return false;
	    }
	    return (int)$this->getAdminStatus() == SUBSCRIBER_SUB_ADMIN;
    }

    /**
     * getAdminStatus() get the admin-level from the database
     *
     * This function assumes isAuth() has been verified.
     *
     * @param void
     * @return Integer value indication the admin-level
     */
    private function getAdminStatus()
    {
	    $adminRes = NORMAL_USER;
	    if (!$this->isAuth()) {
		    return NORMAL_USER;
	    }

	    /* if the database is riddled with errors, do not run through the
	     * test once more, just bail */
	    if ($this->adminDBError) {
		    return NORMAL_USER;
	    }
	    require_once 'mdb2_wrapper.php';
	    $errorCode = create_pw(8);

	    $res	= MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=?", array('text'), array($this->eppn));
	    $size	= count($res);
	    db_array_debug($res);
	    if ($size == 1) {
		    $adminRes = $res[0]['admin_level'];
		    if ($this->getName(false) != $res[0]['admin_name'] ||
			$this->getEmail(false) != $res[0]['admin_email']) {
			    try {
				    MDB2Wrapper::update("UPDATE admins SET admin_name=?, admin_email=? WHERE admin_id=?",
							array('text', 'text', 'text'),
							array($this->getName(false), $this->getEmail(false), $res[0]['admin_id']));
			    } catch (DBStatementException $dbse) {
				    $msg = "[$errorCode] Database not properly set. Missing fields in the admins-table.";
				    Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ . $msg);
				    Framework::error_output($msg . "<br />Server said: " . $dbse->getMessage());
				    $this->adminDBError = true;
			    } catch (DBQueryException $dbqe) {
				    Logger::log_event(LOG_INFO, "[$errorCode] Could not update data for admin." . $dbqe->getMessage());
				    Framework::error_output("[$errorCode] Could not update data for admin. Problems with keys. Server said: "
							    . $dbqe->getMessage());
				    $this->adminDBError = true;
			    } catch (Exception $e) {
				    $msg = "Could not update admin-data. Unknown error. Server said: " . $e->getMessage();
				    Framework::error_output($msg);
				    Logger::Log_event(LOG_INFO, $msg);
				    $this->adminDBError = true;
			    }
		    }
	    }
	    return $adminRes;
    } /*  end getAdminStatus() */

	/**
	 * Return if this person may request a new certificate. This is dependant
	 * on a few conditions:
	 * 		- person is fully decorated
	 * 		- 'confusa' entitlement is set
	 * 		- subscriber of the person is in state 'subscribed'
	 *
	 * @return permission object containing
	 * 		permissionGranted true/false based on whether the permission was granted
	 * 		reasons array with reasons for granting/rejecting the permissions
	 */
	public function mayRequestCertificate()
	{
		$permission = new Permission();
		$permission->setPermissionGranted(true);

		if (empty($this->eppn)) {
			$permission->setPermissionGranted(false);
			$permission->addReason("Need a properly formatted eduPersonPrincipal name!");
		}

		if (empty($this->given_name)) {
			$permission->setPermissionGranted(false);
			$permission->addReason("Need a given-name to place in the certificate!");
		}

		if (empty($this->email)) {
			$permission->setPermissionGranted(false);
			$permission->addReason("Need an email-address to send notifications to!");
		}

		if (empty($this->country)) {
			$permission->setPermissionGranted(false);
			$permission->addReason("Need a country name for the certificates!");
		}

		if (empty($this->subscriberName)) {
			$permission->setPermissionGranted(false);
			$permission->addReason("Need a properly formatted Subscriber name!");
		}

		if (empty($this->entitlement)
				|| !$this->testEntitlementAttribute(Config::get_config('entitlement_user'))) {
			$permission->setPermissionGranted(false);
			$permission->addReason(Config::get_config('entitlement_user') .
								" entitlement must be set but is not set!");
		}

		$query = "SELECT org_state FROM subscribers WHERE name=?";

		/* Bubble up exceptions */
		$res = MDB2Wrapper::execute($query,
				array('text'),
				array($this->db_name));

		if (count($res) == 0) {
			$permission->setPermissionGranted(false);
			$permission->addReason("Your institution " . $this->db_name .
					" was not found in the database!");
			return $permission;
		} else if (count($res) > 1) {
			throw new AuthException("More than one DB-entry with same subscriberOrgName " .
					$this->subscriberOrgName);
		}

		if ($res[0]['org_state'] !== 'subscribed') {
			$permission->setPermissionGranted(false);
			$permission->addReason("Your institution " . $this->db_name .
					" is currently not subscribed to the portal!");
		}

		return $permission;
	}
} /* end class Person */
?>
