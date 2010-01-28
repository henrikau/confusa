<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'classTextile.php';
require_once 'logger.php';

class CP_Priv_Notice extends Content_Page
{
	function __construct()
	{
		parent::__construct("Help", false, "index");
	}

	public function process()
	{
		$nren = $this->person->getNREN();
		$pn_text = $this->getNRENpt($nren);
		$this->tpl->assign('nren', $nren);
		$this->tpl->assign('nren_pt_text', $pn_text);
		$this->tpl->assign('content', $this->tpl->fetch('privacy_notice.tpl'));

	}

	/*
	 * Get the custom privacy notice
	 *
	 * @param $nren The NREN
	 */
	private function getNRENpt($nren)
	{
		if (is_null($nren)) {
			return "";
		}

		$query = "SELECT privacy_notice FROM nrens WHERE nren_id = ?";
		$res = array();
		try {
			$res = MDB2Wrapper::execute($query,
						    array('text'),
						    array($nren->getID()));
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_INFO, "[norm] Could not retrieve the privnotice " .
			                  "text of NREN $nren due to an error with the " .
			                  "statement. Server said " . $dbse->getMessage());
			return "";
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_INFO, "[norm] Could not retrieve the privnotice " .
			                  "text of NREN $nren due to an error in the " .
			                  "query. Server said " . $dbqe->getMessage());
			return "";
		}

		if (count($res) > 0) {
			$pn=$res[0]['privacy_notice'];

			$pn=stripslashes($pn);
			$pn=Input::br2nl($pn);
			$textile = new Textile();
			return $textile->TextileRestricted($pn,0);
		}
		return "No privacy-notice has yet been set for your NREN ($nren)<br />";
	}
}

$fw = new Framework(new CP_Priv_Notice());
$fw->start();

?>
