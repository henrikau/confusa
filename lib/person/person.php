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
n * When creating a certificate, the attributes will be retrieved *from* the
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
    private $mobile;
    private $given_name;
    private $common_name;
    private $email;
    private $db_id;
    private $country;
    private $orgname;
    private $idp;
    private $entitlement;
    /* get variables for:
     * Region (i.e. Sor Trondelag)
     * City (i.e. Trondheim)
     * 
     */

    /* status variables (so we poll the subsystem as little as possible) */
    private $fed_auth;
    private $sms_auth;


    function __construct() {
        $this->mobile = null;
        $this->given_name = null;
        $this->common_name = null;
        $this->email = null;
        $this->entitlement = null;

        /* we're suspicious by nature */
        $this->fed_auth = false;
        $this->sms_auth = false;
        } /* end constructor */

    function __tostring() {
        $var = "<table clas=\"small\">";
	$var .= "<tr><td><b>Name:</b></td><td>" . $this->get_name() . "</td></tr>\n";
	$var .= "<tr><td><B>eduPersonPrincipalName:</b></td><td>" . $this->get_common_name() . "</td></tr>\n";
	$var .= "<tr><td><B>CommonName in DN</b></td><td>" . $this->get_valid_cn() . "</td></tr>\n";
	$var .= "<tr><td><b>mobile</b>:</td><td>" . $this->get_mobile() . "</td></tr>\n";
	$var .= "<tr><td><b>email:</b></td><td>" . $this->get_email() . "</td></tr>\n";
	$var .= "<tr><td><b>Country:</b></td><td>" . $this->get_country() . "</td></tr>\n";
	$var .= "<tr><td><b>OrganizationalName:</b></td><td>" . $this->get_orgname() . "</td></tr>\n";
	$var .= "<tr><td><b>Entitlement:</b></td><td>" . $this->get_entitlement() . "</td></tr>\n";
	$var .= "<tr><td><b>IdP:</b></td><td>". $this->get_idp() . "</td></tr>\n";
	$var .= "<tr><td><b>Complete /DN:</b></td><td>". $this->get_complete_dn() . "</td></tr>\n";
        $var .= "</table><br>";
        return $var;
    }

    function get_complete_dn() {
	    $dn = "/C=" . $this->get_country() . "/O=" . $this->get_orgname() . "/CN=" . $this->get_valid_cn();
	    return $dn;
    }

    public function is_fed_auth() {
        return $this->fed_auth;
        }
    public function is_sms_auth() {
        return $this->sms_auth;
        }
    public function is_auth() {
	    if (Config::get_config('use_sms'))
		    return $this->is_fed_auth() && $this->is_sms_auth();
	    return $this->is_fed_auth();
        }
    public function fed_auth($auth = true) {
        $this->fed_auth = $auth;
        }

    public function sms_auth() {
         $sms = New SMSAuth($person);
         /* set default timeout for one-time-pass and session
          * This can be overriden/changed.
          *
          * Planned in a later release.. :-)
          */
         $sms->set_pw_timeout(15);
         $sms->set_session_timeout(30, true);

         $this->sms_auth = $sms->assert_user();
    }


    public function set_mobile($mobile) {
        if (isset($mobile))
             $this->mobile = htmlentities($mobile);
        }

    public function get_mobile() { return $this->mobile; }

    public function set_name($given_name) {
	    if (isset($given_name)) {
		    $this->given_name = trim(htmlentities($given_name));
				
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
        if (isset($cn)) 
             $this->common_name = htmlentities(str_replace("'", "", $cn));
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
        if (isset($email)) 
            $this->email = htmlentities($email);
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

    public function set_db_id ($id) { if (isset($id)) { $this->db_id = htmlentities($id); } }
    public function get_db_id () { return $this->db_id; }
    public function has_db_id () { return isset($this->db_id); }

    public function set_country($c)
         {
              if (isset($c))
                   $this->country = htmlentities($c);
         }
    public function get_country() { return $this->country; }

    public function set_idp($idp) {
	    if (isset($idp))
		    $this->idp = $idp;
    }
    public function get_idp() { return $this->idp; }


    /**
     * get_mode() - get the current modus for the user
     *
     * This returns the mode the user displays the page in. Even an
     * administrator (of any kind) can view the page as a normal user, and this
     * will be stored in the database for the user.
     *
     * This function will look at the type of user and return the mode based on
     * this and information stored in the database (if admin)
     */
    public function get_mode()
    {
	    if (!$this->is_admin())
		    return NORMAL_MODE;
	    $res = MDB2Wrapper::execute("SELECT last_mode FROM admins WHERE admin=?",array('text'), array($this->get_common_name()));
	    if (count($res) != 1)
		    return NORMAL_MODE;

	    /* We could just return $res['last_mode'][0] but in case the
	     * database schema is ever updated, we do not have to worry about
	     * potentional holes to plug.
	     *
	     * I.e. if new modes are to be added, this part must be updated.
	     */
	    if ($res['last_mode'][0] == ADMIN_MODE)
		    return ADMIN_MODE;
	    return NORMAL_MODE;
    }

    /**
     * set_status() - set the mode for a given person.
     *
     * Enable a user to switch between normal and admin-mode.
     */
    public function set_mode($new_status)
    {
	    $new = (int)$new_status;
	    if ($new == 0 || $new == 1) {
		    if ($this->is_admin()) {
			    MDB2Wrapper::update("UPDATE admins SET last_mode=? WHERE admin=?", array('text', 'text'), array($new, $this->get_common_name()));
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
	    if (!$this->is_auth())
		    return false;

	    return (int)$this->get_admin_status() != NORMAL_USER;
    } /* end function is_admin() */

    public function is_nren_admin()
    {
	    if (!$this->is_auth())
		    return false;

	    if ($this->entitlement == "confusaAdmin")
	    /* test attribute to see if the person is NREN-admin */
	    if ((int)$this->get_admin_status() == NREN_ADMIN)
		    return true;
	    /* add user to table of nren-admins (to save page mode for later) */
	    return (int)$this->get_admin_status() == NREN_ADMIN;
    }


    public function is_subscriber_admin()
    {
	    if (!$this->is_auth())
		    return false;

	    return (int)$this->get_admin_status() == SUBSCRIBER_ADMIN;
    }

    public function is_subscriber_subadmin()
    {
	    if (!$this->is_auth())
		    return false;

	    return (int)$this->get_admin_status() == SUBSCRIBER_SUB_ADMIN;
    }
    /**
     * get_admin_status - get the admin-level from the database
     */
    private function get_admin_status()
    {
	    require_once 'mdb2_wrapper.php';
	    $res = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=?", array('text'), array($this->common_name));
	    $size = count($res);
	    if ($size == 1) {
		    if ($res[0]['admin'] == $this->get_common_name())
			    return $res[0]['admin_level'];
		    echo __FILE__ . ":" . __LINE__ . "<B>Uuuugh! Unreachable point! How did you get here?</B><BR>\n";
	    }
	    return NORMAL_USER;
    }
} /* end class Person */
?>
