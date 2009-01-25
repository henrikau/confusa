<?php
  /* Author: Henrik Austad <henrik.austad@uninett.no>
   *
   * Part of Confusa.
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
	$person->fed_auth(_is_authN());
	if ($person->is_fed_auth()) {
             add_attributes($person);
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
     $attributes = get_attributes();

     if (!isset($attributes['eduPersonPrincipalName'][0])) {
          if (Config::get_config('debug')) {
               echo __FILE__ .":".__LINE__." -> eduPersonPrincipalName not set!<BR>\n";
               echo "<PRE>\n";
               print_r($attributes);
               echo "</PRE>\n";
               echo "<BR>\n";
          }
          $person->fed_auth(false);
     }
     else {
	     if (isset($attributes['mobile'][0]))
		     $person->set_mobile($attributes['mobile'][0]);
	     $person->set_name($attributes['cn'][0]);
	     $person->set_common_name($attributes['eduPersonPrincipalName'][0]);
	     $person->set_email($attributes['mail'][0]);
	     $person->set_country($attributes['country'][0]);
	     $person->fed_auth(true);
     }
} /* end add_attributes() */

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


/* compose_login_links()
 *
 * An entry-point for circumventing the fact that simplesamlphp doesn't handle
 * several different IdPs pr. default very well, and in order to try to
 * circumvent this, we tailor the login-links ourself.
 *
 * Not a perfect solution, and we're getting pretty dependent upon simplesamlphp internals.
 *
 * NOTE: in order to handle new elements in $metadata, we're unsetting this. IOW
 *-      this function has side-effects!
 */
function compose_login_links()
{
     $saml2_file = Config::get_config('saml2_path') . "/metadata/saml20-idp-remote.php";
     if (file_exists($saml2_file)) {
          unset($metadata);
          include($saml2_file);
          $protocol = "http://";
          if ($_SERVER['HTTPS'] == "on")
               $protocol = "https://";

          $server            = $protocol . $_SERVER['HTTP_HOST'];
          $saml2_server      = $server . Config::get_config('www_saml2') . "saml2/sp/";
          $relay_state        = urlencode($server . $_SERVER['HTTP_REFREER'] . "/" . $_SERVER['PHP_SELF']);
          $sso_path          = $saml2_server . "initSSO.php";
          foreach ($metadata as $key => $value) {
               $url = "$sso_path?RelayState=$relay_state&idpentityid=$key";
               echo "<A HREF=\"$url\">". $value['name']  ."</A><BR>\n";
          }
     }
     $shib13_file = Config::get_config('saml2_path') . "/metadata/shib13-idp-remote.php";
     if (file_exists($shib13_file)) {
          unset($metadata);
          include($shib13_file);
          $shib13 = $metadata;
          echo "<BR>\n<B>Shibboleth v1.3 IdPs</B><BR>\n";
          echo "<I>Note</I> - this is not implemented fully yet!<BR>\n";
          foreach ($metadata as $index => $idp) {
               echo "[ <A HREF=\"error\">".$idp['name']."</A> ]<BR>\n";
          }
     }
} /* end compose_login_links() */

/* _is_authN()
 *
 * tests to see if the user is authenticated.
 */
function _is_authN()
    {
    /* check if user is sso-auth */
    $config  = _get_config();
    $session = _get_session();
    if (isset($session) && $session->isValid())
        return true;
    return false;
    }


?>
