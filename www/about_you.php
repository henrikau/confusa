<?php
require_once 'confusa_include.php';
require_once 'framework.php';

class CP_AboutYou extends FW_Content_Page
{
	function __construct()
	{
		parent::__construct("About You", true);
	}

	public function process()
	{
		$textual = "no";
		if (isset($_GET['text'])) {
			$textual = htmlentities($_GET['text']);
		}
		$this->tpl->assign('timeSinceStart', $this->person->getTimeSinceStart());
		$this->tpl->assign('timeLeft', $this->person->getTimeLeft());
		$this->tpl->assign('textual', $textual);
		$this->tpl->assign('content', $this->tpl->fetch('about_you.tpl'));
	}
}

$fw = new Framework(new CP_AboutYou());
$fw->start();

?>
