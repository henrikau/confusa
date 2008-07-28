<?php
/* get simplesaml */
require_once(dirname(__FILE__).'/config.php');
/* include _include in the simplesaml-directory */
require_once(Config::get_config('simplesaml_path'));
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XHTML/Template.php');

require_once('sms_auth.php');
require_once('person.php');
require_once('logger.php');
require_once('mdb2_wrapper.php');
/* global variable to check if the session has been started or not (avoid
 * multiple calls to simple_saml's session_start()
 */
$session_started = false;


/* authenticate_user()
 *
n * This is the main function for checking if the user is authenticated.
 *
 * If the user is not authenticated, this function will either redirect or
 * include login-panel so that the user may log in.
 *
 *
 * scenario 1: user is neither feide nor sms-authenticated:
 *      user is redirected to feide. All control ends here untill user refreshes
 *      page and we see that he/she is authenticated
 *
 * scenario 2: user is feide-authenticated, but has not auth. via sms-pw yet.
 *      a one-time password with default 15 min is created and sent to user via
 *      the registred  mobile-number.
 *
 * scenario 3: user is feide-auth, has received a passwd and want to
 *      authenticate with this.
 *      Since the user has a password set, the validity of the pw is checked
 *      (and a new issued if it has expired and resent to the user) before
 *      'false' is returned.
 */
function authenticate_user($person)
    {
    /* check to see if the person is authenticated. If the person is
     * authenticated OK, is_authenticated will update the auth-fields of person,
     * but also fill in all the remaining fields. 
     */
         $person = is_authenticated($person);

    if (!$person->is_auth()) {
        /* assert SSO
         * Make sure the feide-login is OK.
         */
        _assert_sso($person);

	
        /* assert SMS
         *
         * Make sure that a proper one-time password has been sent to the user,
         * entered by the user and a valid session is in place. 
         */
        if ($person->is_fed_auth() && Config::get_config('use_sms')) {
		_assert_sms($person);
	}
    }
} /* end authenticate_user */

function deauthenticate_user($person)
{
	if (isset($person)) {
		$person->fed_auth(false);
		$person->sms_auth(false);
	}
	$session = _get_session();

        /* TODO: move this to sms_auth! */
	if (Config::get_config('use_sms')) {
	    /* remove edu_name from database */
             $name = str_replace('\\', '', htmlentities($_GET['edu_name']));
	    $name = strip_tags($name);
	    $name = str_replace("'", "", $name);
    
	    /* find user and session */
            $res = MDB2Wrapper::execute("SELECT username, session_id FROM sms_auth WHERE username=?",
                                        array('text'),
                                        array($name));
	    if (count($res) > 0) {
		    /* make sure we're not trying to deauthenticate a different user! */
                 if ($res[0]['session_id'] == session_id() && $res[0]['username'] == $name) {
                            MDB2Wrapper::update("DELETE FROM sms_auth WHERE username=?",
                                                array('text'),
                                                array($name));
		    }
		    else {
                         Logger::log_event(LOG_NOTICE, "Will not drop another user from db! " . $_SERVER['REMOTE_ADDR']);
                         echo "You cannot drop another user from the database!<BR>\n";
                         echo "This incident <B>will</B> be reported!<BR>\n";
		    }
	    }
	    else {
                    Logger::log_event(LOG_NOTICE, "Cannot drop nonexisting user $name from database. Connecting from " . $_SERVER['REMOTE_ADDR']);
	    }
	}
}

/* is_authenticated()
 *
 * This function takes as argument a person, and checks if this person is authenticated.
 * *NOTE* this function does not check the fields of the person-object, it will
 * check the subsystem and *update* the values in person to reflect this.
 *
 * Hence; this is the authoriative-authentication source for any person.
 */
function is_authenticated($person = null) {
	if (!isset($person))
		$person = new Person();

	/* check to see if the person is feide-auth */
	$person->fed_auth(_is_feide_auth());
	if ($person->is_fed_auth()) {
		/* update fields of person */
		$attribs = get_attributes();
		$person->set_mobile($attribs['mobile'][0]);
		$person->set_name($attribs['cn'][0]);
		$person->set_common_name($attribs['eduPersonPrincipalName'][0]);
		$person->set_email($attribs['mail'][0]);
                $person->set_country('NO');
		/* push user to sms-auth */
		if (Config::get_config('use_sms'))
			$person->sms_auth(_test_sms($person));

	}
	return $person;
} /* end is_authenticated */


function reset_sms_password($person) {
	$sms = new SMSAuth($person);
	$sms->reset_pw();
    }
/* ======================================================================
 * Feide part of the auth-library
 * ====================================================================== */

