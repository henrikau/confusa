<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'classTextile.php';
require_once 'logger.php';

class CP_Help extends Content_Page
{
	function __construct()
	{
		parent::__construct("Help", false);
	}

	public function process()
	{
		$nren = $this->person->getNREN();
		$help_text = $this->getNRENHelpText($nren);
		$this->tpl->assign('nren', $nren);
		$this->tpl->assign('nren_help_text', $help_text);
		$this->tpl->assign('help_file', file_get_contents('../include/help.html'));
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
			Logger::log_event(LOG_INFO, "[norm] Could not retrieve the help " .
			                  "text of NREN $nren due to an error with the " .
			                  "statement. Server said " . $dbse->getMessage());
			return "";
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_INFO, "[norm] Could not retrieve the help " .
			                  "text of NREN $nren due to an error in the " .
			                  "query. Server said " . $dbqe->getMessage());
			return "";
		}

		if (count($res) > 0) {
			$help_text=$res[0]['help'];

			$help_text=stripslashes($help_text);
			$help_text=Input::br2nl($help_text);
			$textile = new Textile();
			return $textile->TextileRestricted($help_text,0);
		}
	}
}

$fw = new Framework(new CP_Help());
$fw->start();

?>
