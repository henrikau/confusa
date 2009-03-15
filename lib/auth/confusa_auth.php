<?php
  /* Author: Henrik Austad <henrik.austad@uninett.no>
   *
   * Part of Confusa.
   *
   * This is the main authentication module of Confusa.
   */
/* get simplesaml */
require_once('config.php');

/* include _include in the simplesaml-directory
 * simplesaml_path is the _include in the simplesaml directory
 */
require_once(Config::get_config('simplesaml_path'));
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XHTML/Template.php');

require_once('person.php');
require_once('logger.php');
require_once('debug.php');
require_once('mdb2_wrapper.php');
/* global variable to check if the session has been started or not (avoid
 * multiple calls to simple_saml's session_start()
 */
$session_started = false;


/* authenticate_user()
 *
 * This is the main function for checking if the user is authenticated.
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
    }
} /* end authenticate_user */

function deauthenticate_user($person)
{
	if (isset($person)) {
		$person->fed_auth(false);
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

	/* check to see if the person is authN */
	$config = _get_config();
	$session = _get_session();
	if (isset($session)) {
		$person->fed_auth($session->isValid());
		if ($person->is_fed_auth()) {
			add_attributes($person);
		}
	}
	return $person;
} /* end is_authenticated */


/* add_attributes
 *
 * This function decorates a person-object, which must be non-null in order to
 * provide some sane abstraction away from the attributes-array.
 *
 * This in itself is not a good reason for using a dedicated object, but we try
 * to tie as many strings together (like attributes, authentication status etc)
 * and provide a sane interface to the outside world.
 */
function add_attributes($person) 
{
     $attributes = _get_attributes();

     if (!isset($attributes['eduPersonPrincipalName'][0])) {
	  $debug_string=__FILE__ .":".__LINE__." -> eduPersonPrincipalName not set!<BR>\n";
	  Debug::dump($debug_string);
          $person->fed_auth(false);
     }
     else {
	     if (isset($attributes['mobile'][0]))
		     $person->set_mobile($attributes['mobile'][0]);
	     $person->set_name($attributes['cn'][0]);
	     $person->set_common_name($attributes['eduPersonPrincipalName'][0]);
	     $person->set_email($attributes['mail'][0]);
	     $person->set_country($attributes['country'][0]);
	     $person->set_orgname(Config::get_config('cert_o'));
	     $person->set_orgunitname(Config::get_config('cert_ou'));

	     $person->fed_auth(true);
     }
} /* end add_attributes() */

/** logout_link
 *
 * params:
 * @logout_location:	If secondary logout is needed, or some logout-success
 *			message needs to be displayed, this is the page that the user will be
 *			redirected to.
 * @logout_name:	Content of the logout-link
 * @edu_name:		The unique feide name of the person we're logging out (so
 *			that the logout-form can remove info from the database).
 */
function logout_link($logout_location="logout.php", $logout_name="Logout Confusa", $person)
{
     $config = _get_config();
     $edu_name = $person->get_common_name();

     /* need to find the url, and handle some quirks in the result from selfURL
      * in order to get proper url-base */
     $base = SimpleSAML_Utilities::selfURL();
     if (strpos($base, ".php"))
          $base = dirname($base);
     $link_base =  dirname($base).'/simplesaml/saml2/sp/initSLO.php?RelayState='.$base .'/'. $logout_location;
     $link = '<A HREF="' . $link_base . '">' . $logout_name . '</A>';

    return $link;
} // end get_logout_link()


function show_sso_debug($person) {
    if (!isset($person)) {
        echo __FILE__ . ":" . __LINE__ . " person does not exist<BR>\n";
        return;
        
        }
    $config  = _get_config();
    $session = _get_session();
    if($person->is_auth()) {
        $attributes = _get_attributes();
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
            echo "\t<tr>\n\t\t<td>$name</td>\n\t\t<td>" . $value[0] . "</td>\n\t</tr>\n";
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

function _get_attributes()
{ 
    return _get_session()->getAttributes();
}


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
   * http://rnd.feide.no/content/using-simplesamlphp-service-provider#id436365
   */
  if (!isset($session) || !$session->isValid() ) {
       SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'saml2/sp/initSSO.php',array('RelayState' => SimpleSAML_Utilities::selfURL()));
       exit(0);
  }

  /* update person, FIXME: update attributes as well */
  $person->fed_auth($session->isValid());
  add_attributes($person);
} /* end  _assert_sso() */

?>
