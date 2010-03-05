<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'MDB2Wrapper.php';
require_once 'input.php';
require_once 'file_io.php';
require_once 'file_upload.php';
require_once 'logger.php';
require_once 'classTextile.php';
require_once 'confusa_constants.php';

class CP_Stylist extends Content_Page
{
	/* allowed smarty tags for the notification mail template */
	private $NOTIFICATION_MAIL_TAGS = array('subscriber',
	                                        'product_name',
	                                        'confusa_url',
	                                        'dn',
	                                        'download_url',
	                                        'subscriber_support_email',
	                                        'subscriber_support_url',
	                                        'order_number',
	                                        'issue_date',
	                                        'ip_address',
	                                        'nren');
	/* allowed smarty tags for the NREN about and help texts */
	private $NREN_TEXTS_TAGS = array('subscriber',
	                                 'product_name',
	                                 'confusa_url',
	                                 'subscriber_support_email',
	                                 'subscriber_support_url');

	function __construct() {
		parent::__construct("Stylist", true, 'stylist');
	}

	/*
	 * Dispatcher for non-visual operations like e.g. writing changes to
	 * somewhere or updating values.
	 */
	public function pre_process($person)
	{
		parent::pre_process($person);

		/* if $person is not a NREN admin we stop here */
		if (!$this->person->isNRENAdmin()) {
			return false;
		}

		if (isset($_POST['stylist_operation'])) {
			switch(htmlentities($_POST['stylist_operation'])) {
			case 'change_help_text':
				$new_text = Input::sanitizeText($_POST['help_text']);
				$this->updateNRENHelpText($this->person->getNREN(), $new_text);
				break;
			case 'change_about_text':
				$new_text = Input::sanitizeText($_POST['about_text']);
				$this->updateNRENAboutText($this->person->getNREN(), $new_text);
				break;
			case 'change_privnotice_text':
				$new_text = Input::sanitizeText($_POST['privnotice_text']);
				$this->updateNRENPrivacyNotice($this->person->getNREN(), $new_text);
				break;
			case 'change_css':
				if (isset($_POST['reset'])) {
					$this->resetNRENCSS($this->person->getNREN());
				} else if (isset($_POST['download'])) {
					$new_css = Input::sanitizeCSS($_POST['css_content']);
					$this->downloadNRENCSS($new_css);
				} else if (isset($_POST['change'])) {
					/* the CSS will not be inserted into the DB or executed in another way.
					* Hence do not sanitize it. It will contain 'dangerous' string portions,
					* such as { : ' anyways, so it would be hard to insert it into the DB properly*/
					$new_css = Input::sanitizeCSS($_POST['css_content']);
					$this->updateNRENCSS($this->person->getNREN(), $new_css);
				}
				break;
			case 'change_mail':
				if (isset($_POST['reset'])) {
					$this->resetNRENMailTpl($this->person->getNREN());
				} else if (isset($_POST['change'])) {
					$new_template = strip_tags($_POST['mail_content']);
					$this->updateNRENMailTpl($this->person->getNREN(),
					                         $new_template);
				} else if (isset($_POST['test'])) {
					/* see where mail_content is set in
					 * process() for how the current
					 * template is kept. */
					$this->sendNRENTestMail($this->person, strip_tags($_POST['mail_content']));
				}
				break;
			case 'upload_logo':
				$position = $_POST['position'];
				if (array_search($position, ConfusaConstants::$ALLOWED_LOGO_POSITIONS) === FALSE) {
					Framework::error_output("The specified position " .
					                        htmlentities($position) .
					                        " is not a legal logo position!");
					return;
				}

				if (isset($_FILES['nren_logo']['name'])) {
					/* only allow image uploads */
					if (strpos($_FILES['nren_logo']['type'], 'image/') !== false) {
						$this->uploadLogo('nren_logo', $position, $this->person->getNREN());
					}
				}
				break;
			case 'delete_logo':
				$position = $_POST['position'];
				if (array_search($position, ConfusaConstants::$ALLOWED_LOGO_POSITIONS) === FALSE) {
					Framework::error_output("The specified position " .
					                        htmlentities($position) .
					                        " is not a legal logo position!");
					return;
				}

				$this->deleteLogo($position, $this->person->getNREN());
				break;
			case 'change_title':
				if (isset($_POST['portalTitle'])) {
					$titleValue = Input::sanitize($_POST['portalTitle']);
				} else {
					$titleValue = "";
				}

				if (isset($_POST['changeButton'])) {
					$showTitle = isset($_POST['showPortalTitle']);
					$this->updateNRENTitle($this->person->getNREN(),
					                       $titleValue,
					                       $showTitle);
				}
				break;
			default:
				Framework::error_output("Unknown operation chosen in the stylist!");
				break;
			}
		}
	}

