<?php 
require_once('confusa_include.php');
include_once('framework.php');
include_once('logger.php');
require_once("output.php");
require_once("pw.php");

final class Index extends FW_Content_Page
{

	function __construct()
	{
		parent::__construct("Index", false);
	}

	function __destruct()
	{
		decho(__FILE__ . " aiieee, dying");
	}
	public function pre_process($person)
	{
		$this->setPerson($person);
		$this->setManager();
		return false;
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
	function process($person)
	{
		if ($person->is_auth()) {
			switch($person->get_mode()) {
			case NORMAL_MODE:
				echo "Showing normal-mode splash<BR>\n";
				break;
			case ADMIN_MODE:
				echo "Showing admin-mode splash<BR>\n";
				break;
			default:
				$code = create_pw(8);
				error_output("Unknown mode, contact the administrator with this error code " . $code);
				$msg  = $code . " ";
				$msg .= "User " . $person->get_common_name() . " was given mode " . $person->get_mode();
				$msg .= ". This is not a valid mode. Verify content in admins-table";
				Logger::log_event(LOG_WARNING, $msg);
			}
		} else {
			include('unclassified_intro.php');
		}
	} /* end process() */

	public function post_render($person)
	{
		decho(__FILE__ . " Cleaning up..");
	}
}

$ind = new Index();
$fw  = new Framework($ind);
$fw->start();
unset($fw);
unset($ind);

?>
