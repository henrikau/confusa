<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';

class Help extends FW_Content_Page
{
	function __construct()
	{
		parent::__construct("Help", false);
	}

	public function process()
	{
		$help_text = $this->getNRENHelpText($this->person->get_nren());
		$this->tpl->assign('nren_help_text', $help_text);
		$this->tpl->assign('help_file', file_get_contents('../include/ipso_lorem.html'));
		$this->tpl->assign('content', $this->tpl->fetch('help.tpl'));

	}

	/*
	 * Get the custom help text entered for/by a certain NREN
	 *
	 * @param $nren The NREN for which the help-text should be retrieved
	 */
	private function getNRENHelpText($nren)
	{
		$query = "SELECT help FROM nrens WHERE name = ?";

		$res = array();

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output("Could not retrieve the help text of your NREN due " .
									"to an error with the statement. Server said " .
									$dbse->getMessage());
			return "";
		} catch (DBQueryException $dbqe) {
			Framework::error_output("Could not retrieve the help text of your NREN due " .
									"to an error in the query. Server said " .
									$dbqe->getMessage());
			return "";
		}

		if (count($res) > 0) {
			return $res[0]['help'];
		}
	}
}

$fw = new Framework(new Help());
$fw->start();

?>

