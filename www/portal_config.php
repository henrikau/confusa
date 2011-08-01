<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';

class CP_Portal_Config extends Content_Page
{
	private $maint_msg		= null;

	function __construct() {
		parent::__construct("Portal Config", true, 'portal_config');
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		/* if $person is not a NREN admin we stop here */
		if (!$this->person->isNRENAdmin()) {
			return false;
		}

		/* get existing maint-msg */
		$this->getMaintMsg();

		/* set new maint-msg */
		if (array_key_exists("nren_maint_msg", $_POST)
		    && $_POST["nren_maint_msg"] !== ""
		    && $_POST["nren_maint_msg"] !== $this->maint_msg) {
			try {
				$this->saveMaintMsg($_POST['nren_maint_msg']);
				Framework::success_output("Successfully saved new NREN maintenance message to database.");
			} catch(Exception $e) {
				$this->fw->error_output("Could not save NREN maintenance message to database.");
			}
		}

		/* set/unset in maint-mode? */
	}

	public function process()
	{
		if (!$this->person->isNRENAdmin()) {
			Logger::log_event(LOG_NOTICE, "User " . stripslashes($this->person->getX509ValidCN()) . " tried to access the NREN-area");
			$this->tpl->assign('reason', 'You are not an NREN-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}

		if (!is_null($this->maint_msg))
			$this->tpl->assign('nren_maint_msg', $this->maint_msg);

		$this->tpl->assign('content', $this->tpl->fetch('portal_config.tpl'));
	}

	private function getMaintMsg()
	{
		if (!is_null($this->maint_msg))
			return false;

		try {
			$res =  MDB2Wrapper::execute("SELECT maint_mode, maint_msg FROM nrens WHERE nren_id=?",
						     array('text'),
						     array($this->person->getNREN()->getID()));
		} catch (DBQueryException $dbqe) {
			;
		} catch (DBStatementException $dbse) {
			;
		}
		if (count($res) == 1) {
			if (array_key_exists('maint_msg', $res[0])) {
				$this->maint_msg = $res[0]['maint_msg'];
				return true;
			}
		}
		return false;
	} /* end getMaintMsg() */

	private function saveMaintMsg($msg)
	{
		if (is_null($this->maint_msg))
			$this->getMaintMsg;
		if (is_null($this->maint_msg))
			throw new Exception("Could not retrieve maint-msg from DB. Cannot save new msg");
		$this->maint_msg = $msg;
		MDB2Wrapper::update("UPDATE nrens SET maint_msg=?  WHERE nren_id=?",
				    array('text', 'text'),
				    array($msg, $this->person->getNREN()->getID()));

		$this->maint_msg = null;
		$this->getMaintMsg();
		if ($this->maint_msg !== $msg) {
			throw new Exception("Error when updating nren-maint-msg, could not write correct msg to database");
		}
		return true;
	}
}

$fw = new Framework(new CP_Portal_Config());
$fw->start();
?>
