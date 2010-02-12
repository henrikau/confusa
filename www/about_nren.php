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
		if ($this->person->isAuth()) {
			$about_text = $this->person->getNREN()->getAboutText($this->person);
			$this->tpl->assign('text_info', $about_text);
		}

		$this->tpl->assign('content', $this->tpl->fetch('about_nren.tpl'));
	}

}

$fw = new Framework(new CP_About_NREN());
$fw->start();

?>
