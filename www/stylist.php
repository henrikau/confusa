<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'input.php';

class CP_Stylist extends FW_Content_Page
{
	function __construct() {
		parent::__construct("Stylist", true);
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		/* if $person is not a NREN admin we stop here */
		if (!$this->person->is_nren_admin()) {
			return false;
		}

		if (isset($_POST['stylist_operation'])) {
			switch(htmlentities($_POST['stylist_operation'])) {
			case 'change_help_text':
				$new_text = Input::sanitize($_POST['help_text']);
				$this->updateNRENHelpText($this->person->get_nren(), $new_text);
				break;
			case 'change_about_text':
				$new_text = Input::sanitize($_POST['about_text']);
				$this->updateNRENAboutText($this->person->get_nren(), $new_text);
				break;
			case 'change_css':
				if (isset($_POST['reset'])) {
					$this->resetNRENCSS($this->person->get_nren());
				} else {
					/* the CSS will not be inserted into the DB or executed in another way.
					* Hence do not sanitize it. It will contain 'dangerous' string portions,
					* such as { : ' anyways, so it would be hard to insert it into the DB*/
					$new_css = $_POST['css_content'];
					$this->updateNRENCSS($this->person->get_nren(), $new_css);
				}
				break;
			default:
				Framework::error_output("Unknown operation chosen in the stylist!");
				break;
			}
		}
	}

	public function process()
	{
		if (!$this->person->is_nren_admin()) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->get_valid_cn() . " tried to access the NREN-area");
			$this->tpl->assign('reason', 'You are not an NREN-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}

		if (isset($_GET['show'])) {
			switch(htmlentities($_GET['show'])) {
			case 'text':
				$texts = $this->getNRENTexts($this->person->get_nren());

				if ($texts != NULL) {
					$this->tpl->assign('help_text', $texts[0]);
					$this->tpl->assign('about_text', $texts[1]);
				}

				$this->tpl->assign('edit_help_text', true);
				break;
			case 'css':
				$this->tpl->assign('edit_css', true);
				$css_string = $this->fetchNRENCSS($this->person->get_nren());

				if (!is_null($css_string)) {
					$this->tpl->assign('css_content', $css_string);
				}

				break;
			case 'logo':
				$this->tpl->assign('edit_logo', true);
				break;
			default:
				Framework::error_output("Unsupported operation chosen!");
				break;
			}
		}

		$this->tpl->assign('content', $this->tpl->fetch('stylist.tpl'));
	}

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
	}

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
	}

	/**
	 * Fetch the CSS file content for a certain NREN. If no CSS file for the
	 * NREN has been defined so far, display the standard site-wide CSS
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
		echo $main_css_path;

		if (file_exists($main_css_path) === TRUE) {
			$fd = fopen($main_css_path, 'r');

			if ($fd === FALSE) {
				Framework::error_output("Could not open Confusa's main CSS file! Please contact an administrator!");
				return;
			}

			$css_string = fread($fd, filesize($main_css_path));
			fclose($fd);

			return $css_string;
		}

		return NULL;
	}

	private function updateNRENCSS($nren, $content)
	{
		$css_path = Config::get_config('install_path') . 'www/css/';
		$css_path .= 'custom/' . $nren;

		/* if the path to the NREN's CSS file does not exist, create the
		 * respective folders
		 * This should have been done by the bootstrap script, though
		 */
		if (!file_exists($css_path)) {
			mkdir($css_path, 0644, TRUE);
		}

		$css = $css_path . '/custom.css';
		$fd = fopen($css, "w");

		if ($fd === FALSE) {
			Framework::error_output("Could not write to custom CSS file! Please contact an administrator!");
			return;
		}

		$success = fwrite($fd, $content);

		if ($success === FALSE) {
			Framework::error_output("Could not write to custom CSS file! Please contact an administrator!");
			return;
		}

		fclose($fd);
	}

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
}

$fw = new Framework(new CP_Stylist());
$fw->start();
?>