function _assert_sso($person) 
{
  $config  = _get_config();
  $session = _get_session();

  /* Check if valid local session exists..
   *
   * If not:
   *	session set
   *	session valid
   * Do:
   *	set new header
   *
   * http://rnd.feide.no/content/using-simplesamlphp-service-provider#id436365
   */
  if (!isset($session) || !$session->isValid() ) {
       SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'saml2/sp/initSSO.php',array('RelayState' => SimpleSAML_Utilities::selfURL()));
       exit(0);
  }

  /* update person, FIXME: update attributes as well */
  $person->fed_auth($session->isValid());
  
} /* end  _assert_sso() */

function show_sso_debug($person) {
    if (!isset($person)) {
        echo __FILE__ . ":" . __LINE__ . " person does not exist<BR>\n";
        return;
        
        }
    $config  = _get_config();
    $session = _get_session();
    if($person->is_auth()) {
        $attributes = get_attributes();
        $time_left = $session->remainingTime();
        $hours_left = (int)($time_left / 3600);
        $mins_left = (int)(($time_left % 3600)/60);
        $secs_left = (int)(($time_left % 3600) % 60);
        printn("<HR>");
        printBRn("<B>session and attributes from SimpleSAML debug info:</B>");
        printBRn("Your session is valid for ". 
                 $hours_left . "h " . 
                 $mins_left  . "min " . 
                 $secs_left  . "seconds<BR>");

        printn("<table>");
        foreach ($attributes AS $name => $value) {
            /* several values in the field */
            echo "\t<tr>\n\t\t<td>" . $name . "</td>\n\t\t<td>" . $value[0] . "</td>\n\t</tr>\n";
        }
        printn("</table>");
        printn("<HR>");
    }
}
/* gets the config from the SimpleSAML-package. (abstract layer, don't want this
 * in general code.
 *
 * Parameters: none
 * Returns: config-descriptor from simple-saml
 */
function _get_config() 
{
    return SimpleSAML_Configuration::getInstance();
}

/* _get_session()
 *
 * Returns: the session-descriptor from SimpelSAML. If the session has not been
 * started, this function will also start a new session for you. :-)
 */
function _get_session() 
{
    // ensure that session_start() is only called once
    global $session_started;
    if (!$session_started) {
        session_start();
        $session_started=true;
    }
    return SimpleSAML_Session::getInstance();
}

function get_attributes() 
{
    return _get_session()->getAttributes();
}

/* feide_logout_link
 *
 * params: logout_location (which page should handle the logout from Feide?)
 *         logout_name (the logout-name)
 *         edu_name: The unique feide name of the person we're logging out (so
 *         that the logout-form can remove info from the database). 
 */
function feide_logout_link($logout_location="logout.php", $logout_name="Logout Confusa", $person) 
{
     $config = _get_config();
     
     /* $attr= get_attributes(); */

     $edu_name = $person->get_common_name();/* $attr['eduPersonPrincipalName'][0]; */

     /* need to find the url, and handle some quirks in the result from selfURL
      * in order to get proper url-base */
     $base = SimpleSAML_Utilities::selfURL();
     if (strpos($base, ".php"))
          $base = dirname($base);
     $link_base =  $base.'/saml2/sp/initSLO.php?RelayState='.$base .'/'. $logout_location;
     if (Config::get_config('use_sms'))
          $link_base .= "?edu_name='" . $edu_name;

    $link = '<A HREF="' . $link_base . '">' . $logout_name . '</A>';

    return $link;
} // end get_logout_link()

function _is_feide_auth()
    {
    /* check if user is sso-auth */
    $config  = _get_config();
    $session = _get_session();
    if (isset($session) && $session->isValid())
        return true;
    return false;
    }


/* _assert_sms()
 *
 * call the SMSAuth part and make sure that the one_time_pass is handled correctly.
 * if this assertion fails, this method will not only return false (to indicate
 * that the user is *not* authenticated), but also include the login-form so
 * that the user can enter the password.
 */
function _assert_sms($person) 
{
	return _validate_sms($person, true);

} /* end _assert_sms() */

function _test_sms($person)
{
	return _validate_sms($person, false);
}

function _validate_sms($person, $assert)
{
    $valid_sms_user = false;
    $sms = New SMSAuth($person);

    /* set default timeout for one-time-pass and session
     * This can be overriden/changed.
     *
     * Planned in a later release.. :-)
     */
    $sms->set_pw_timeout(15);
    $sms->set_session_timeout(30, true);
    $valid_sms_user = $sms->assert_user();
    /* echo __FILE__ . ":".  __LINE__ . " done asserting user<br>\n"; */
    $person->sms_auth($valid_sms_user);
    /* if the user is not authorized via SMS, show the login-form
     * the subsystem will handle creation and distribution of the password by itself.
     */
    if (!$valid_sms_user && $assert)
        {
             Logger::log_event(LOG_NOTICE, "unauthorized user, awaiting password");
		include('login_form.php');
        }
    return $valid_sms_user;
}

?>
