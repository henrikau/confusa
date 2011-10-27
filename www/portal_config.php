<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';

class CP_Portal_Config extends Content_Page
{
	private $maint_msg		= null;
	private $maint_mode		= null;

	function __construct() {
		parent::__construct("Portal Config", true, 'portal_config');
	}


	public function pre_process($person)
	{
		parent::pre_process($person);
		$this->readMaintMsg();
		$this->readMaintMode();

		/* if $person is not a NREN admin we stop here */
		if (!$this->person->isNRENAdmin()) {
			return false;
		}


		/* set new maint-msg */
		if (array_key_exists("nren_maint_msg", $_POST)
		    && $_POST["nren_maint_msg"] !== ""
		    && $_POST["nren_maint_msg"] !== $this->maint_msg) {
			if ($this->saveMaintMsg($_POST['nren_maint_msg'])) {
				Framework::success_output("Successfully saved new NREN maintenance message to database.");
			} else {
				Framework::error_output("Could not save NREN maintenance message to database.");
			}
		}

		/* set/unset in maint-mode? */
		if (array_key_exists("nren_maint_mode", $_POST)) {
			$mode = $_POST['nren_maint_mode'];
			if ($this->maint_mode !== $mode) {
				if ($this->saveMaintMode(Input::sanitizeMaintMode($mode))) {
					if ($this->maint_mode === 'y')
						Framework::success_output("Successfully placed portal in NREN-maintenance mode.");
					else
						Framework::success_output("Successfully placed portal in NREN-normal mode.");
				} else {
					if ($this->maint_mode === 'y')
						Framework::error_output("Could not place portal in NREN-maintenance-mode.");
					else
						Framework::error_output("Could not place portal in NREN-normal mode.");

				}
			}

		}
	} /* end pre_process() */

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

		$this->tpl->assign('maint_mode', ($this->maint_mode === 'y') ? true : false);
		$this->tpl->assign('content', $this->tpl->fetch('portal_config.tpl'));
	}

	/**
	 * readMaintMsg() read the message from the database and cache locally.
	 */
	private function readMaintMsg()
	{
		if (!is_null($this->maint_msg))
			return false;

		try {
			$res =  MDB2Wrapper::execute("SELECT maint_mode, maint_msg FROM nrens WHERE nren_id=?",
						     array('text'),
						     array($this->person->getNREN()->getID()));
		} catch (DBQueryException $dbqe) {
			/* FIXME */
			;
		} catch (DBStatementException $dbse) {
			/* FIXME */
			;
		}
		if (count($res) == 1) {
			if (array_key_exists('maint_msg', $res[0])) {
				$this->maint_msg = $res[0]['maint_msg'];
				return true;
			}
		}
		return false;
	} /* end readMaintMsg() */

	private function saveMaintMsg($msg)
	{
		if (!isset($msg))
			return false;
		try {
			MDB2Wrapper::update("UPDATE nrens SET maint_msg=?  WHERE nren_id=?",
								array('text', 'text'),
								array($msg, $this->person->getNREN()->getID()));
		} catch (DBQueryException $dbqe) {
			/* FIXME */
			;
		} catch (DBStatementException $dbse) {
			/* FIXME */
			;
		}

		$this->readMaintMsg();
		$nname = $this->person->getNREN()->getName();
		if ($this->maint_msg !== $msg) {
			Logger::log_event(LOG_ERR, "Could not save NREN-maintenance-message for $nname to DB.");
			return false;
		}
		Logger::log_event(LOG_NOTICE, $this->person->getEPPN() . "(".
						  $this->person->getName().
						  ") updated maintenance-message for " .
						  $this->person->getNREN()->getName());
		return true;
	}

	/**
	 * readMaintMode() - return the mode
	 *
	 * This function is pretty strict, only 'y' will evaluate to be in
	 * maintenance-mode, everyting else will result in 'n' (also if value is
	 * empty or erroneous.
	 *
	 * @param void
	 * @return void
	 * @access private
	 */
	private function readMaintMode()
	{
		if (isset($this->maint_mode)) {
			return;
		}
		try {
			$res = MDB2Wrapper::execute("SELECT maint_mode FROM nrens WHERE nren_id=?",
										array('text'),
										array($this->person->getNREN()->getID()));
		} catch (DBQueryException $dbqe) {
			/* FIXME */
			;
		} catch (DBStatementException $dbse) {
			/* FIXME */
			;
		}
		$this->maint_mode = 'n';
		if (count($res) > 0 && array_key_exists('maint_mode', $res[0])) {
			$this->maint_mode = Input::sanitizeMaintMode($res[0]['maint_mode']);
		}
	}

	/**
	 * setMaintMode() - set the maintenance-mode for the current NREN
	 *
	 * @param String $mode the mode (either 'y' or 'n')
	 * @returns Boolean success if the new mode was stored in the database.
	 * @access private
	 */
	private function saveMaintMode($mode)
	{
		if (!isset($mode))
			return false;
		unset($this->maint_mode);
		try {
			MDB2Wrapper::update("UPDATE nrens SET maint_mode=? WHERE nren_id=?",
								array('text', 'text'),
								array($mode, $this->person->getNREN()->getID()));
		} catch (DBQueryException $dbqe) {
			/* FIXME */
			;
		} catch (DBStatementException $dbse) {
			/* FIXME */
			;
		}
		$this->readMaintMode();
		$nname = $this->person->getNREN()->getName();

		if ($this->maint_mode !== $mode) {
			Logger::log_event(LOG_ERR, "Could not update maintenance-mode for $nname in the database.");
			return false;
		}

		Logger::log_event(LOG_NOTICE, $this->person->getEPPN() . " (".
						  $this->person->getName().
						  ") moved NREN $nname" .
						  ($mode === 'y' ? " into maintenance mode." : " out of maintenance mode."));
		return true;
	}
}

$fw = new Framework(new CP_Portal_Config());
$fw->start();
?>
