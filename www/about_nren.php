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
		$logo = "view_logo.php?nren=" . $this->person->getNREN();
		$this->tpl->assign('logo', $logo);
		$about_text = $this->getAboutTextForNREN($this->person->getNREN());
		/* now do some manual templating on the about-text. This is surely faster
		 * than everything that can be done with smarty when one has a PHP string */
		$about_text = str_ireplace('{$subscriber}', $this->person->getSubscriber()->getOrgName(), $about_text);

		if (Config::get_config('cert_product') == PRD_ESCIENCE) {
			$productName = ConfusaConstants::$ESCIENCE_PRODUCT;
		} else {
			$productName = ConfusaConstants::$PERSONAL_PRODUCT;
		}

		$about_text = str_ireplace('{$product_name}', $productName, $about_text);
		$about_text = str_ireplace('{$confusa_url}', Config::get_config('server_url'), $about_text);
		$about_text = str_ireplace('{$subscriber_support_email}',
		              $this->person->getSubscriber()->getHelpEmail(), $about_text);
		$about_text = str_ireplace('{$subscriber_support_url}',
		              $this->person->getSubscriber()->getHelpURL(), $about_text);
		$this->tpl->assign('subscriber', htmlentities($this->person->getSubscriber()->getOrgName()));
		$this->tpl->assign('text_info', $about_text);
		$this->tpl->assign('content', $this->tpl->fetch('about_nren.tpl'));
	}

	/*
	 * Get the about-text for a certain NREN, so it can be displayed in Confusa's
	 * about-section
	 */
	private function getAboutTextForNREN($nren)
	{
		$query = "SELECT about FROM nrens WHERE name = ?";

		try {
			$res = MDB2Wrapper::execute($query,
										array('text'),
										array($nren));
		} catch (DBStatementException $dbse) {
			Framework::error_output($this->translateMessageTag('abt_err_dbstat') . " " .
			                        htmlentities($dbse->getMessage()));
			return "";
		} catch (DBQueryException $dbqe) {
			Framework::error_output($this->translateMessageTag('abt_err_dbquery') .  " " .
			                        htmlentities($nren));
			return "";
		}

		if (count($res) > 0) {
			$textile = new Textile();
			return $textile->TextileRestricted(Input::br2nl(stripslashes($res[0]['about'])),0);
		} else {
			return "";
		}
	}

}

$fw = new Framework(new CP_About_NREN());
$fw->start();

?>
