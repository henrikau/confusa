<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
include_once 'Framework.php';
include_once 'Logger.php';
require_once 'Output.php';
require_once 'pw.php';

final class CP_Index extends Content_Page
{

	function __construct()
	{
		parent::__construct("Index", false, "index");
	}
	function pre_process($person)
	{
		parent::pre_process($person);
		$this->tpl->assign('extraScripts', array('js/jquery-1.6.min.js'));
		$this->tpl->assign('rawScript', file_get_contents('../include/rawToggleExpand.js'));
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
		if ($this->person->isAuth()) {
			$this->tpl->assign('subjectDN', $this->ca->getFullDN());
		}

		$this->tpl->assign('content',
				   $this->tpl->fetch('index.tpl'));
	} /* end process() */
}

$fw  = new Framework(new CP_Index());
$fw->start();
unset($fw);

?>
