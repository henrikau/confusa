<?php
require_once 'confusa_include.php';
include_once 'framework.php';
include_once 'logger.php';
require_once 'output.php';
require_once 'pw.php';
require_once 'content_page.php';

final class CP_Index extends Content_Page
{

	function __construct()
	{
		parent::__construct("Index", false, "index");
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
		$this->tpl->assign('content',
				   $this->tpl->fetch('index.tpl'));
	} /* end process() */
}

$fw  = new Framework(new CP_Index());
$fw->start();
unset($fw);

?>
