<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'input.php';
require_once 'file_upload.php';
require_once 'logger.php';

class CP_Stylist extends FW_Content_Page
{

	/* maximum width for custom logos */
	private $allowed_width;
	/* maximum height for custom logos */
	private $allowed_height;

	function __construct() {
		parent::__construct("Stylist", true);
		$this->allowed_width = 200;
		$this->allowed_height = 200;
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
			case 'change_css':
				if (isset($_POST['reset'])) {
					$this->resetNRENCSS($this->person->getNREN());
				} else {
					/* the CSS will not be inserted into the DB or executed in another way.
					* Hence do not sanitize it. It will contain 'dangerous' string portions,
					* such as { : ' anyways, so it would be hard to insert it into the DB properly*/
					$new_css = Input::sanitizeCSS($_POST['css_content']);
					$this->updateNRENCSS($this->person->getNREN(), $new_css);
				}
				break;
			case 'upload_logo':
				if (isset($_FILES['nren_logo']['name'])) {
					/* only allow image uploads */
					if (eregi('image/', $_FILES['nren_logo']['type'])) {
						$this->uploadLogo('nren_logo', $this->person->getNREN());
					}
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
				}

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
				$this->tpl->assign('edit_logo', true);
				$logo = Framework::get_logo_for_nren($this->person->getNREN());
				$this->tpl->assign('logo', $logo);
				$extensions = implode(", ", Framework::$allowed_img_suffixes);
				$this->tpl->assign('extensions', $extensions);
				$this->tpl->assign('width', $this->allowed_width);
				$this->tpl->assign('height', $this->allowed_height);
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
	 * 									 $about Individual about text
	 */
	private function getNRENTexts($nren)
	{
		$query = "SELECT help, about FROM nrens WHERE name=?";

		$res = NULL;

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem looking up the NREN about- and help-texts in the DB. " .
									"Looks like a server problem, contact an administrator. " .
									"Server said " .  $dbse->getMessage());
			return NULL;
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem looking up the NREN about- and help-texts in the DB. " .
									"Looks like a problem with the supplied data. " .
									"Server said " . $dbqe->getMessage());
			return NULL;
		}

		if (count($res) === 1) {
			$result = array();
			$result[0] = $res[0]['help'];
			$result[1] = $res[0]['about'];
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
		$query = "UPDATE nrens SET help=? WHERE name=?";

		try {
			$res = MDB2Wrapper::update($query,
										array('text', 'text'),
										array($new_text, $nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem updating the help text of your NREN! " .
									"Please contact an administrator to resolve this! Server said " . $dbse->getMessage());
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem updating the help text of your NREN, " .
									"probably related to the supplied data. Please verify the data to be inserted! " .
									"Server said " . $dbqe->getMessage());
		}

		Logger::log_event(LOG_INFO, "Help-text for NREN $nren was changed. " .
				  "User contacted us from " . $_SERVER['REMOTE_ADDR']);
	}

	/*
	 * Update the about-text of a NREN
	 *
	 * @param $nren The NREN whose about-text is going to be updated
	 * @param $new_text The updated about-text
	 */
	private function updateNRENAboutText($nren, $new_text)
	{
		$query = "UPDATE nrens SET about=? WHERE name=?";

		try {
			$res = MDB2Wrapper::update($query,
									   array('text', 'text'),
									   array($new_text, $nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Problem updating the about text of your NREN! " .
									"Please contact an administrator to resolve this! Server said " . $dbse->getMessage());
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Problem updating the about text of your NREN, " .
									"probably related to the supplied data. Please verify the data to be inserted! " .
									"Server said " . $dbqe->getMessage());
		}

		Logger::log_event(LOG_INFO, "About-text for NREN $nren was changed. " .
						  "User contacted us from " . $_SERVER['REMOTE_ADDR']);
	}

	/**
	 * Fetch the CSS file content for a certain NREN. If no CSS file for the
	 * NREN has been defined so far, display the standard site-wide CSS
	 *
	 * @param $nren The NREN for which the CSS-file is to be fetched
	 */
	private function fetchNRENCSS($nren)
	{
		$css_path = Config::get_config('install_path') . 'www/css/';
		$css_path .= 'custom/' . $nren . '/custom.css';

		if (file_exists($css_path) === TRUE) {
			$fd = fopen($css_path, 'r');

			if ($fd === FALSE) {
				Framework::error_output('Could not open NREN-specific CSS file! Please contact an administrator!');
				return;
			}

			$css_string = fread($fd, filesize($css_path));
			fclose($fd);

			return $css_string;
		}

		/* if the search for a custom CSS did not return a result, search for
		 * the main CSS
		 */
		$main_css_path = Config::get_config('install_path') . 'www/css/';
		$main_css_path .= 'confusa2.css';

		if (file_exists($main_css_path) === TRUE) {
			$fd = fopen($main_css_path, 'r');

			if ($fd === FALSE) {
				Framework::error_output("Could not open Confusa's main CSS file! Please contact an administrator!");
				return;
			}

			$css_string = fread($fd, filesize($main_css_path));
			fclose($fd);

			return Input::sanitizeCSS($css_string);
		}

		return NULL;
	}

	/*
	 * Update the customized CSS file of a certain NREN. Write the CSS file to
	 * a certain NREN-specific folder on the filesystem.
	 *
	 * @param $nren The NREN whose CSS is to be updated
	 * @param $content The content which forms the new custom CSS file of the NREN
	 */
	private function updateNRENCSS($nren, $content)
	{
		$css_path = Config::get_config('install_path') . 'www/css/';
		$css_path .= 'custom/' . $nren;

		/* if the path to the NREN's CSS file does not exist, create the
		 * respective folders
		 * This should have been done by the bootstrap script, though
		 */
		if (!file_exists($css_path)) {
			mkdir($css_path, 0755, TRUE);
		}

		$css = $css_path . '/custom.css';
		$fd = fopen($css, "w");

		if ($fd === FALSE) {
			Framework::error_output("Could not write to custom CSS file! Please contact an administrator!");
			return;
		}

		if (ini_get('magic_quotes_gpc') === "1") {
			/* no slashes should be introduced into the content */
			$content = stripslashes($content);
		}

		$success = fwrite($fd, $content);

		if ($success === FALSE) {
			Framework::error_output("Could not write to custom CSS file! Please contact an administrator!");
		} else {
			Logger::log_event(LOG_INFO, "The custom CSS for NREN " . $nren .
										" was changed. User contacted us from " .
										$_SERVER['REMOTE_ADDR']);
		}

		fclose($fd);
		return;
	}

	/*
	 * Reset the CSS changes of a certain NREN. In techspeak, delete the custom
	 * CSS file so a fallback to the standard CSS file will be performed.
	 *
	 * @param $nren The NREN, whose custom CSS is to be "reset"
	 */
	private function resetNRENCSS($nren)
	{
		$css_file = Config::get_config('install_path') . 'www/css/';
		$css_file .= 'custom/' . $nren . '/custom.css';

		if (file_exists($css_file)) {
			$success = unlink($css_file);

			if ($success === FALSE) {
				Framework::error_output("Could not reset the CSS file! Please contact an administrator!");
			}
		}
	}

	/*
	 * Upload a custom logo for a certain NREN. Enforce dimensional constraints,
	 * as well as filename (suffix) constraints. Store the file in a NREN-specific
	 * subdirectory of the graphics-folder
	 */
	private function uploadLogo($filename, $nren) {
		$fu = new FileUpload($filename, false, false, NULL);

		if ($fu->file_ok()) {
			$file_tokens = explode(".", $_FILES[$filename]['name']);

			$suffix = $file_tokens[count($file_tokens) - 1];

			if (array_search($suffix, Framework::$allowed_img_suffixes) === FALSE) {
				Framework::error_output("Your file has an illegal ending! Make sure the ending is one of: "
									. implode(" ", Framework::$allowed_img_suffixes));
				return;
			}

			list($width, $height, $type) = getimagesize($_FILES[$filename]['tmp_name']);

			if (is_null($type) || $type < 0) {
				Framework::error_output("What you have provided doesn't seem to be an image!");
				return;
			}

			if ($width > $this->allowed_width) {
				Framework::error_output("The width of your image is $width pixel, greater than " .
										"the allowed image-width $this->allowed_width pixel. Please " .
										"crop or resize your image and upload it again");
				return;
			}

			if ($height > $this->allowed_height) {
				Framework::error_output("The height of your image is $width pixel, greater than " .
										"the allowed image-height $this->allowed_height pixel. Please " .
										"crop or resize your image and upload it again");
				return;
			}

			/* keep the suffix but change the name to custom.suffix
			 */
			$logo_path = Config::get_config('install_path') . 'www/';
			$logo_path .= Config::get_config('custom_logo');
			$logo_path .= $nren;

			if (!file_exists($logo_path)) {
				mkdir($logo_path, 0755, TRUE);
			} else {
				/* delete all the other potential logos that might be there */
				foreach (Framework::$allowed_img_suffixes as $all_suffix) {
					$file = $logo_path . "/custom.$all_suffix";
					if (file_exists($file)) {
						unlink($file);
					}
				}
			}

			$content = $fu->get_content();
			$logo_file = $logo_path . '/custom.' . $suffix;

			try {
				$fu->write_content_to_file($logo_file);
			} catch (FileException $fexp) {
				Framework::error_output("Could not save the logo on the server. " .
							"Server said: " . $fexp->getMessage());
			}

			Logger::log_event(LOG_INFO, "Logo for NREN $nren was changed to new " .
							  "logo custom.$suffix User contacted us from " .
							  $_SERVER['REMOTE_ADDR']);
		}
	}
}

$fw = new Framework(new CP_Stylist());
$fw->start();
?>
