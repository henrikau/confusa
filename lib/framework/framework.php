<?php

/* framework.php
 *
 * Framework class for Confusa.
 *
 * This will handle all aspects regarding layout and authentication of user.
 */
require_once 'confusa_include.php';
require_once 'confusa_auth.php';
require_once 'person.php';
require_once 'logger.php';
require_once 'content_page.php';
require_once 'output.php';

/* global config */
require_once 'config.php';
require_once 'cert_manager_online.php';
require_once 'cert_manager_standalone.php';

try {
	require_once Config::get_config('smarty_path') . 'Smarty.class.php';
} catch (KeyNotFoundException $knfe) {
	die("Cannot load smarty, smarty_path not set!");
}

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
	private $tpl;
	private $renderError = false;
	private static $errors = array();
	private static $messages = array();
	private static $warnings = array();
	private static $successes = array();
	private static $sensitive_action = false;

	/* Limit the file endings that are going to be accepted.
	 * There can be images with embedded comments. As the comments can
	 * contain PHP code, allowing files with suffix .php is dangerous,
	 * even when a check for the file mime-type has already been made.
	 * Classical injection scenario.
	 */
	public static $allowed_img_suffixes = array('png','jpg','gif');

	public function __construct($contentPage) {
		if (!isset($contentPage)) {
			Framework::error_output("Error! content_page not provided to Framework constructor");
			exit(0);
		}
		if (!($contentPage instanceof Content_Page)) {
			Framework::error_output("Supplied contentPage is not of class Content_Page");
			exit(0);
		}
		if (!Config::get_config('valid_install')) {
			echo "You do not have a valid configuration. Please edit the confusa_config.php properly first<BR>\n";
			exit(0);
		}
		$this->contentPage = $contentPage;
		$this->person	= new Person();
		$this->tpl	= new Smarty();
		$this->tpl->template_dir= Config::get_config('install_path').'lib/smarty/templates';
		$this->tpl->compile_dir	= Config::get_config('install_path').'lib/smarty/templates_c';
		$this->tpl->config_dir	= Config::get_config('install_path').'lib/smarty/configs';
		$this->tpl->cache_dir	= Config::get_config('install_path').'lib/smarty/cache';
		$this->tpl->assign('title', Config::get_config('system_name').' - '.$this->contentPage->get_title());
		if (Config::get_config('maint')) {
			$this->tpl->assign('instance', Config::get_config('system_name'));
			$this->tpl->assign('maint', $this->tpl->fetch('maint.tpl'));
			$this->tpl->display('site.tpl');
			exit(0);
		}
	}

	public function authenticate() {
		/* if login, trigger SAML-redirect first */
		$auth = AuthHandler::getAuthManager($this->person);

		try {
			if (!$auth->checkAuthentication()) {
				if ($this->contentPage->is_protected() || (isset($_GET['start_login']) && $_GET['start_login'] === 'yes')) {
					$auth->authenticateUser();
				}
			}
		}
		catch (ConfusaGenException $cge) {
			Framework::error_output($cge->getMessage());
			$this->renderError = true;
			return;
		}
		/* get the updated person object back from the authentication framework */
		$this->person = $auth->getPerson();
		/* show a warning if the person does not have Confusa entitlement and ConfusaAdmin entitlement */
		if ($this->person->isAuth()) {
			if ($this->person->testEntitlementAttribute("Confusa") == false) {
				if ($this->person->testEntitlementAttribute("ConfusaAdmin") == false) {
					Framework::message_output("'Confusa' Entitlement not set. You do not qualify " .
								"to request certificates at this time. Please ask an IT-administrator at your " .
								"institution to resolve this issue.");
				}
			}
		}

		if (Framework::$sensitive_action) {
			$delta = Config::get_config('protected_session_timeout')*60 - $this->person->getTimeSinceStart();
			if ($delta < 0) {
				$path = $_SERVER['SCRIPT_NAME'];
				$parts = explode('/', $path);
				$file = $parts[count($parts) - 1];
				$auth->deAuthenticateUser($file);

				require_once 'refresh.html';
				$msg =  __FILE__ . ":" . __LINE__ . " Sensitive action, and your session is too old (";
				$msg .= ((int)$delta*-1)." seconds passed the limit) ";
				$msg .= "--- the re-auth has not been implemented yet.";
				Logger::log_event(LOG_NOTICE,$msg);
				exit(0);
			}
		}
	}

	/**
	 * sensitive_action() - make sure that the user is recently AuthN
	 *
	 * Some actions are more sensitive than others. This function will
	 * notify the framework that the user should be AuthN recently. The
	 * limit is configurable.
	 */
	public static function sensitive_action()
	{
		Framework::$sensitive_action = true;
	}

	public function start()
	{
		/* Set tpl object to content page */
		$this->contentPage->setTpl($this->tpl);

		/* check the authentication-thing, catch the login-hook
		 * This is done via confusa_auth
		 */
		try {
			$this->authenticate();
			$res = $this->contentPage->pre_process($this->person);
			if ($res) {
				$this->tpl->assign('extraHeader', $res);
			}
		} catch (CriticalAttributeException $cae) {
			$msg  = "<center>";
			$msg .= "<b>Error(s) with attributes</b><br /><br />";
			$msg .= $cae->getMessage() . "<br /><br />";
			$msg .= "<b>Cannot continue</b><br /><br />";
			$msg .= "Please contact your local IT-support, and ask them to resolve this issue.";
			$msg .= "</center>";
			Framework::error_output($msg);
			$this->renderError = true;
		} catch (MapNotFoundException $mnfe) {
			$msg  = "<center>\n";
			$msg .= "<b>Error(s) with attributes</b><br /><br />";
			$msg .= "No map has been configured for your subscriber. ";
			$msg .= "Please contact your local IT-departement and ask them to forward the request ";
			$msg .= "to the registred NREN administrator for your domain.";
			Framework::error_output($msg);
			$this->renderError = true;
		} catch (ConfusaGenException $cge) {
			Framework::error_output("Could not authenticate you! Error was: " .
									$cge->getMessage());
			$this->renderError = true;
		} catch (Exception $e) {
			Framework::error_output("Uncaught exception occured!<br />\n" . $e->getMessage());
			$this->renderError = true;
		}

		/* Mode-hook, to catch mode-change regardless of target-page (not only
		 * index) */
		if (isset($_GET['mode'])) {
			$new_mode = NORMAL_MODE;
			if (htmlentities($_GET['mode']) == 'admin') {
				$new_mode = ADMIN_MODE;
			}
			$this->person->setMode($new_mode);
		}

		$this->tpl->assign('person', $this->person);
		$this->tpl->assign('is_online', (Config::get_config('ca_mode') === CA_ONLINE));
		/* If we have a renderError, do not allow the user-page to
		 * render, otherwise, run it, and catch all unhandled exception
		 *
		 * The general idea, is that the process() should be
		 * self-contained wrt to exceptions.
		 */
		if (!$this->renderError) {
			try {
				$this->contentPage->process($this->person);
			} catch (Exception $e) {
				Framework::error_output("Unhandled exception found in user-function!<br />\n" . $e->getMessage());
			}
		}
		$this->tpl->assign('logoutUrl', 'logout.php');
		$this->tpl->assign('menu', $this->tpl->fetch('menu.tpl')); // see render_menu($this->person)
		$this->tpl->assign('errors', self::$errors);
		$this->tpl->assign('messages', self::$messages);
		$this->tpl->assign('successes', self::$successes);
		$this->tpl->assign('warnings', self::$warnings);

		/* get custom logo if there is any */
		$logo = Framework::get_logo_for_nren($this->person->getNREN());
		$css = Framework::get_css_for_nren($this->person->getNREN());
		$this->tpl->assign('logo', $logo);
		$this->tpl->assign('css',$css);
		$this->tpl->display('site.tpl');
		

		$this->contentPage->post_process($this->person);
		if (Config::get_config('debug')) {
			echo "<center>\n";
			echo "<address>\n";
			echo "During this session, we had " . MDB2Wrapper::getConnCounter() . " individual DB-connections.<br />\n";
			echo "</address>\n";
			echo "</center>\n";
		}
	} /* end start() */

	public static function error_output($message)
	{
		self::$errors[] = $message;
	}
	public static function message_output($message)
	{
		self::$messages[] = $message;
	}

	public static function success_output($message)
	{
		self::$successes[] = $message;
	}

	public static function warning_output($message)
	{
		self::$warnings[] = $message;
	}

	/*
	 * Check if there is a custom logo for a certain NREN and if there is,
	 * return its URL.
	 *
	 * @param NREN the name of the NREN of which the logo should be retrieved
	 * @return $url: The URL of the logo
	 *
	 * 			NULL if the logo does not exist
	 */
	public static function get_logo_for_nren($nren)
	{
		$logo_path = Config::get_config('install_path') . 'www/';
		$logo_path .= Config::get_config('custom_logo') . '/' . $nren . '/custom.';

		$logo_suffix = "";

		/*
		 * Search if there is one custom.png, custom.jpg or custom.any_other_
		 * allowed_suffix file in the custom-logo folder.
		 *
		 * If there isn't return null
		 */
		foreach(Framework::$allowed_img_suffixes as $suffix) {
			if (file_exists($logo_path . $suffix)) {
				$logo_suffix = $suffix;
				break;
			}
		}

		if (empty($logo_suffix)) {
			return NULL;
		}

		$image = $logo_path . $logo_suffix;

		$logo_url = Config::get_config('custom_logo');
		$logo_url .= $nren . '/custom.' . $logo_suffix;

		return $logo_url;
	}

	/*
	 * Check if a custom CSS file for a certain NREN exists and return it if it
	 * does.
	 *
	 * @param $nren The name of the NREN for which to retrieve the custom CSS
	 * @return The custom CSS file for the respective NREN
	 */
	public static function get_css_for_nren($nren)
	{
		$css_path = Config::get_config('install_path') . 'www/';
		$css_path .= Config::get_config('custom_css') . $nren . '/custom.css';

		if (!file_exists($css_path)) {
			return NULL;
		}

		$css_url =  Config::get_config('custom_css');
		$css_url .= $nren . '/custom.css';

		return $css_url;
	}

	
} /* end class Framewokr */
