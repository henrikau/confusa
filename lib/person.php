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
 */
class Person{

    /* instance-variables: */
    private $mobile;
    private $given_name;
    private $common_name;
    private $email;
    private $db_id;

    /* get variables for:
     * Region (i.e. Sor Trondelag)
     * City (i.e. Trondheim)
     * 
     */

    /* status variables (so we poll the subsystem as little as possible) */
    private $fed_auth;
    private $sms_auth;


    private $config;
    function __construct() {
        $this->mobile = null;
        $this->given_name = null;
        $this->common_name = null;
        $this->email = null;

        /* we're suspicious by nature */
        $this->fed_auth = false;
        $this->sms_auth = false;

	global $confusa_config;
	$this->config = $confusa_config;
        } /* end constructor */
    function __destruct() {

        } /* end destructor */

    function __tostring() {
        $var = "";
        $var .= "Name: " . $this->given_name . "<BR>\n";
        $var .= "eduPersonPrincipalName: " . $this->common_name . "<BR>\n";
        $var .= "mobile: " . $this->mobile . "<BR>\n";
        $var .= "email: " . $this->email . "<BR>\n";
        return $var;
        }
    public function is_fed_auth() {
        return $this->fed_auth;
        }
    public function is_sms_auth() {
        return $this->sms_auth;
        }
    public function is_auth() {
	    if ($this->config['use_sms'])
		    return $this->is_fed_auth() && $this->is_sms_auth();
	    return $this->is_fed_auth();
        }
    public function fed_auth($auth = true) {
        $this->fed_auth = $auth;
        }

    public function sms_auth($auth = true) {
        $this->sms_auth = $auth;
    }

    public function set_mobile($mobile) {
        if (isset($mobile))
            $this->mobile = $mobile;
        }

    public function get_mobile() { return $this->mobile; }

    public function set_name($given_name) {
        if (isset($given_name)) $this->given_name = $given_name;
        }
    public function get_name() { return $this->given_name; }

    public function set_common_name($cn) {
        if (isset($cn)) 
            $this->common_name = $cn;
        }
    public function get_common_name() { return $this->common_name; }

    public function set_email($email) {
        if (isset($email)) 
            $this->email = $email;
        }
    public function get_email() { return $this->email; }

    public function get_keyholder() { return $this->keyholder; }

    public function set_db_id ($id) { if (isset($id)) { $this->db_id = $id; } }
    public function get_db_id () { return $this->db_id; }
    public function has_db_id () { return isset($this->db_id); }
      } /* end class Person */
?>
