<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'Person.php';
require_once 'classTextile.php';

class CP_About_NREN extends Content_Page
{
	function __construct()
	{
		parent::__construct("About NREN", false, "index");
	}


	public function process()
	{
		$nren = $this->person->getNREN();
		if (!isset($nren)) {
			$this->tpl->assign('content', $this->tpl->fetch('unclassified_intro.tpl'));
			return;
		}

		$aboutText = $nren->getAboutText($this->person);
		if (isset($aboutText)) {
			$this->tpl->assign('text_info', $aboutText);
		} else {
			$this->tpl->assign('nren_unset_about_text',
							   $this->translateTag('nren_unset_about_text', 'index'));
			$this->tpl->assign('nren_contact_email', $nren->getContactEmail(true));
		}
		$this->tpl->assign('content', $this->tpl->fetch('about_nren.tpl'));
	} /* end process() */
}

$fw = new Framework(new CP_About_NREN());
$fw->start();

?>
