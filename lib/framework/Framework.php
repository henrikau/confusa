<?php

/* framework.php
 *
 * Framework class for Confusa.
 *
 * This will handle all aspects regarding layout and authentication of user.
 */
require_once 'confusa_include.php';
require_once 'Confusa_Auth.php';
require_once 'AuthHandler.php';
require_once 'NREN_Handler.php';
require_once 'NREN.php';
require_once 'Person.php';
require_once 'Logger.php';
require_once 'Content_Page.php';
require_once 'Output.php';
require_once 'CGE_ComodoCredentialException.php';
require_once 'confusa_handler.php';
require_once 'Translator.php';
require_once 'Input.php';
/* global config */
require_once 'Config.php';
require_once 'CA_Comodo.php';
require_once 'CA_Standalone.php';

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
	private static $framework;
	private static $errors = array();
	private static $messages = array();
	private static $warnings = array();
	private static $successes = array();
	private static $sensitive_action = false;

	public function __construct($contentPage) {
		if (!isset($contentPage)) {
			echo "Error! content_page not provided to Framework constructor";
			exit(0);
		}
		if (!($contentPage instanceof Content_Page)) {
			echo "Supplied contentPage is not of class Content_Page";
			exit(0);
		}
		if (!Config::get_config('valid_install')) {
			echo "You do not have a valid configuration. Please edit the confusa_config.php properly first<BR>\n";
			exit(0);
		}
		self::$framework = $this;
		/* is the connection running over SSL? */
		if (!(array_key_exists('HTTPS', $_SERVER) || array_key_exists('https', $_SERVER)) ||
		    strtolower($_SERVER['HTTPS']) != "on") {
			if (Config::get_config('debug')) {
				Framework::warning_output("WARNING: SSL is OFF.<br />".
							  " We <b>strongly</b> recommend that ".
							  "you enable SSLv3/TLS for this instance ".
							  "even though you are running in debug-mode.");
				Logger::logEvent(LOG_WARNING, "Framework", "__construct()", " Confusa is running (in debug-mode), ".
						  "and is accessible over plain HTTP.");
			} else {
				echo "Framework: HTTPS is OFF!<br />\n";
				echo "This is deemed to be a critical installation, and it debug-mode is OFF.<br /><br />\n";
				echo "Until this has been resolved, Confusa will <b>not</b> run.<br /><br />\n";
				echo "Please configure Apache to serve content over SSL, and make sure that ";
				echo "the instance is either not available over HTTP, or that it is ";
				echo "redirected to a secure connection.";
				Logger::logEvent(LOG_CRIT, "Framework", "__construct()",
				" Confusa is available via HTTP. Please configure HTTPS properly.");
				exit(0);
			}
		}

		/*
		 * language change requested, refresh the page to also localize the
		 * error-messages coming from Framework
		 */
		if (isset($_GET['lang'])) {
			$langCode = Input::sanitizeLangCode($_GET['lang']);
			setcookie("language", $langCode);
			$contentPage->getTranslator()->setLanguage($langCode);
		}

		$this->contentPage = $contentPage;

		$this->person	= new Person();
		$this->tpl	= new Smarty();
		$this->tpl->template_dir= Config::get_config('install_path').'templates';
		if (!is_dir($this->tpl->template_dir)) {
			Logger::logEvent(LOG_ALERT, "Framework", "__construct()",
			                  "Error: nonexistant templatedir: " . $this->tpl->template_dir);
			exit(0);
		}
		if (!is_dir(ConfusaConstants::$SMARTY_TEMPLATES_C) ||
		    !is_writable(ConfusaConstants::$SMARTY_TEMPLATES_C)) {
			Logger::logEvent(LOG_NOTICE, "Framework", "__construct()",
			                 "smarty template-compile-dir (" .
			                 ConfusaConstants::$SMARTY_TEMPLATES_C .
			                 ")  not writable to webserver. Please correct.");
		}
		$this->tpl->compile_dir	= ConfusaConstants::$SMARTY_TEMPLATES_C;

		$this->tpl->config_dir	= Config::get_config('install_path').'lib/smarty/configs';

		if (!is_dir(ConfusaConstants::$SMARTY_CACHE) ||
		    !is_writable(ConfusaConstants::$SMARTY_CACHE)) {
			Logger::logEvent(LOG_NOTICE, "Framework", "__construct()",
			                 "smarty template cache(" .
			                 ConfusaConstants::$SMARTY_CACHE.
			                 ")  not writable to webserver. Please correct.");
		}
		$this->tpl->cache_dir	= ConfusaConstants::$SMARTY_CACHE;

		$this->tpl->assign('title', Config::get_config('system_name').' - '.$this->contentPage->get_title());
		$this->tpl->assign('system_title', Config::get_config('system_name'));
		if (Config::get_config('maint')) {
			$this->tpl->assign('instance', Config::get_config('system_name'));
			$this->tpl->assign('maint', $this->tpl->fetch('maint.tpl'));
			$this->tpl->display('site.tpl');
			exit(0);
		}
	}

	/**
	 * @throws CGE_CriticalAttributeException If an attribute needed for the operation of Confusa is not found
	 * @throws MapNotFoundException If the NREN-map for the attributes is not found
	 */
	public function authenticate() {
		/* if login, trigger SAML-redirect first */
		$auth = AuthHandler::getAuthManager($this->person);
		$authRequired = $this->contentPage->is_protected() ||
		                (isset($_GET['start_login']) && $_GET['start_login'] === 'yes');
		$auth->authenticate($authRequired);

		/* show a warning if the person does not have Confusa
		 * entitlement and ConfusaAdmin entitlement */
		if ($this->person->isAuth()) {
			if ($this->person->testEntitlementAttribute(Config::get_config('entitlement_user')) == false) {
				if ($this->person->testEntitlementAttribute(Config::get_config('entitlement_admin')) == false) {
					$entitlement  = Config::get_config('entitlement_namespace') . ":";
					$entitlement .= Config::get_config('entitlement_user');
					$msg  = $this->contentPage->translateMessageTag('fw_error_entitlement_unset_1');
					$msg .= "<br /><i>$entitlement</i><br /><br />";
					$msg .= $this->contentPage->translateMessageTag('fw_error_entitlement_unset_2');
					if (!is_null($this->person->getSubscriber())) {
						$url  = $this->person->getSubscriber()->getHelpURL();
						$email = $this->person->getSubscriber()->getHelpEmail();

						$msg .= "<br />\n";
						$msg .= $this->contentPage->translateMessageTag('fw_error_entitlement_unset_3');
						$msg .= '<br /><ul><li style="margin: 1em 0 0 2em">';

						$msg .= $this->contentPage->translateMessageTag('fw_error_entitlement_unset_4');
						$msg .= "<a href=\"mailto:$email\">$email</a></li>";
						$msg .= '<li style="margin: 1em 0 0 2em">';
						$msg .= $this->contentPage->translateMessageTag('fw_error_entitlement_unset_5');
						$msg .= "<a href=\"$url\">$url</a></li>\n</ul><br />\n";
					}
					Framework::error_output($msg);
				} else {
					$entitlement = Config::get_config('entitlement_namespace') . ":";
					$entitlement = Config::get_config('entitlement_user');
					$msg = $this->contentPage->translateMessageTag('fw_error_entitlement_unset_1');
					$msg .= "<br /><i>$entitlement</i><br /><br />";
					$msg .= $this->contentPage->translateMessageTag('fw_error_entitlement_unset_6');

					Framework::warning_output($msg);
				}
			}
		} else {
			/* maybe we can guess the NREN from the URL */
			$this->person->setNREN(NREN_Handler::getNREN($_SERVER['SERVER_NAME']), 1);
		}

		/*
		 * Force reauthentication based on the settings if the session is too
		 * old */
		if (Framework::$sensitive_action) {
			$auth->reAuthenticate();
		}
	}

	/**
	 * sensitive_action() - make sure that the user is recently AuthN
	 *
	 * Some actions are more sensitive than others. This function will
	 * notify the framework that the user should be AuthN recently. The
	 * limit is configurable.
	 *
	 * @param	void
	 * @return	void
	 * @access	public
	 * @static
	 */
	public static function sensitive_action()
	{
		Framework::$sensitive_action = true;
	}

	public function start()
	{
		/* From OWASP (prevent clickjacking):
		 *
		 * This new (nonstandard) X-FRAME-OPTIONS header is used to mark
		 * responses that shouldn't be framed. There are two options with
		 * X-FRAME-OPTIONS. The first is DENY, which prevents everyone from
		 * framing the content.
		 *
		 * This can also be done by apache itself:
		 * a2enmod headers
		 * Add to the Virtualhost, directory that hosts confusa:
		 * Header set X-Frame-Options "DENY"
		 */
		header('X-Frame-Options: DENY');
		/*
		 * Strict-Transport-Security (RFC 6797)
		 * Once page has been accessed over HTTPS and this header was present,
		 * confirmant browsers will force subsequent requests over HTTPS aswell.
		 */
		header('Strict-Transport-Security: max-age=31536000');

		/* Set tpl object to content page */
		$this->contentPage->setTpl($this->tpl);

		/* check the authentication-thing, catch the login-hook
		 * This is done via confusa_auth
		 */
		try {
			$this->authenticate();
		} catch (CGE_CriticalAttributeException $cae) {
			$msg  = "<b>" . $this->contentPage->translateMessageTag('fw_error_critical_attribute1') . "</b><br /><br />";
			$msg .= htmlentities($cae->getMessage()) . "<br /><br />";
			$msg .= $this->contentPage->translateMessageTag('fw_error_critical_attribute2');
			Framework::error_output($msg);
			$this->renderError = true;
		} catch (MapNotFoundException $mnfe) {
			$msg = $this->contentPage->translateMessageTag('fw_error_map_notfound');

			/* if user is admin */
			if ($this->person->isNRENAdmin()) {
				$msg .= "<br /><br />";
				$msg .= "<a href=\"attributes.php?mode=admin&anticsrf=".Framework::getAntiCSRF()."\">";
				$msg .= $this->contentPage->translateMessageTag('fw_error_map_updatemap');
				$msg .= "</>\n";
			}
			Framework::error_output($msg);
			$this->renderError = true;
		} catch (ConfusaGenException $cge) {
			Framework::error_output($this->contentPage->translateMessageTag('fw_error_auth') .
			                         htmlentities($cge->getMessage()));
			$this->renderError = true;
		}

		if ($this->isCSRFAttempt()) {
			Framework::error_output($this->contentPage->translateMessageTag('fw_anticsrf_msg'));
			$this->tpl->assign('instance', Config::get_config('system_name'));
			$this->tpl->assign('errors', self::$errors);
			$this->tpl->display('site.tpl');
			exit(0);
		}

		/* Create a new anti CSRF token and export to the template engine */
		$this->current_anticsrf = self::getAntiCSRF();
		$this->tpl->assign('ganticsrf', 'anticsrf='.$this->current_anticsrf);
		$this->tpl->assign('panticsrf',
				   '<input type="hidden" name="anticsrf" value="'.
				   $this->current_anticsrf.'" />');
		/*
		 * Try to run the pre-processing
		 */
		try {
			$res = $this->contentPage->pre_process($this->person);
			if ($res) {
				$this->tpl->assign('extraHeader');
			}
		} catch (CGE_RemoteCredentialException $rce) {
			$msg  = $this->contentPage->translateMessageTag('fw_error_remote_credential1');
			$msg .= "<i>". htmlentities($rce->getMessage()) . "</i><br /><br />";

			if ($this->person->isNRENAdmin()) {
				$msg .=  "<div style=\"text-align: center\">";
				$msg .= self::translateMessageTag('fw_error_remote_credential2') . "</div>";
			} else {
				$msg .= Framework::error_output($this->contentPage->translateMessageTag('fw_error_remote_credential3'));
				$this->renderError = true;
			}
			Framework::warning_output($msg);
		} catch (KeyNotFoundException $knfe) {
				$this->renderError = true;

				$errorTag = PW::create(8);
				$msg  = "[$errorTag] " .
				        $this->contentPage->translateMessageTag('fw_keynotfound1');
				Logger::logEvent(LOG_NOTICE, "Framework", "start()",
				                 "Config-file not properly configured: " . $knfe->getMessage(),
				                 __LINE__, $errorTag);

				$msg .= htmlentities($knfe->getMessage());
				$msg .= "<br />" . $this->contentPage->translateMessageTag('fw_keynotfound2');
				Framework::error_output($msg);
		} catch (Exception $e) {
			Framework::error_output($this->contentPage->translateMessageTag('fw_unhandledexp1') .
			                        "<br />" . htmlentities($e->getMessage()));
			$this->renderError = true;
		}

		/* ----------------------------------------------------------------
		 * Admin messages, trigger on missing elements
		 */
		if ($this->person->isNRENAdmin()) {
			$this->triggerAdminIssues();
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

		$this->tpl->assign('title_logo', $this->contentPage->translateMessageTag('l10n_title_logo'));
		$this->tpl->assign('person',	$this->person);
		$this->tpl->assign('subscriber',$this->person->getSubscriber());
		$this->tpl->assign('nren',	$this->person->getNREN());
		$this->tpl->assign('is_online', (Config::get_config('ca_mode') === CA_COMODO));

		/* If we have a renderError, do not allow the user-page to
		 * render, otherwise, run it, and catch all unhandled exception
		 *
		 * The general idea, is that the process() should be
		 * self-contained wrt to exceptions.
		 *
		 * A NREN admin is supposed to be able to "fix stuff" such as for instance
		 * CGE_CriticalAttributeExceptions and should hence see the pages also if
		 * renderError is set.
		 */
		if (!$this->renderError || $this->person->isNRENAdmin()) {
			try {
				$this->applyNRENBranding();
				$this->contentPage->process($this->person);
			} catch (KeyNotFoundException $knfe) {
				$errorTag = PW::create(8);
				$msg  = "[$errorTag] " .
				        $this->contentPage->translateMessageTag('fw_keynotfound1');
				Logger::logEvent(LOG_NOTICE, "Framework", "start()",
				                 "Config-file not properly configured: " . $knfe->getMessage(),
				                 __LINE__, $errorTag);
				$msg .= htmlentities($knfe->getMessage());
				$msg .= "<br />" . $this->contentPage->translateMessageTag('fw_keynotfound2');
				Framework::error_output($msg);
			} catch (Exception $e) {
				Logger::logEvent(LOG_INFO, "Framework", "start()",
				                 "Unhandleded exception when running contentPage->process()",
				                 __LINE__);
				Framework::error_output($this->contentPage->translateMessageTag('fw_unhandledexp1')
				                        . "<br />\n" . htmlentities($e->getMessage()));
			}
		} else {
			$nren = $this->person->getNREN();

			if (isset($nren)) {
				/* if all else fails, at least give the user some recovery information */
				Framework::message_output($this->contentPage->translateMessageTag('fw_unrecoverable_nren') .
				                          htmlentities($this->person->getEPPN()));
			} else {
				$errorTag = PW::create();
				Framework::error_output("[$errorTag] " .
				                        $this->contentPage->translateMessageTag('fw_unrecoverable_nonren'));
				Logger::logEvent(LOG_WARNING, "Framework", "start()",
				                 "User contacting us from " . $_SERVER['REMOTE_ADDR'] .
				                 " tried to login from IdP that appears to have no NREN-mapping!",
				                 __LINE__, $errorTag);
			}
		}
		$this->tpl->assign('logoutUrl', 'logout.php');
		// see render_menu($this->person)
		$this->tpl->assign('menu', $this->tpl->fetch('menu.tpl'));
		$this->tpl->assign('errors', self::$errors);
		$this->tpl->assign('messages', self::$messages);
		$this->tpl->assign('successes', self::$successes);
		$this->tpl->assign('warnings', self::$warnings);

		if (Config::get_config('debug')) {
			$db_debug_res = "";
			$db_debug_res .= "<address>\n";
			$db_debug_res .= "During this session, we had ";
			$db_debug_res .= MDB2Wrapper::getConnCounter() . " individual DB-connections.<br />\n";
			$db_debug_res .= "</address>\n";
			$this->tpl->assign('db_debug', $db_debug_res);
		}
		$this->tpl->display('site.tpl');

		if (!$this->renderError) {
			$this->contentPage->post_process($this->person);
		}
	} /* end start() */

	/**
	 * Assign NREN help and about texts, plus the privacy notice.
	 * Apply NREN look and feel, like CSS, custom logos and portal title
	 */
	private function applyNRENBranding()
	{
		$nren = $this->person->getNREN();

		/* can not brand the portal without an NREN */
		if (empty($nren)) {
			return;
		}

		/* apply the logos */
		$logo_path = Config::get_config('custom_logo') . $nren . "/custom_";

		foreach(ConfusaConstants::$ALLOWED_LOGO_POSITIONS as $pos) {
			foreach (ConfusaConstants::$ALLOWED_IMG_SUFFIXES as $sfx) {
				$logo_file = $logo_path . $pos . "." . $sfx;
				if (file_exists($logo_file)) {
					$imgurl = "view_logo.php?nren=$nren&amp;pos=$pos&amp;suffix=$sfx";
					$this->tpl->assign("logo_$pos", $imgurl);
					break;
				}
			}
		}

		/* apply the CSS */
		$css = "get_css.php?nren=" . $nren;
		$this->tpl->assign('css',$css);

		/* apply the custom title on the portal */
		if ($nren->getShowPortalTitle()) {
			$customPortalTitle = $nren->getCustomPortalTitle();

			if (isset($customPortalTitle)) {
				$this->tpl->assign('system_title', '&nbsp;' .  $customPortalTitle);
			} else {
				$this->tpl->assign('system_title', '&nbsp;' .  Config::get_config('system_title'));
			}
		} else {
			$this->tpl->assign('system_title', '');
		}
	} /* end applyNRENBranding */

	/**
	 * isCSRFAttempt() - test to see if the current connection is an CSRF
	 *
	 * If a GET or POST is set, the client tries to send data to the
	 * portal, and we must make sure that the user is not being fooled by a
	 * malicious site.
	 *
	 * @param	void
	 * @return	boolean status indicating if a detectable
	 * @access	private
	 */
	private function isCSRFAttempt()
	{
		if (!empty($_GET) || !empty($_POST)) {
			$facsrft = null;

			if (isset($_GET) && array_key_exists('anticsrf', $_GET)) {
				$facsrft = Input::sanitizeAntiCSRFToken($_GET['anticsrf']);
			} else if (isset($_POST) && array_key_exists('anticsrf', $_POST)) {
				$facsrft = Input::sanitizeAntiCSRFToken($_POST['anticsrf']);
			} else if (isset($_GET) && array_key_exists('return', $_GET)) {
				/* handle SSP redirect to idp_select */
				$res = self::getCSRFRandToken(urldecode(urldecode($_GET['return'])));
				Logger::log_event(LOG_WARNING, "Handling SSP redirect - ".$res['csrf'] . "\n");
				if (is_array($res)) {
					$facsrft = $res['full'];
				}
			}
			if (!self::validateACSRFT($facsrft)) {
				$msg =  "Got a GET/POST request without the correct anticsrf tag.";
				$msg .= "Supplied: $facsrft (res: ".$res['full'] . ")";
				if (array_key_exists('HTTP_REFERER', $_SERVER)) {
					$msg .= " Referer was " . $_SERVER['HTTP_REFERER'];
				}
				Logger::log_event(LOG_WARNING, "[Anti CSRF] $msg");
				return true;
			}
		}
		/* no detectable CSRF attempt */
		return false;
	} /* end CSRFAttempt() */

	/**
	 * triggerAdminIssues() - post error-messages
	 *
	 * Function will report issues in the form of errors to the screen when an
	 * admin looks at the portal and something is amiss. This includes:
	 *
	 * - missing privacy notice for the NREN
	 * - missing about_nren text
	 * - incomplete or missing attribute-map
	 *
	 * @param	void
	 * @return	void
	 * @access	private
	 */
	private function triggerAdminIssues()
	{
		if (!isset($this->person))
			return;
		$nren = $this->person->getNREN();
		if (!$nren)
			return;

		$url_arg = "?mode=admin&amp;show=text&amp;anticsrf=".Framework::getAntiCSRF();

		if (!$nren->hasHelpText()) {
			Framework::warning_output("Missing NREN help-text. <a href=\"stylist.php" .
									  $url_arg . "#edit_help\">Configure</a>");
		}

		if (!$nren->hasAboutText()) {
			Framework::warning_output("Missing About-NREN text. <a href=\"stylist.php".
									  $url_arg . "#edit_about\">Configure</a>");
		}

		if (!$nren->hasPrivacyNotice()) {
			Framework::warning_output("Missing privacy-notice. <a href=\"stylist.php" .
									  $url_arg . "#edit_pn\">Configure</a>");
		}
	} /* end triggerAdminIssues() */

	/**
	 * Register new error-message to display in the page
	 *
	 * This will post a message in the top of the general content-area
	 * surrounded with a red (if the CSS is unaltered) box to draw
	 * attention.
	 *
	 * @param	String $message the error to display
	 * @return	void
	 * @access	public
	 * @
	 */
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

	/**
	 * getAntiCSRF() return a per-session unique identifier
	 *
	 * We could introduce the session_id, but to aovid session-hijacking we
	 * do not want to include this into all the form-requests in Confusa.
	 *
	 * Instead we use a second-order identifier derived from the session
	 * along with extra randomness and the current time. This is then
	 * concatenated and fed through a one-way function (to proect the
	 * session_id).
	 *
	 * Why not rely upon HTTP_REFERER as a test?
	 * - *nothing* from a browser (with respect to anti CSRF) can be trusted.
	 * - it is easy to fake a HTTP_REFERER value by explicitly setting the
	 *   headers sent.
	 * - browsers are vulnerable, exploits exists
	 *
	 * @param	String $rand	A random seed. If not supplied, a random
	 *				value is generated.
	 * @return	String		The Anti CSRF token.
	 * @access	static
	 */
	public static function getAntiCSRF($rand = null)
	{
		if (is_null($rand)) {
			$rand = rand(0, PHP_INT_MAX);
		}
		/* make sure rand only contains allowed characters. */
		$rand = Input::sanitizeAntiCSRFToken($rand);
		return $rand.":".sha1(session_id().$rand);
	} /* end getAntiCSRF() */

	private static function getCSRFRandToken($token)
	{
		$token = substr($token, strpos($token, 'anticsrf') + strlen('anticsrf') + 1 );
		$pos = strrpos($token, ":");
		if ($pos===false) {
			/* echo $token;  */
			return false;
		}
		return array('rand' => substr($token, 0, $pos),
					 'csrf' => substr($token, $pos +1),
					 'full' => $token);
	}

	/**
	 * validateACSRFT() validate a supplied token
	 *
	 * The function takes an arbitrary token and tries to generate a
	 * matching token from the salt. If successful, the token is valid.
	 *
	 * @param	String	$token	The token to validate
	 * @return	Boolean		True if the token is valid
	 * @access	static
	 */
	public static function validateACSRFT($token)
	{
		if (is_null($token) || $token == "") {
			return false;
		}
		$pos = strrpos($token, ":");
		if (!$pos) {
			throw new ConfusaGenException("Malformed Anti CSRF token, could not determine placement of delimiter.");
		}
		return $token === self::getAntiCSRF(substr($token, 0, $pos));
	} /* end validateACSRFT() */

	public static function get()
	{
		if (!is_null(self::$framework))
			return self::$framework;
		return null;
	}

} /* end class Framework */

?>