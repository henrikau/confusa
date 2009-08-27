<?php
require_once 'input.php';
require_once 'CriticalAttributeException.php';
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

    private $email;
    private $country;

    /* The name of the subscriber, e.g. 'ntnu', 'uio', 'uninett' */
    private $subscriberName;

    private $nren;
    private $entitlement;

    private $session;
    private $saml_config;

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
     * clearAttributes() - reset all attributes known to Person
     *
     * This function will effectively reset the person.
     */
    public function clearAttributes()
    {
	    
	    $this->given_name = null;
	    unset($this->given_name);

	    $this->eppn = null;
	    unset($this->eppn);

	    $this->email = null;
	    unset($this->email);

	    $this->country = null;
	    unset($this->country);

	    $this->subscriberName = null;
	    unset($this->subscriberName);

	    $this->nren = null;
	    unset($this->nren);

	    $this->entitlement = null;
	    unset($this->entitlement);

	    $this->session = null;
	    unset($this->session);

	    $this->saml_config = null;
	    unset($this->saml_config);

	    $this->isAuthenticated = false;
    }

    /**
     * setSession - set a reference to the current session
     *
     * @session : the current session for this (authN) user
     */
    function setSession($session)
    {
	    if (!isset($session)) {
		    return;
	    }
	    $this->session = $session;
    }
    /**
     * setSAMLConfiguration - add a reference to the config
     *
     * The configuration contains a lot of useful information, some of which is
     * directly related to the lifespan of the session.
     *
     * @config - the configuration object for this session/instance.
     */
    function setSAMLConfiguration($config)
    {
	    if (!isset($config)) {
		    return;
	    }
	    $this->saml_config = $config;
    }

    /**
     * getTimeLeft - the time in seconds until the session expires.
     *
     * Each session has a pre-determined lifespan. Confusa (and in return,
     * SimpleSAMLphp) can ask for a particular timeframe, but it is the IdP that
     * decides this.
     *
     * This function returns the time in seconds until the session expires and
     * the user must re-AuthN.
     */
    function getTimeLeft()
    {
	    if (!isset($this->session))
		    return null;
	    return $this->session->remainingTime();
    }

    /**
     * getTimeSinceStart - get the seconds since the session started.
     *
     * The session started when the user last authenticated.
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
     * getX509SubjectDN - construct the complete /DN for a certificate/CSR
     *
     * @return: generated /DN from attributes to use in the certificate subject.
     */
    function getX509SubjectDN()
    {
	    $dn = "";
	    $country	= $this->getCountry();
	    $son	= $this->getSubscriberOrgName();

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
     * isAuth - return a boolean value indicating if the person is AuthN
     *
     * @return boolean (true when person *is* authenticated)
     */
    public function isAuth()
    {
	    return $this->isAuthenticated;
    }

    /**
     * setAuth - set the authN status of the person
     *
     * @auth: a boolean describing the AuthN-status.
     */
    public function setAuth($auth = true)
    {
	    $this->isAuthenticated = $auth;
    }

    /**
     * setName - set the (full) name for the user.
     *
     * A full name, is the name on the form 'John Doe'
     *
     *		http://rnd.feide.no/content/cn
     *
     * @given_name : the full name of the person.
     */
    public function setName($cn) {
	    if (isset($cn)) {
		    $this->given_name = Input::sanitize(trim(htmlentities($cn)));
	    }
    }

    /**
     * getName - return the full name for the person.
     *
     * @return : full, given name, for the person.
     */
    public function getName() { return $this->given_name; }

    public function getSAMLConfiguration() {
	return $this->saml_config;
    }

    /* setEPPN - set the ePPN for the person
     *
     * The eduPersonPrincipalName is a guaranteed unique key, and is widely used
     * within in Confusa for drilling down the identity of the user.
     *
     * @eppn: the ePPN.
     */
    public function setEPPN($eppn)
    {
        if (!isset($eppn)) {
		throw new CriticalAttributeException("eduPersonPrincipalName (or equvivalent token) not set for person!");
	}
	$this->eppn = Input::sanitize(htmlentities($eppn));
    }

    /**
     * getEPPN - return the ePPN for the person.
     *
     * @return : string containing the ePPN for the user
     */
    public function getEPPN()
    {
	    return $this->eppn;
    }

    /** getX509ValidCN - get a valid /CN for a X.509 /DN
     *
     * This will return the common-name attribute for the X.509 subject. As not
     * all characters are printable, this function will also strip those away.
     *
     * @return: a X.509 printable /CN attribute
     */
    public function getX509ValidCN()
    {
	    $res = "";
	    if (isset($this->given_name)) {
		    $tmp_name = $this->given_name;
		    $tmp_name = preg_replace("/[^a-z \d]/i", "", $tmp_name);
		    $res .= $tmp_name . " ";
	    }
	    return $res . $this->getEPPN();
    }

    /**
     * Get the session of a person
     *
     * @return The session that is associated with the person
     */
    public function getSession()
    {
	return $this->session;
    }

    /**
     * setEmail - set a email-address for the person
     *
     * @email : the (new) email address for the person
     */
    public function setEmail($email)
    {
        if (isset($email)) {
		$this->email = Input::sanitize($email);
        }
    }

    /**
     * getEmail - return the registred email-address
     *
     * @return: string containing the email-address
     */
    public function getEmail() { return $this->email; }


    /** setSubscriberOrgName - set the name of the subscriber organization
     *
     * @subscriber
     */
    public function setSubscriberOrgName($subscriber)
    {
	    if (isset($subscriber))
		    $this->subscriberName = strtolower(Input::sanitize($subscriber));
    }

    /**
     * getSusbscriberOrgName - return the name of the person's subscriber organization name. 
     *
     * This is a name of the home-institution, e.g. 'ntnu',  'uio'.
     *
     * @return: string holding the subscriber's name.
     */
    public function getSubscriberOrgName()
    {
	    return $this->subscriberName;
    }


    /**
     * setEduPersonEntitlement - store the entitlement
     *
     * The entitlement is set by the IdP for the user, and we use this to test
     * for admins. This is not a sufficicent conditions, but is is a necessary
     * one.
     *
     * @entitlement: the entitlement for the person
     *
     * TODO:	how to handle the case when several entitlement-attributes are
     *		set.
     */
    public function setEduPersonEntitlement($entitlement)
    {
	    if (isset($entitlement)) {
		    if (is_array($entitlement)) {
			    $this->setEduPersonEntitlement($entitlement[0]);
		    } else {
			    $this->entitlement = strtolower(Input::sanitize($entitlement));
		    }
	    }
    }

    /**
     * getEduPersonEntitlement - return the entitlement.
     *
     * This results the (relevant) entitlement(s).
     *
     * @return string with the entitlement.
     */
    public function getEduPersonEntitlement()
    {
	    return $this->entitlement;
    }


    /**
     * setCountry() - set the country the user belongs to.
     *
     * This is actually a potential problem, as this country is the country
     * where the *NREN* is located. As most federations are national (hence the
     * name), it should be accurate most of the time.
     *
     * @country : the country of the NREN (and in effect, person)
     */
    public function setCountry($country)
    {
	    if (isset($country)) {
		    $this->country = strtoupper(substr(Input::sanitize($country),0, 2));
	    }
    }

    /**
     * getCountry() - return the country for the user
     *
     * @return string with the two-letter
     */
    public function getCountry()
    {
	    return $this->country;
    }


    /**
     * setNREN - set the National Research and Education Network for the user.
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
     * @nren : the NREN the user ultimately belongs to.
     */
    public function setNREN($nren) {
	    if (isset($nren)) {
		    $this->nren = strtolower(Input::sanitize($nren));
	    }
    }

    /**
     * getNREN - return the NREN
     *
     * @return string with the name of the nren
     */
    public function getNREN()
    {
	    return $this->nren;
    }


    /**
     * getMode() - get the current modus for the user
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
     * @return integer indicating the mode of the user
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
     * inAdminMode() - test to see if person is currently in *any* admin-mode
     *
     * This function is intended as a convenient way of getting a yes/no answer
     * to whether or not we should show the user the admin-menu.
     *
     * @return boolean true when the user is in admin-mode, false otherwise
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
     * @new_mode: the new mode for the user.
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
     * isAadmin() - test to see if the user is an admin (of any kind)
     *
     * Test to see if the user is part of the admin-crowd. This will allow the
     * user to add news entries.
     *
     * @return boolean, true if person has admin-privileges in the Confusa instance.
     */
    public function isAdmin()
    {
	    return (int)$this->getAdminStatus() != NORMAL_USER;
    } /* end function isAdmin() */


    /**
     * is(NREN|Subscriber|SubscriberSub)Admin()
     *
     *
     * @return : boolean true when person is the given admin
     */
    public function isNRENAdmin()
    {
	    /* test attribute to see if the person is NREN-admin */
	    if ((int)$this->getAdminStatus() == NREN_ADMIN) {
		    return true;
            }
    }
    public function isSubscriberAdmin()
    {
	    if (!$this->entitlement == "confusaAdmin") {
		    return false;
	    }
	    return (int)$this->getAdminStatus() == SUBSCRIBER_ADMIN;
    }
    public function isSubscriberSubAdmin()
    {
	    if (!$this->entitlement == "confusaAdmin") {
		    return false;
	    }
	    return (int)$this->getAdminStatus() == SUBSCRIBER_SUB_ADMIN;
    }

    /**
     * getAdminStatus - get the admin-level from the database
     *
     * This function assumes isAuth() has been verified.
     */
    private function getAdminStatus()
    {
	    $adminRes = NORMAL_USER;
	    if (!$this->isAuth()) {
		    return NORMAL_USER;
	    }
	    require_once 'mdb2_wrapper.php';
	    $res	= MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=?", array('text'), array($this->eppn));
	    $size	= count($res);
	    db_array_debug($res);
	    if ($size == 1) {
		    if ($res[0]['admin'] == $this->getEPPN())
			    $adminRes = $res[0]['admin_level'];
	    }
	    return $adminRes;
    }
} /* end class Person */
?>
