<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
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

		if (isset($nren)) {
			$aboutText = $nren->getAboutText($this->person);
		} else {
			$this->tpl->assign('content', $this->tpl->fetch('about_nren.tpl'));
			return;
		}

		$this->tpl->assign('text_info', $aboutText);
		$this->tpl->assign('content', $this->tpl->fetch('about_nren.tpl'));
	}

}

$fw = new Framework(new CP_About_NREN());
$fw->start();

?>