	/*
	 * Dispatcher for visual operations, e.g. displaying a mask
	 */
	public function process()
	{
		if (!$this->person->isNRENAdmin()) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->getX509ValidCN() . " tried to access the NREN-area");
			$this->tpl->assign('reason', 'You are not an NREN-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}

		if (isset($_GET['show'])) {
			switch(htmlentities($_GET['show'])) {
			case 'text':
				$texts = $this->getNRENTexts($this->person->getNREN());

				if ($texts != NULL) {
					$this->tpl->assign('help_text', $texts[0]);
					$this->tpl->assign('about_text', $texts[1]);
					$this->tpl->assign('privnotice_text', $texts[2]);
				}

				$this->tpl->assign('tags', $this->NREN_TEXTS_TAGS);
				$this->tpl->assign('edit_help_text', true);
				break;
			case 'css':
				$this->tpl->assign('edit_css', true);
				$css_string = $this->fetchNRENCSS($this->person->getNREN());

				if (!is_null($css_string)) {
					$this->tpl->assign('css_content', $css_string);
				}

				break;
			case 'logo':
				$nren = $this->person->getNREN();
				$basepath = Config::get_config('custom_logo') . $nren .
				                  "/custom";
				foreach (ConfusaConstants::$ALLOWED_LOGO_POSITIONS as $pos) {
					foreach (ConfusaConstants::$ALLOWED_IMG_SUFFIXES as $sfx) {
						$logo_name = $basepath . "_" . $pos . "." . $sfx;
						if (file_exists($logo_name)) {
							$imgurl = "view_logo.php?nren=$nren&amp;pos=$pos&amp;suffix=$sfx";
							$this->tpl->assign("logo_$pos", $imgurl);
							break;
						}
					}
				}

				$this->assignLogoDimsFromCSS($this->person->getNREN());

				$this->tpl->assign('edit_logo', true);
				$extensions = implode(", ", ConfusaConstants::$ALLOWED_IMG_SUFFIXES);
				$this->tpl->assign('extensions', $extensions);
				break;
			case 'mail':
				$this->tpl->assign('edit_mail', true);
				$this->tpl->assign('tags', $this->NOTIFICATION_MAIL_TAGS);

				/* set the supplied mail_content back in the
				 * form (exported to tpl with same name. */
				$template_string = $this->fetchNRENMailTpl($this->person->getNREN());
				$changed_template = $template_string;

				if (isset($_POST['mail_content'])) {
					/* we have new content, store and
					 * compare later with
					 * default-template. This is so we can
					 * retain the values between
					 * iterations. */
					$changed_template = strip_tags($_POST['mail_content']);
				}
				if (isset($template_string)) {
					if ($template_string == $changed_template) {
						$this->tpl->assign('mail_content', $template_string);
					} else {
						$this->tpl->assign('mail_content', $changed_template);
					}
				}
				break;
			case 'title':
				$nren = $this->person->getNREN();
				$this->tpl->assign('edit_title', true);
				$this->tpl->assign('portalTitle', $nren->getCustomPortalTitle());
				$this->tpl->assign('showPortalTitle', $nren->getShowPortalTitle());
				break;
			default:
				Framework::error_output("Unsupported operation chosen!");
				break;
			}
		}

		$this->tpl->assign('content', $this->tpl->fetch('stylist.tpl'));
	}

