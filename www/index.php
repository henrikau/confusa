<?php 
require_once 'confusa_include.php';
include_once 'framework.php';
include_once 'logger.php';
require_once 'output.php';
require_once 'pw.php';

final class Index extends FW_Content_Page
{

	function __construct()
	{
		parent::__construct("Index", false);
	}

	/**
	 * process - main control function for handling CSRs and certificates
	 *
	 * It will make sure all CSRs and Certificates stored in the database will be
	 * processed and displayed to the user properly.
	 *
	 * @person : the person-object associated with this instance. If the person is
	 *	     non-AuthN, a unclassified version will be displayed.
	 */
	function process()
	{
		if ($this->person->is_auth()) {
			if ($this->person->get_mode() > 1) {
				$code = create_pw(8);
				Framework::error_output("Unknown mode, contact the administrator with this error code " . $code);
				$msg  = $code . " ";
				$msg .= "User " . $this->person->get_common_name() . " was given mode " . $this->person->get_mode();
				$msg .= ". This is not a valid mode. Verify content in admins-table";
				Logger::log_event(LOG_WARNING, $msg);
			}
		}
		$this->tpl->assign('content', $this->tpl->fetch('index.tpl'));
	} /* end process() */
}

$ind = new Index();
$fw  = new Framework($ind);
$fw->start();
unset($fw);
unset($ind);

?>
