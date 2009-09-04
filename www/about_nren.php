<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'classTextile.php';

class CP_About_NREN extends FW_Content_Page
{
	function __construct()
	{
		parent::__construct("About NREN", false, "index.php");
	}


	public function process()
	{
		$logo = Framework::get_logo_for_nren($this->person->getNREN());
		$this->tpl->assign('logo', $logo);
		$about_text = $this->getAboutTextForNREN($this->person->getNREN());
		$this->tpl->assign('text_info', $about_text);
		$this->tpl->assign('content', $this->tpl->fetch('about_nren.tpl'));
	}

	/*
	 * Get the about-text for a certain NREN, so it can be displayed in Confusa's
	 * about-section
	 */
	private function getAboutTextForNREN($nren)
	{
		$query = "SELECT about FROM nrens WHERE name = ?";

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Error fetching the NREN about-page. Probably a " .
									"configuration problem! Server said: " . $dbse->getMessage());
			return "";
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Error fetching the NREN about-page. Looks like a " .
									"problem with the supplied data. Queried NREN was " . $nren);
			return "";
		}

		if (count($res) > 0) {
			$textile = new Textile();
			return $textile->TextileRestricted(Input::br2nl(stripslashes($res[0]['about'])),0);
		} else {
			return "";
		}
	}

}

$fw = new Framework(new CP_About_NREN());
$fw->start();

?>
