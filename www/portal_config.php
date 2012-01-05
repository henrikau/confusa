<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';

/**
 * Portal Config - handle maintenance-mode for a particular NREN.
 */
class CP_Portal_Config extends Content_Page
{
	function __construct() {
		parent::__construct("Portal Config", true, 'portal_config');
	}


	public function pre_process($person)
	{
		if (!$person->isNRENAdmin()) {
			return false;
		}

		/* Need to do this /before/ pre-process to change page when we switch
		 * to/from maint-mode */
		$this->setPerson($person);
		$this->handleMaintMode();

		parent::pre_process($person);
		$this->handleMaintText();
	} /* end pre_process() */

	public function process()
	{
		if (!$this->person->isNRENAdmin()) {
			Logger::log_event(LOG_NOTICE, "User " . stripslashes($this->person->getX509ValidCN()) . " tried to access the NREN-area");
			$this->tpl->assign('reason', 'You are not an NREN-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}

		$this->tpl->assign('nren_maint_msg', $this->person->getNREN()->getMaintMsg());

		/* set maint-mode msg */
		if (($this->person->getNREN()->getMaintMode() === "y"))
			$this->tpl->assign('maint_mode_msg', $this->translateTag('l10n_nren_maint_mode_enabled', 'portal_config'));
		else
			$this->tpl->assign('maint_mode_msg', $this->translateTag('l10n_nren_maint_mode_disabled', 'portal_config'));

		/* set the radio-buttons */
		$this->tpl->assign('maint_mode_v', array('y', 'n'));
		$this->tpl->assign('maint_mode_t', array(' enabled', ' disabled'));
		$this->tpl->assign('maint_mode_selected', $this->person->getNREN()->getMaintMode());
		$this->tpl->assign('maint_mode', $this->person->getNREN()->getMaintMode() === 'y');
		$this->tpl->assign('content', $this->tpl->fetch('portal_config.tpl'));
	}

	private function handleMaintMode()
	{
		if (array_key_exists("nren_maint_mode", $_POST)) {
			$mode = $_POST['nren_maint_mode'];
			unset($_POST['nren_maint_mode']);
			if ($this->person->getNREN()->setMaintMode(Input::sanitizeMaintMode($mode))) {
				if ($this->person->getNREN()->getMaintMode() === 'y')
					Framework::success_output($this->translateTag('l10n_nren_maint_mode_success', 'portal_config'));
				else
					Framework::success_output($this->translateTag('l10n_nren_maint_normal_success', 'portal_config'));
			} else {
				if ($this->person->getNREN()->getMaintMode() === 'y')
					Framework::error_output($this->translateTag('l10n_nren_maint_mode_failure', 'portal_config'));
				else
					Framework::error_output($this->translateTag('l10n_nren_maint_normal_failure', 'portal_config'));

			}
		}
	}

	private function handleMaintText()
	{
		if (array_key_exists("nren_maint_msg", $_POST)) {
			if ($this->person->getNREN()->setMaintMsg($this->person, $_POST['nren_maint_msg'])) {
				Framework::success_output($this->translateTag("l10n_nren_maint_msg_success", 'portal_config'));
			} else {
				Framework::error_output($this->translateTag("l10n_nren_maint_msg_failure", 'portal_config'));
			}
		}
	}

} /* end CP_Portal_Config */

$fw = new Framework(new CP_Portal_Config());
$fw->start();
?>
