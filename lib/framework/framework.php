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
require_once('content_page.php');
require_once('output.php');
require_once('header.php');

/* global config */
require_once('config.php');
require_once('cert_manager_online.php');
require_once('cert_manager_standalone.php');

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
	private $person;
	private $contentPage;

    public function __construct($contentPage) {
	    if (!isset($contentPage)) {
		    error_output("Error! content_page not provided to Framework constructor");
		    exit(0);
	    }
	    if (!($contentPage instanceof ContentPage)) {
		    error_output("Supplied contentPage is not of class ContentPage");
		    exit(0);
	    }
	    if (!Config::get_config('valid_install')) {
		    echo "You do not have a valid configuration. Please edit the confusa_config.php properly first<BR>\n";
		    exit(0);
	    }
	    $this->contentPage = $contentPage;
	    $this->person = new Person();
	    set_title($this->contentPage->get_title());
    }

    public function authenticate() {
        is_authenticated($this->person);
        if (!$this->person->is_auth()) {
		/* if login, trigger SAML-redirect first */
		if ($this->contentPage->is_protected() || (isset($_GET['start_login']) && $_GET['start_login'] === 'yes')) {
			_assert_sso($this->person);
		}
        }
	$uname = "anonymous";
	if($this->person->is_auth())
		$uname = $this->person->get_valid_cn();
        return $this->person;
    }

   public function start()
   {
	   /* check the authentication-thing, catch the login-hook
	    * This is done via confusa_auth
	    */
	   $this->authenticate();

	   /* Allow content-page to do pre-process */
	   $res = $this->contentPage->pre_process($this->person);
	   if ($res) {
		   add_header($res);
	   }
	   show_headers();
	   echo "<BODY>\n";
	   echo "<CENTER>\n";
	   echo "<TABLE class=\"header\">\n";
	   echo "<TR>\n";
	   echo "<TD ><IMG SRC=\"graphics/logo-sigma.png\"></TD>\n";
	   echo "<TD WIDTH=\"100\"></TD>\n";
	   echo "<TD><FONT SIZE=\"+2\">Confusa</TD>\n";
	   echo "</TR>\n";
	   echo "</TABLE>\n";
	   echo "</CENTER>\n";

	 /* Mode-hook, to catch mode-change regardless of target-page (not only
	  * index) */
	 if (isset($_GET['mode'])) {
		 $new_mode = NORMAL_MODE;
		 if (htmlentities($_GET['mode']) == 'admin') {
			 $new_mode = ADMIN_MODE;
		 }
		 $this->person->set_mode($new_mode);
	 }

	
        echo "\n<TABLE class=\"main\">\n";
        echo "\t<TR>\n";

        /* === MENU === */
        echo "\t\t<TD class=\"main\" WIDTH=\"100\" VALIGN=\"TOP\">\n";
        render_menu($this->person);
        echo "\t\t</TD>\n";

	/* === Main === */
        echo "\t\t<TD class=\"main\">\n";
	echo "\t\t<TABLE class=\"content\">\n";
	echo "\t\t\t<TR><TD>\n";
	$this->contentPage->process($this->person);
	echo "\t\t\t</TD></TR>\n";
	echo "\t\t</TABLE>\n";
        echo "\t\t</TD>\n";

	/* === Post-amble */
        echo "</TABLE>\n";
        include_once('footer.php');


	$this->contentPage->post_render($this->person);
        } /* end render_page */

    private function user_rendering()
        {
        /* check to see if the user wants to log in, if so, start login-procedure */
            if (!$this->person->is_auth()) {
                if ($this->flogin || (isset($_GET['start_login']) && $_GET['start_login'] === 'yes')) {
                    authenticate_user($this->person);
                    }
            if (isset($_POST['start_login']) && $_POST['start_login'] == 'yes')
                authenticate_user($this->person);
            }
            $func = $this->f_content;
            $func($this->person);
        } /* end user-rendering */

    } /* end class Framewokr */
