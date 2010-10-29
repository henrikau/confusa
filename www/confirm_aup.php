<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'confusa_constants.php';

final class CP_Confirm_AUP extends Content_Page
{
	function __construct()
	{
		parent::__construct("Confirm AUP", true, "processcsr");
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		$this->tpl->assign('extraScripts', array('js/jquery-1.4.1.min.js'));
		$this->tpl->assign('rawScript', file_get_contents('../include/rawToggleExpand.js'));

		if (isset($_POST['aup_box']) && ($_POST['aup_box']) == "user_agreed") {
			CS::setSessionKey('hasAcceptedAUP', true);

			/* FIXME: these pages should not be hardcoded... */
			if ($this->person->getNREN()->getEnableEmail() == "0") {
				header("Location: receive_csr.php");
			} else {
				/* redirect user further to the actual request process */
				header("Location: select_email.php");
			}
		} else {
			/* user will have to reacknowledge AUP */
			if (CS::getSessionKey('hasAcceptedAUP') === true) {
				CS::deleteSessionKey('hasAcceptedAUP');
			}
		}
	}

	public function process()
	{
		if (Config::get_config('cert_product') == PRD_PERSONAL) {
			$this->tpl->assign('cps', ConfusaConstants::$LINK_PERSONAL_CPS);
		} else {
			$this->tpl->assign('cps', ConfusaConstants::$LINK_ESCIENCE_CPS);
		}

		$this->tpl->assign('privacy_notice_text', $this->person->getNREN()->getPrivacyNotice($this->person));
		$this->tpl->assign('content', $this->tpl->fetch('confirm_aup.tpl'));
	}
}

$fw = new Framework(new CP_Confirm_AUP());
$fw->start();
?>