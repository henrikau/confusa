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
    private $mobile;
    private $given_name;
    private $common_name;
    private $email;
    private $db_id;
    private $country;

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

        /* we're suspicious by nature */
        $this->fed_auth = false;
        $this->sms_auth = false;
        } /* end constructor */

    function __tostring() {
        $var = "<table clas=\"small\">";

        if (isset($this->given_name))
             $var .= "<tr><td><b>Name:</b></td><td>" . $this->given_name . "</td></tr>\n";

        if (isset($this->common_name))
             $var .= "<tr><td><B>eduPersonPrincipalName:</b></td><td>" . $this->common_name . "</td></tr>\n";

        if (isset($this->mobile))
             $var .= "<tr><td><b>mobile</b>:</td><td>" . $this->mobile . "</td></tr>\n";

        if (isset($this->email))
             $var .= "<tr><td><b>email:</b></td><td>" . $this->email . "</td></tr>\n";
        if (isset($this->country))
             $var .= "<tr><td><b>Country:</b></td><td>" . $this->country . "</td></tr>\n";

        $var .= "</table><br>";
        return $var;
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
         if (isset($given_name)) $this->given_name = htmlentities($given_name);
        }
    public function get_name() { return $this->given_name; }

    public function set_common_name($cn) {
        if (isset($cn)) 
             $this->common_name = htmlentities(str_replace("'", "", $cn));
        }
    public function get_common_name() { return $this->common_name; }

    public function set_email($email) {
        if (isset($email)) 
            $this->email = htmlentities($email);
        }
    public function get_email() { return $this->email; }

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


    /* is_admin()
     *
     * Test to see if the user is part of the admin-crowd. This will allow the
     * user to add news entries.
     */
    public function is_admin()
    {
         if (!$this->is_auth())
              return false;

         require_once('mdb2_wrapper.php');
         $res = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin=?", array('text'), array($eppn));
         if (count($res) != 1)
              return false;

         return true;
    } /* end function is_admin() */

  } /* end class Person */
?>
