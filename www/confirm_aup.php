<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'confusa_constants.php';

/**
 * Class asking user about whether they agree to AUP. Sets session key upon
 * user agreement. Subsequent scripts in the certificate request pipeline
 * should check this session key before allowing any further actions.
 * @author tzangerl
 * @since v0.7-rc0
 *
 */
final class CP_Confirm_AUP extends Content_Page
{
	function __construct()
	{
		parent::__construct("Confirm AUP", true, "processcsr");
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		$this->tpl->assign('extraScripts', array('js/jquery-1.6.1.min.js'));
		$this->tpl->assign('rawScript', file_get_contents('../include/rawToggleExpand.js'));

		/* need to confirm AUP only once per session */
		if ((isset($_POST['aup_box']) &&
			($_POST['aup_box']) == "user_agreed")) {
			CS::setSessionKey('hasAcceptedAUP', true);

			header("Location: select_email.php");
		}
	}

	public function process()
	{
		if (Config::get_config('cert_product') == PRD_PERSONAL) {
			$this->tpl->assign('cps', ConfusaConstants::$LINK_PERSONAL_CPS);
		} else {
			$this->tpl->assign('cps', ConfusaConstants::$LINK_ESCIENCE_CPS);
		}

		Logger::log_event(LOG_INFO, "User acknowledged session: " . CS::getSessionKey('hasAcceptedAUP'));
		$this->tpl->assign('aup_session_state', CS::getSessionKey('hasAcceptedAUP'));
		$this->tpl->assign('privacy_notice_text', $this->person->getNREN()->getPrivacyNotice($this->person));
		$this->tpl->assign('content', $this->tpl->fetch('confirm_aup.tpl'));
	}
}

$fw = new Framework(new CP_Confirm_AUP());
$fw->start();
?>