	/*
	 * Get the help and about texts for a certain NREN from the DB
	 *
	 * @param $nren The name of the NREN for which to retrieve the texts
	 * @return list($help, $about) where $help Individual help text
	 *									 $about Individual about text
	 */
	private function getNRENTexts($nren)
	{
		$sample_text ="h4. A heading\n\nMake a *strong* point on something\n\n";
		$sample_text .= "_Emphasize_ a point, -invalidate it-, +insert replacement+\n\n";
		$sample_text .= "* Enumerate\n";
		$sample_text .= "* all the\n";
		$sample_text .= "* advantages\n";
		$sample_text .= "# list-items\n";
		$sample_text .= "## and even subadvantages\n\n\n";
		$sample_text .= "|ung|äldre|äldst|\n";
		$sample_text .= "|barn|mor|mormor|\n";
		$sample_text .= "|tables|are|nice|\n\n";
		$sample_text .= "Appear smart, use footnotes[1]\n\n";
		$sample_text .= "\"Present a link\":http://beta.confusa.org\n\n";
		$sample_text .= "fn1. Roddenberry, G.: Where no man has gone before\n";


		$query = "SELECT help, about, privacy_notice FROM nrens WHERE name=?";

		$res = NULL;

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem looking up the NREN about-, help- and ".
						"privacy-notice-texts in the DB. " .
						"Looks like a server problem, contact an administrator. " .
						"Server said " .  htmlentities($dbse->getMessage()));
			return NULL;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem looking up the NREN about-, help- and ".
						"privacy-notice-texts in the DB. " .
						"Looks like a problem with the supplied data. " .
						"Server said " . htmlentities($dbqe->getMessage()));
			return NULL;
		}

		if (count($res) === 1) {
			$result = array();

			if (is_null($res[0]['help'])) {
				$result[0] = $sample_text;
			} else {
				$result[0] = Input::br2nl(stripslashes($res[0]['help']));
			}

			if (is_null($res[0]['about']) || empty($res[0]['about'])) {
				$result[1] = $sample_text;
			} else {
				$result[1] = Input::br2nl(stripslashes($res[0]['about']));
			}

			if (is_null($res[0]['privacy_notice']) || empty($res[0]['privacy_notice'])) {
				$result[2] = $sample_text;
			} else {
				$result[2] = Input::br2nl(stripslashes($res[0]['privacy_notice']));
			}
			return $result;
		} else if (count($res) > 1) { /* conflict!! */
			Framework::error_output("More than one pair of about and help texts in the DB." .
									"Please contact an administrator to resolve this!");
			return NULL;
		}

	}

	/*
	 * Update the help text of a certain NREN
	 *
	 * @param $nren The NREN whose help text is to be updated
	 * @param $new_text The new help text of that NREN
	 */
	private function updateNRENHelpText($nren, $new_text)
	{
		$query = "UPDATE nrens SET help=? WHERE nren_id=?";

		try {
			$res = MDB2Wrapper::update($query,
						   array('text', 'text'),
						   array($new_text, $nren->getID()));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem updating the help text of your NREN! " .
						"Please contact an administrator to resolve this! Server said " .
						htmlentities($dbse->getMessage()));
			return;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem updating the help text of your NREN, " .
						"probably related to the supplied data. Please verify the data to be inserted! " .
						"Server said " . htmlentities($dbqe->getMessage()));
			return;
		}

		Logger::log_event(LOG_INFO, "Help-text for NREN $nren was changed. " .
				  "User contacted us from " . $_SERVER['REMOTE_ADDR']);
		Framework::success_output($this->translateTag('l10n_suc_updhelptext', 'stylist'));
	}

	/*
	 * Update the about-text of a NREN
	 *
	 * @param $nren The NREN whose about-text is going to be updated
	 * @param $new_text The updated about-text
	 */
	private function updateNRENAboutText($nren, $new_text)
	{
		$query = "UPDATE nrens SET about=? WHERE nren_id=?";

		try {
			$res = MDB2Wrapper::update($query,
						   array('text', 'text'),
						   array($new_text, $nren->getID()));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem updating the about text of your NREN! " .
						"Please contact an administrator to resolve this! Server said " .
						htmlentities($dbse->getMessage()));
			return;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem updating the about text of your NREN, " .
						"probably related to the supplied data. Please verify the data to be inserted! " .
						"Server said " . htmlentities($dbqe->getMessage()));
			return;
		}

		Logger::log_event(LOG_INFO, "About-text for NREN $nren was changed. " .
				  "User contacted us from " . $_SERVER['REMOTE_ADDR']);
		Framework::success_output($this->translateTag('l10n_suc_updabouttext', 'stylist'));
	}

	/*
	 * Update the privacy_notice of a NREN
	 *
	 * @param $nren The NREN whose about-text is going to be updated
	 * @param $new_text The updated privacy-notice
	 */
	private function updateNRENPrivacyNotice($nren, $new_text)
	{
		$query = "UPDATE nrens SET privacy_notice=? WHERE nren_id=?";

		try {
			$res = MDB2Wrapper::update($query,
						   array('text', 'text'),
						   array($new_text, $nren->getID()));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem updating the privacy-notice of your NREN! ".
						"Please contact an administrator to resolve this! ".
						"Server said " . htmlentities($dbse->getMessage()));
			return;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem updating the about text of your NREN, " .
						"probably related to the supplied data. ".
						"Please verify the data to be inserted! " .
						"Server said " . htmlentities($dbqe->getMessage()));
			return;
		}

		Logger::log_event(LOG_INFO, "Privacy-notice for NREN $nren was changed by ".
				  $this->person->getEPPN() . " from " . $_SERVER['REMOTE_ADDR']);
		Framework::success_output($this->translateTag('l10n_suc_privnoticetext', 'stylist'));
	}

	/**
	 * Change the portal title for the given NREN to the new title, or disable
	 * the display of a portal title.
	 *
	 * @param $nren NREN The NREN for which the custom portal title is set
	 * @param $portalTitle string The custom portal-title for the NREN
	 * @param $showPortalTitle boolean Whether a portal-title should be shown
	 *                                 for the NREN
	 * @return void
	 */
	private function updateNRENTitle($nren, $portalTitle, $showPortalTitle)
	{
		$nren->setCustomPortalTitle($portalTitle);
		$nren->setShowPortalTitle($showPortalTitle);

		if ($nren->saveNREN()) {
			Framework::success_output($this->translateTag('l10n_suc_portaltitle', 'stylist') .
			                          " $portalTitle");
		}
	}

	/**
	 * Fetch the CSS file content for a certain NREN. If no CSS file for the
	 * NREN has been defined so far, display the standard site-wide CSS
	 *
	 * @param $nren The NREN for which the CSS-file is to be fetched
	 */
	private function fetchNRENCSS($nren)
	{
		$css_path = Config::get_config('custom_css') . $nren . '/custom.css';

		if (file_exists($css_path) === TRUE) {
			try {
				$css_string = File_IO::readFromFile($css_path);
				return Input::sanitizeCSS($css_string);
			} catch (FileException $fexp) {
				Framework::error_output("Could not open NREN-specific CSS file! Server said "
										. htmlentities($fexp->getMessage()) . "!");
			}
		}

		/* if the search for a custom CSS did not return a result, search for
		 * the main CSS
		 */
		$main_css_path = Config::get_config('install_path') . 'www/css/';
		$main_css_path .= 'confusa2.css';

		try {
			$css_string = File_IO::readFromFile($main_css_path);
			return Input::sanitizeCSS($css_string);
		} catch (FileException $fexp) {
			Framework::error_output("Could not open Confusa's main CSS file! Server said "
									. htmlentities($fexp->getMessage()) . "!");
			return;
		}
	}

	/**
	 * get the custom mail template defined per NREN
	 *
	 * @param $nren string the name of the NREN for which the custom template
	 *                     exists
	 */
	private function fetchNRENMailTpl($nren)
	{
		$tpl_path = Config::get_config('custom_mail_tpl') . $nren . '/custom.tpl';

		if (file_exists($tpl_path) === true) {
			try {
				$tpl_string = File_IO::readFromFile($tpl_path);
				return $tpl_string;
			} catch (FileException $fexp) {
				Framework::error_output("Could not open NREN-specific notification " .
				                        "template! Server said " .
				                        htmlentities($fexp->getMessage()) . "!");
			}
		}

		$main_tpl_path = Config::get_config('install_path') . 'lib/smarty/templates/';
		$main_tpl_path .= 'email/notification.tpl';

		try {
			$tpl_string = File_IO::readFromFile($main_tpl_path);
			return $tpl_string;
		} catch (FileException $fexp) {
			Framework::error_output("Could not open Confusa's default notification " .
			                        "mail template! Server said " .
			                        htmlentities($fexp->getMessage()) . "!");
			Logger::log_event(LOG_WARN, "[nadm] Could not open Confusa's default " .
			                            "notification mail template! Server said " .
			                            $fexp->getMessage());
		}
	} /* end fetchNRENMailTpl */

	/*
	 * Update the customized CSS file of a certain NREN. Write the CSS file to
	 * a certain NREN-specific folder on the filesystem.
	 *
	 * @param $nren The NREN whose CSS is to be updated
	 * @param $content The content which forms the new custom CSS file of the NREN
	 */
	private function updateNRENCSS($nren, $content)
	{
		$css_path = Config::get_config('custom_css') . $nren . '/custom.css';

		try {
			/* if the path to the NREN's CSS file does not exist, create the
			 * respective folders
			 * This should have been done by the bootstrap script, though
			 */
			File_IO::writeToFile($css_path, $content, TRUE, TRUE);
		} catch (FileException $fexp) {
			Framework::error_output("Could not write to custom CSS file! Please contact an administrator!");
			return;
		}

		Logger::log_event(LOG_INFO, "The custom CSS for NREN " . $nren .
									" was changed. User contacted us from " .
									$_SERVER['REMOTE_ADDR']);
		Framework::success_output($this->translateTag('l10n_suc_updcss', 'stylist'));
	}

	/**
	 * Replace the content of the notification mail template with $content
	 *
	 * @param $nren string the NREN for which the notification mail is updated
	 * @param $content string the content of the new notification mail template
	 */
	private function updateNRENMailTpl($nren, $content)
	{
		$template_path = Config::get_config('custom_mail_tpl') . $nren . '/custom.tpl';

		/* filter the content, only allowed smarty tags must be included */
		$tok = strtok($content, '{$');

		while ($tok !== false) {
			$close_tag = strpos($tok, '}');
			$variable = substr($tok, 2, $close_tag - 2);

			/* tag not allowed */
			if (array_search($variable, $this->NOTIFICATION_MAIL_TAGS) === false) {
				/* strip the variable identifiers from it */
				$content = str_replace('{$' . $variable . '}', $variable, $content);
			}

			$tok = strtok($content);
		}

		/* now replace all occurences of '{' not followed by '$'
		 * (non-variable smarty control structures) */
		$content = preg_replace('/\{[^$$].*\}/', '', $content);

		try {
			File_IO::writeToFile($template_path, $content, true, true);
		} catch (FileException $fexp) {
			Framework::error_output("Could not write the custom notification template! " .
			                        "Please contact an IT-administrator.");
			return;
		}

		Logger::log_event(LOG_INFO, "The notification mail template for NREN " .
		                            $nren .
		                            " was changed. User contacted us from " .
		                            $_SERVER['REMOTE_ADDR']);
		Framework::success_output($this->translateTag('l10n_suc_updnotmail', 'stylist'));
	}

	/**
	 * Send a test mail to the given recipient using the customized NREN
	 * template of the recipient.
	 *
	 * @param $recipient Person The recipient to which the test-email is sent
	 */
	private function sendNRENTestMail($recipient, $template)

	{
		require_once 'mail_manager.php';
		require_once 'CA.php';

		$timezone = new DateTimeZone($this->person->getTimezone());
		$dt = 		new DateTime("now", $timezone);

		$ip					= $_SERVER['REMOTE_ADDR'];
		$order_number		= '1234567890 (invalid example)';
		CA::sendMailNotification($order_number,
		                         $dt->format('Y-m-d H:i T'),
		                         $ip,
		                         $recipient,
		                         $this->ca->getFullDN(),
		                         $template);

		$email = $recipient->getEmail();
		Framework::success_output($this->translateTag('l10n_suc_testmailsent', 'stylist') .
		                          " " . $email);
	} /* end sendNRENTestMail */

	/**
	 * Download customized CSS to the user's harddisk
	 *
	 * @param $css string the updated CSS
	 */
	private function downloadNRENCSS($css)
	{
		require_once 'file_download.php';
		download_file($css, 'custom.css');
	}

	/*
	 * Reset the CSS changes of a certain NREN. In techspeak, delete the custom
	 * CSS file so a fallback to the standard CSS file will be performed.
	 *
	 * @param $nren The NREN, whose custom CSS is to be "reset"
	 */
	private function resetNRENCSS($nren)
	{
		$css_file = Config::get_config('custom_css') . $nren . '/custom.css';

		if (file_exists($css_file)) {
			$success = unlink($css_file);

			if ($success === FALSE) {
				Framework::error_output("Could not reset the CSS file! Please contact an administrator!");
			}
		}

		Framework::message_output($this->translateTag('l10n_suc_cssreset', 'stylist'));
	}

	private function resetNRENMailTpl($nren)
	{
		$tpl_file = Config::get_config('custom_mail_tpl') . $nren . '/custom.tpl';

		if (file_exists($tpl_file)) {
			$success = unlink($tpl_file);

			if ($success === false) {
				Framework::error_output("Could not reset the notification mail template!" .
				                        "Please contact an IT-administrator.");
			}
		}

		Framework::message_output($this->translateTag('l10n_suc_mailreset', 'stylist'));
	}

	/*
	 * Upload a custom logo for a certain NREN. Enforce
	 * filename (suffix) constraints. Store the file in a NREN-specific
	 * subdirectory of the graphics-folder, suffixed with the position within
	 * Confusa (tl - top left, tc - top center, tr - top right, bg - background,
	 * bl - bottom left, bc - bottom center, br - bottom right).
	 *
	 * @param $filename string the filename of the uploaded file
	 * @param $pos char(2) the position within confusa
	 * @param $nren string the name of the NREN the logo belongs to
	 */
	private function uploadLogo($filename, $pos, $nren) {
		$fu = new FileUpload($filename, false, false, NULL);

		if ($fu->file_ok()) {
			$file_tokens = explode(".", $_FILES[$filename]['name']);

			$suffix = $file_tokens[count($file_tokens) - 1];

			if (array_search($suffix, ConfusaConstants::$ALLOWED_IMG_SUFFIXES) === FALSE) {
				Framework::error_output($this->translateTag('l10n_err_illegalending', 'stylist') . " "
									. implode(" ", ConfusaConstants::$ALLOWED_IMG_SUFFIXES));
				return;
			}

			list($width, $height, $type) = getimagesize($_FILES[$filename]['tmp_name']);

			if (is_null($type) || $type < 0) {
				Framework::error_output($this->translateTag('l10n_err_notanimage', 'stylist'));
				return;
			}

			/* keep the suffix but change the name to custom_[pos].suffix
			 */
			$logo_path = Config::get_config('custom_logo');
			$logo_path .= $nren;

			if (!file_exists($logo_path)) {
				mkdir($logo_path, 0755, TRUE);
			} else {
				/* delete all the other potential logos that might be there */
				foreach (ConfusaConstants::$ALLOWED_IMG_SUFFIXES as $all_suffix) {
					$file = $logo_path . "/custom_$pos.$all_suffix";
					if (file_exists($file)) {
						unlink($file);
					}
				}
			}

			$content = $fu->get_content();
			$logo_file = $logo_path . '/custom_' . $pos . '.' . $suffix;

			try {
				$fu->write_content_to_file($logo_file);
			} catch (FileException $fexp) {
				Framework::error_output("Could not save the logo on the server. " .
							"Server said: " . htmlentities($fexp->getMessage()));
				return;
			}

			Logger::log_event(LOG_INFO, "Logo for NREN $nren was changed to new " .
							  "logo custom.$suffix User contacted us from " .
							  $_SERVER['REMOTE_ADDR']);
			Framework::success_output($this->translateTag('l10n_suc_updatelogo', 'stylist'));
		}
	}

	/**
	 * Parse the CSS for the current logo dimensioning and assign it to the
	 * smarty template. This should give the administrator that is using the
	 * stylist some help in picking correctly dimensioned logos or adapting
	 * the CSS.
	 *
	 * @param $nren string the NREN whose customized CSS applies
	 * @return void
	 */
	private function assignLogoDimsFromCSS($nren) {
		$css_string = $this->fetchNRENCSS($nren);

		if (isset($css_string)) {
			$pos_tl = stripos($css_string, "#logo_header_left");
			$width_tl_b = stripos($css_string, "min-width:", $pos_tl);
			$width_tl_b += 10;
			$width_tl_e = stripos($css_string, ";", $width_tl_b);
			$width_tl = substr($css_string, $width_tl_b, $width_tl_e - $width_tl_b);
			$this->tpl->assign("css_tl", $width_tl);

			$pos_tc = stripos($css_string, "#logo_header_center");
			$width_tc_b = stripos($css_string, "min-width:", $pos_tc);
			$width_tc_b += 10;
			$width_tc_e = stripos($css_string, ";", $width_tc_b);
			$width_tc = substr($css_string, $width_tc_b, $width_tc_e - $width_tc_b);
			$this->tpl->assign("css_tc", $width_tc);

			$pos_tr = stripos($css_string, "#logo_header_right");
			$width_tr_b = stripos($css_string, "min-width:", $pos_tr);
			$width_tr_b += 10;
			$width_tr_e = stripos($css_string, ";", $width_tr_b);
			$width_tr = substr($css_string, $width_tr_b, $width_tr_e - $width_tr_b);
			$this->tpl->assign("css_tr", $width_tr);

			$pos_bl = stripos($css_string, "#logo_footer_left");
			$width_bl_b = stripos($css_string, "min-width:", $pos_bl);
			$width_bl_b += 10;
			$width_bl_e = stripos($css_string, ";", $width_bl_b);
			$width_bl = substr($css_string, $width_bl_b, $width_bl_e - $width_bl_b);
			$this->tpl->assign("css_bl", $width_bl);

			$pos_bc = stripos($css_string, "#logo_footer_center");
			$width_bc_b = stripos($css_string, "min-width:", $pos_bc);
			$width_bc_b += 10;
			$width_bc_e = stripos($css_string, ";", $width_bc_b);
			$width_bc = substr($css_string, $width_bc_b, $width_bc_e - $width_bc_b);
			$this->tpl->assign("css_bc", $width_bc);

			$pos_br = stripos($css_string, "#logo_footer_right");
			$width_br_b = stripos($css_string, "min-width:", $pos_br);
			$width_br_b += 10;
			$width_br_e = stripos($css_string, ";", $width_br_b);
			$width_br = substr($css_string, $width_br_b, $width_br_e - $width_br_b);
			$this->tpl->assign("css_br", $width_br);
		}
	}

	/**
	 * Delete the NREN logo for the given position within Confusa. This will
	 * really delete the physical file containing the logo.
	 *
	 * @param $position string a position from
	 *                  ConfusaConstants::$ALLOWED_IMG_POSITIONS
	 * @param $nren string the name of the NREN, whose custom-logo should be
	 *                     removed
	 * @return void
	 */
	private function deleteLogo($position, $nren)
	{
		$basepath = Config::get_config('custom_logo') . $nren . "/custom_";
		$basepath .= $position . ".";
		$result = FALSE;

		foreach (ConfusaConstants::$ALLOWED_IMG_SUFFIXES as $sfx) {
			$logoName = $basepath . $sfx;
			if (file_exists($logoName)) {
				$result = unlink($logoName);
				break;
			}
		}

		if ($result === FALSE) {
			Framework::error_output("Could not delete NREN-logo with name " .
			                        htmlentities($logoName) .
			                        ". Maybe the server is misconfigured, " .
			                        "please contact a site-administrator.");
			Logger::log_event(LOG_INFO, "[nadm] Error when trying to delete " .
			                  "NREN logo $logoName, for NREN $nren.");
		}
	} /* end function deleteLogo */
}
$fw = new Framework(new CP_Stylist());
$fw->start();
?>
