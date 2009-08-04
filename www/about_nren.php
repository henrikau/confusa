<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';

class About_NREN extends FW_Content_Page
{
	function __construct()
	{
		parent::__construct("About NREN", false);
	}


	public function process()
	{
		$logo = Framework::get_logo_for_nren($this->person->get_nren());
		$this->tpl->assign('logo', $logo);
		$about_text = $this->getAboutTextForNREN($this->person->get_nren());
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
			return $res[0]['about'];
		} else {
			return "";
		}
	}

}

$fw = new Framework(new About_NREN());
$fw->start();

?>
