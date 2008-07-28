<?php
require_once('mdb2_wrapper.php');
require_once('pw.php');
require_once('sms_commons.php');
require_once('mail_manager.php');
require_once('logger.php');
require_once('person.php');
require_once('config.php');

/* ======================================================================
 * sms_auth.php
 * 
 * SMS part of the auth-library
 *
 * For an understanding, please refer to the documentation/auth_progress
 * 
 * This part has the following tasks:
 * - after the user has been feide-authenticated, a single, one-time, short-lived
 *   password is sent to the registred mobile-number.
 * - A login-face will prompt for this password.
 * - when the correct password has been entered, a session is created with the
 *   same timeout as the feide-timeout.
 *
 * There are multiple scenarios which this package must handle:
 * - the user is not authenticated (trivial, default case)
 * - the user has an old session but has logged out of feide (hence a new
 *   session must be created.
 * - the user has no session, password has been generated, but there's no mobile
 *   number registred. A default error-page should be displayed with indication
 *   to what the user must do, to register this number.
 * ====================================================================== */

class SMSAuth
    {
	    private $sms_debug;
	    private $table_name;
	    private $person;
	    private $ptl;
	    private $stl;

    function __construct($pers)
        {
             if (!isset($pers)) {
			echo __FILE__ . " This must be given a person-object!<BR>\n";
			return;
		}
		$this->person = $pers;
		if ($this->person->get_mobile() == null && !sms_debug)
                {
                    echo "<BR><BR><BR><BR>\n";
                    echo "<CENTER><FONT color=\"RED\">You <B>MUST</B> have the mobile-field set in attribues to be ";
                    echo "able to use this page.<BR>\n";
                    echo "Contact your local IT-administration to correct this issue<BR>\n";
                    echo "</FONT></CENTER>";
                    echo "<BR><BR><BR><BR>\n";
                }
		$this->edu_name                 = str_replace("'", "", $this->person->get_common_name());
		$this->mobile                   = str_replace(" ", "", $this->person->get_mobile());
		$this->table_name               = Config::get_config('mysql_default_table');
                $this->sms_debug                = Config::get_config('sms_debug');
		$this->sms_gw_addr =            = Config::get_config('sms_gw_addr');

		$this->set_pw_timeout(Config::get_config('sms_pw_timeout'));
		$this->set_session_timeout(Config::get_config('sms_session_timeout'), true);
            
        } /* end constructor */

    /* set_pw_timeout()
     *
     * Sets the default timeout for a newly created one_time_pass.
     * The constructor sets this to 15 by default, but this can be overridden by
     * the caller
     */
    function set_pw_timeout($timeout_min)
        {
            if ($timeout_min > 0 && $timeout_min < 60)
                $this->ptl = sprintf("0 0:%u:0", $timeout_min);
        } /* end set_pw_timeout() */

    /* set_session_timeout()
     *
     * sets the default timeout for the session.
     * Pr. default, it will reset the timeout every time a protected page is
     * refreshed so that you have a (pr. default) 30 min idle-time before timing
     * out. 
     */
    function set_session_timeout($timeout_min, $updatable)
        {
            if ($timeout_min > 0 && $timeout_min < 60)
                $this->stl = sprintf("0 0:%u:0", $timeout_min);
        } /* end set_session_timeout */

    /* assert_user()
     *
     * asserts the user to the database.
     * this is the primary authentication function for the SMS-layer of the
     * confusa-auth library.
     * This will:
     *  - check if the user is in the database, or add it to the database
     *  - check if the password in the database is valid, or issue a new one if
     *    it's not.
     *  - check if the password supplied by the user matches the one in the
     *    database
     *
     * params  : None
     * returns : true if the user has successfully authenticated him-/herself
     *           via the one_time_pass sent via SMS
     */
    public function assert_user()
        {
		/* find user in database. get_person_id will create a new if none exists. */
		$this->get_person_id();
		/* valid session? No need to check password,
		 * session is OK, return true :-) */
		if ($this->valid_sms_session()) {
			return true;
		}

        /* check password. If it validates, we're ok
         * If so, set a valid session and authenticate the user.
         */
        if($this->validate_password()) 
            {
            $this->set_valid_session();
            return true;
            }
        /* user not authenticated :( */
        return false;
        } /* end assert_user */

    /* clear_user()
     *
     * Clears all info of the user from the database. This effectively logs the
     * user out of the SMS-layer
     */
    public function remove_user()
    {
        $this->clear_from_db();
    }

    public function reset_pw()
    {
        $this->create_new_pw();
    }

    /* check if the user is in the database
     *
     * If the person is found, the id is returned
     * If not found, the persion is added and the new id is returned.
     * If, for some reason, multiple instances of the person is found (since
     * edu_name is not primary key), all elements are dropped and a new is
     * created. If, one person was logged in, and then added one extra time,
     * he/she must log in again.
     */
    private function get_person_id()
        {
		/* only one element? */
		$query = "SELECT id FROM " . $this->table_name . " WHERE username='" .  $this->edu_name . "'";
                $res = MDB2Wrapper::execute("SELECT id FROM " . $this->table_name . " WHERE username=?", 
                                            array('text'), 
                                            array($this->person->get_common_name()));
                
		switch(count($res)) {
		case 0:
			/* user not found, need to be added to database. */
			$this->add_to_db();
			break;
		case 1:
			/* one and only. Do nothing */
                     $this->person->set_db_id($res[0]['id']);
                     break;
		default:
			/* several instances. Remove all end create a brand new. */
			$this->clear_from_db();
			$this->add_to_db();
			break;
		} /* switch-case */
        } /* end get_person_id() */

    /* add_to_db()
     *
     * adds the person to the database. 
     */
    private function add_to_db()
        {
        MDB2Wrapper::update("INSERT INTO " . $this->table_name . "(username) VALUES(?)", 
                            array('text'), 
                            array($this->person->get_common_name()));
	/* set new password in database */
	$this->create_new_pw();
        } /* end add_to_db */

    /* clear_from_db()
     *
     * removes the person from the database. It matches edu_name, not id, so
     * multiple instances are cleared.
     */
    private function clear_from_db()
    {
        MDB2Wrapper::update("DELETE FROM $this->table_name WHERE username=?", 
                            array('text'), 
                            array($this->person->get_common_name()));
    } /* end clear_from_db() */

    /* valid_sms_session()
     *
     * Checks if the user has an existing, valid session. If so, update the
     * timeout to prevent unnecessary reauthentication and return true to notify
     * the rest of the system that user is auth ok.
     *
     * If the session is *not* ok (eiter no session exists, or there exists an
     * old session, but it has timed out), session_id is cleared and false is returned.
     *
     * params: None
     * returns: true if session is valid, false otherwise.
     */
    private function valid_sms_session()
        {
        /* is session_id set? we *know* that the user exists, and that the
         * userid is valid */
             $query  = "SELECT id, session_id FROM " . $this->table_name . " WHERE id=?";
             $query .= " AND session_id IS NOT NULL";
             $query .= " AND valid_untill > current_timestamp()";
             $res = MDB2Wrapper::execute($query, 
                                         array('integer'), 
                                         array($this->person->get_db_id()));
        /* if we get a hit, we get more than 0 rows, and hence, we have a valid
         * session. See the SQL-query for details
         */
        if (count($res) > 0)
            {
            $this->update_session_timeout();
            return true;
            }
        
        /* remove session info, the session is clearly invalid, or one does not exist. */
        MDB2Wrapper::update("UPDATE $this->table_name SET session_id=NULL WHERE id=?", 
                            array('integer'), 
                            array($this->person->get_db_id()));
        return false;
        }

    /* update_session_timeout() (pure procedure)
     *
     * Updates the current session-timeout with given session_timeout_limit
     * this helps us progressing the session_timeout. See valid_sms_session()
     * for details
     *
     * params : none
     * returns: none
     */
    private function update_session_timeout()
        {
            MDB2Wrapper::update("UPDATE $this->table_name SET valid_untill = addtime(current_timestamp(), ?) WHERE id=?",
                                array('text', 'integer'),
                                array($this->stl, $this->person->get_db_id()));
        } /* end update_session_timeout() */

    private function set_valid_session()
        {
        /* set session */
            MDB2Wrapper::update("UPDATE $this->table_name SET session_id=? WHERE id=?",
                                array('text', 'integer'),
                                array(session_id(), $this->person->get_db_id()));
            $this->update_session_timeout();
            
            /* remove pw */
            MDB2Wrapper::update("UPDATE $this->table_name SET one_time_pass=NULL WHERE id=?",
                                array('integer'),
                                array($this->person->get_db_id()));
        }

    /* validate_password
     *
     * This method tries to authenticate the user via the one_time_pass.
     * It will first check if the pass in the database itself is valid, and only
     * if this pw is valid, will it try to determine wether or not the user has
     * provieded the correct password via the input field.
     *
     * This method will return false if the user is *not* one_time_pass
     * authenticated.
     *
     * It will return true if the password itself is valid *and* the user has
     * provided it via the login-form.
     * The function will then proceed to clear the password (so that it cannot
     * be reused), set a session_id (default is whatever NOT NULL) and a
     * session_timeout via the valid_untill field. 
     */
    private function validate_password()
        {
        /* is password set and valid? */
        if (!$this->isvalid_pw()) 
            {
            /* create new pw and insert it into the database */
            $this->create_new_pw();
            return false;
            }
        else {
		/* the user wants to generate a new one-time-password */
            if (isset($_POST['new_pw'])) {
                return false;
                /* $this->remove_pw(); */
                /* return $this->validate_password(); */
            }
            /* the password is set, *and* valid, hence, we can check if the */
            /* user has provided it for us. */
            else if (isset($_POST['passwd'])) {
                 $user_pw = htmlentities($_POST['passwd']);
                $res = MDB2Wrapper::execute("SELECT one_time_pass FROM $this->table_name WHERE id=?",
                                            array('integer'),
                                            array($this->person->get_db_id()));
                $db_pw = $res[0]['one_time_pass'];

                /* if password is correct  */
                if (scramble_passwd($user_pw) === $db_pw) {
                     Logger::log_event(LOG_NOTICE, "acceptet one-time password from user at " . $_SERVER['REMOTE_ADDR']);
			return true;
                }
                else {
			echo "<FONT COLOR="RED"><B>Wrong password!</B></FONT><BR>";
			echo "\n";
                }
            }
        } /* end ifelse */
        return false;
        }


    private function create_new_pw() {
	    if (!$this->person->has_db_id()) {
		    $this->get_person_id();
	    }

            $pw     = create_pw(8);
            MDB2Wrapper::update("UPDATE $this->table_name SET one_time_pass=? , valid_untill = addtime(current_timestamp(), ?) WHERE id=?",
                                array('text', 'text', 'integer'),
                                array(scramble_passwd($pw), $this->ptl, $this->person->get_db_id()));
            
            /* send to user */
            $this->send_pw($pw);
    }
        
    /* valid_pw()
     *
     * checks to see if the one_time_pass is set and if it is set,
     * that it is not too old.
     *
     * If this function returns true, the one_time_pass is 'valid' and may be
     * used for authentication.
     */
    private function isvalid_pw() 
        {
        if (count(MDB2Wrapper::execute("SELECT * FROM $this->table_name WHERE id=? AND one_time_pass IS NOT NULL AND valid_untill > current_timestamp()",
                                       array('integer'),
                                       array($this->person->get_db_id()))) > 0)
             return true;
        return false;
        }
    
    private function send_pw($pw)
    {
	    $body       = "Your new onetime password for confusa: " . $pw . ". ";
	    $body	.= ".The password is valid for 15minutes.";
	    /* length of message (atm 86, well within the required 160) */
	    /* echo "length: " . strlen($body) . "<br>\n"; */
	    if (isset($this->mobile) && !$this->sms_debug) {
		    $adr        = $this->sms_gw_addr;
		    $subject    = "sms " . $this->mobile;
		    mail($adr, $subject, $body);
	    }
	    if ($this->sms_debug)
		    echo "Password created " . $pw . " , sending to " . $this->mobile . " <BR>\n";
        }
    }     /* end class SMSAuth */
?>
