<?php

  /* framework.php
   *
   * Framework class for Confusa.
   *
   * This will handle all aspects regarding layout and authentication of user.
   */
require_once('confusa_include.php');
require_once('confusa_auth.php');
require_once('menu.php');
require_once('person.php');
require_once('logger.php');

/* global config */
require_once('confusa_config.php');

/* class Framework
 *
 * This class provides the framework for Confusa
 * To use this, simply create an instance, and pass along your function that you
 * want to render the content-page with.
 * The object will then check for login (or you can force it to login) create
 * menu and finally, include the content of your page.
 * 
 * All pages that wants to use the confusa functionality, must call
 * render_page, and pass along a function-pointer which renders the content of
 * the page. (see index.php for an example).
 */
class Framework {
    private $f_content;         /* pointer to content-rendering function */
    private $flogin;            /* force login of user (i.e. this page is
                                 * *never* available for unauthenticated users */
    private $person;
    private $config;

    public function __construct($content_page) {
	    global $confusa_config;
	    $this->config = $confusa_config;
      /* check for config-file */
      if ( !isset($this->config) || !$this->config['valid_install']) {
	echo "confusa_config is not set! You must edit this file first and validate it.<BR>\n";
	echo "You should have a look at confusa_config.php.template. <BR>\n";
	exit(0);
      }

      $this->f_content = $content_page;
      $this->flogin = false;
      $this->person = new Person();
    }

    public function force_login() {
      $this->flogin = true;
    }


    public function render_page() {
        /* check the authentication-thing, catch the login-hook
         * This is done via confusa_auth
         */
        is_authenticated($this->person);
        /* if login, trigger SAML-redirect first */
        if ($this->flogin || (isset($_GET['start_login']) && $_GET['start_login'] === 'yes')) {
             _assert_sso($this->person);
	}
        $uname = "anonymous";
        if($this->person->is_auth())
             $uname = $this->person->get_common_name();

	Logger::log_event(LOG_INFO, "displaying " . $this->f_content . " to user " . $uname . " connecting from " . $_SERVER['REMOTE_ADDR']);
        require_once('header.php');
        echo "\n<TABLE class=\"main\">\n";
        echo "\t<TR>\n";

        /* include the menu, the menu will itself sort out what to display
         * according to begin logged in or not */
        echo "\t\t<TD class=\"main\" WIDTH=\"100\" VALIGN=\"TOP\">\n";
        render_menu($this->person->is_auth());
        echo "\t\t</TD>\n";

        /* include content of page with login if set*/
        echo "\t\t<TD class=\"main\">\n";
        $this->user_rendering();
        echo "\t\t</TD>\n";


        echo "</TABLE>\n";
        echo "<BR><ADDRESS>Last updated: " . $this->get_last_mod() . "</ADDRESS><BR>\n";
        include_once('footer.php');
        } /* end render_page */

    private function user_rendering()
        {
        /* check to see if the user wants to log in, if so, start login-procedure */
            if (!$this->person->is_auth()) {
                if ($this->force_login || (isset($_GET['start_login']) && $_GET['start_login'] === 'yes')) {
                    authenticate_user($this->person);
                    }
            else if (isset($_POST['new_pw']))
                reset_sms_password($this->person);

            if (isset($_POST['start_login']) && $_POST['start_login'] == 'yes')
                authenticate_user($this->person);
            }
            $func = $this->f_content;
            $func($this->person);
        } /* end user-rendering */

    private function get_last_mod()
        {
        return "14. May 2008";
        }
    } /* end class Framewokr */
