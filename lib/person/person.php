<?php
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
    private $common_name;
    private $email;
    private $country;
    private $orgname;
    private $idp;
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
        $this->given_name = null;
        $this->common_name = null;
        $this->email = null;
        $this->entitlement = null;

        /* we're suspicious by nature */
        $this->isAuthenticated = false;
        } /* end constructor */

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
    function getX509SubjectDN() {
	    $dn = "/C=" . $this->get_country() . "/O=" . $this->get_orgname() . "/CN=" . $this->get_valid_cn();
	    return $dn;
    }

    /**
     * isAuth - return a boolean value indicating if the person is AuthN
     *
     * @return boolean (true when person *is* authenticated)
     */
    public function isAuth() {
	    return $this->isAuthenticated;
    }

    /**
     * setAuth - set the authN status of the person
     *
     * @auth: a boolean describing the AuthN-status.
     */
    public function setAuth($auth = true) {
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
		    $this->given_name = trim(htmlentities($cn));
	    }
    }

    public function get_name() { return $this->given_name; }

    /* "Safe" function
     *
     * THis returns a 'safe representation' of the person's name.
     * As a user's name can contain different special characters, whitespace and
     * other nonsense, we remove it here, sothat elements that require *very*
     * sanitized input, can call this instead of the original get_name()
     */
    public function get_safe_name() {
	    /* remove non-printable characters, or, only allow printable characters */
	    $tmp_name = $this->given_name;
	    $tmp_name = preg_replace("/[^a-z \d]/i", "", $tmp_name);

	    return $tmp_name;
    }

    public function set_common_name($cn) {
        if (isset($cn)) {
             $this->common_name = htmlentities(str_replace("'", "", $cn));
         }
        }
    public function get_common_name() { return $this->common_name; }

    public function get_valid_cn() {
        if (isset($this->given_name)) {
	        return $this->get_safe_name() . " " . $this->get_common_name();
        } else {
            return $this->get_common_name();
        }
    }
    public function set_email($email) {
        if (isset($email)) {
            $this->email = htmlentities($email);
        }
        }
    public function get_email() { return $this->email; }


    public function set_orgname($orgname) {
	    if (isset($orgname))
		    $this->orgname = $orgname;
    }
    public function get_orgname() { return $this->orgname; }

    public function set_entitlement($entitlement) {
      if (isset($entitlement)) {
        $this->entitlement = $entitlement;
      }
    }

    public function get_entitlement() { return $this->entitlement; }

    public function get_keyholder() { return $this->keyholder; }

    public function set_country($c)
         {
              if (isset($c)) {
                   $this->country = htmlentities($c);
               }
         }
    public function get_country() { return $this->country; }

    public function set_idp($idp) {
	    if (isset($idp)) {
		    $this->idp = $idp;
        }
    }
    public function get_idp() { return $this->idp; }

    public function set_nren($nren) {
	    if (isset($nren))
		    $this->nren = $nren;
    }
    public function get_nren() { return $this->nren; }


    /**
     * get_mode() - get the current modus for the user
     *
     * This returns the mode the user displays the page in. Even an
     * administrator (of any kind) can view the page as a normal user, and this
     * will be stored in the database for the user.
     *
     * This function will look at the type of user and return the mode based on
     * this and information stored in the database (if admin)
     *
     * NORMAL_MODE: 0
     * ADMIN_MODE:  1
     */
    public function get_mode()
    {
	    /* If user is not admin, the mode is NORMAL_MODE either way */
	    if (!$this->is_admin()) {
		    return NORMAL_MODE;
        }
	    $res = MDB2Wrapper::execute("SELECT last_mode FROM admins WHERE admin=?",
					array('text'),
					array($this->get_common_name()));
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

    public function in_admin_mode()
    {
	    return $this->get_mode() == ADMIN_MODE;
    }
    /**
     * set_status() - set the mode for a given person.
     *
     * Enable a user to switch between normal and admin-mode.
     */
    public function set_mode($new_mode)
    {
	    $new = (int)$new_mode;
	    if ($new == 0 || $new == 1) {
		    if ($this->is_admin()) {
			    Logger::log_event(LOG_DEBUG, "Changing mode (-> $new_mode) for " . $this->get_common_name());
			    MDB2Wrapper::update("UPDATE admins SET last_mode=? WHERE admin=?",
						array('text', 'text'),
						array($new, $this->get_common_name()));
		    }
	    }
    }

    /* is_admin()
     *
     * Test to see if the user is part of the admin-crowd. This will allow the
     * user to add news entries.
     */
    public function is_admin()
    {
	    if (!$this->isAuth()) {
		    return false;
        }
	    return (int)$this->get_admin_status() != NORMAL_USER;
    } /* end function is_admin() */

    public function is_nren_admin()
    {	    	
	    if (!$this->isAuth()) {
		    return false;
        }

	    if ($this->entitlement == "confusaAdmin") {
	        /* test attribute to see if the person is NREN-admin */
	        if ((int)$this->get_admin_status() == NREN_ADMIN) {
		        return true;
            }
        }
	    /* add user to table of nren-admins (to save page mode for later) */
	    return (int)$this->get_admin_status() == NREN_ADMIN;
    }


    public function is_subscriber_admin()
    {
	    if (!$this->isAuth()) {
		    return false;
        }

	    return (int)$this->get_admin_status() == SUBSCRIBER_ADMIN;
    }

    public function is_subscriber_subadmin()
    {
	    if (!$this->isAuth()) {
		    return false;
        }

	    return (int)$this->get_admin_status() == SUBSCRIBER_SUB_ADMIN;
    }
    /**
     * get_admin_status - get the admin-level from the database
     *
     * This function assumes is_auth() has been verified.
     */
    private function get_admin_status()
    {
	    require_once 'mdb2_wrapper.php';
	    $res = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=?", array('text'), array($this->common_name));
	    $size = count($res);
	    db_array_debug($res);
	    if ($size == 1) {
		    if ($res[0]['admin'] == $this->get_common_name())
			    return $res[0]['admin_level'];
		    echo __FILE__ . ":" . __LINE__ . "<B>Uuuugh! Unreachable point! How did you get here?</B><BR>\n";
	    }
	    return NORMAL_USER;
    }
} /* end class Person */
?>
