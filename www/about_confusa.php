<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'framework.php';
require_once 'Config.php';
include_once 'MDB2Wrapper.php';
require_once 'metainfo.php';

class CP_About_Confusa extends Content_Page
{
	function __construct()
	{
		parent::__construct("About Confusa", false, "credits");
	}

	function process()
	{
		/*
		 * Only show this to admins, normal users have no need for this information.
		 */
		if ($this->person->isAdmin()) {
			$this->assignVersionVariables();

			if (Config::get_config('debug') === true) {
				$this->tpl->assign('debug', true);
				$this->assignDebugVariables();
			}
		}

		$operator_logo = Config::get_config('operator_logo');
		if (Config::get_config('operator_show_logo') === true && file_exists($operator_logo)) {
			$operator_url = Config::get_config('operator_url');
			$op_creds = "<a href=\"$operator_url\" target=\"_blank\"><img src=\"view_logo.php?op=yes\" alt=\"Operator logo\" /></a>";
			$this->tpl->assign("op_creds", $op_creds);
		}

		$this->tpl->assign('content', $this->tpl->fetch('about_confusa.tpl'));
	}

	/**
	 * Decorate the about::confusa template with the information from the
	 * VERSION file
	 */
	private function assignVersionVariables()
	{
		try {
			$confusaVersion = MetaInfo::getConfusaVersion();
		} catch (ConfusaGenException $cge) {
			Framework::error_output("Could not determine the version of Confusa! " .
			                        "Please contact an administrator about that!");
		}

		$version_path = Config::get_config('install_path') . "VERSION";
		$version_file = file_get_contents($version_path);

		$this->tpl->assign('cVersion', $confusaVersion);

		$cdn_line_start = strpos($version_file, "NAME=");
		$cdn_line_end = strpos($version_file, "\n", $cdn_line_start);

		if ($cdn_line_start === false || $cdn_line_end === false) {
			Framework::error_output("Could not determine the version codename of " .
			                        "Confusa! Please contact an administrator about " .
			                        "that!");
		}

		$cdn_line_start += 5;
		$versionCodename = substr($version_file, $cdn_line_start,
		                          ($cdn_line_end - $cdn_line_start));
		$this->tpl->assign('cCodename', $versionCodename);
	}

	/**
	 * Assign information that can be helpful for debugging Confusa to the
	 * about template. This might be the PHP version and other information.
	 */
	private function assignDebugVariables()
	{
		$phpVersion = phpversion();
		$this->tpl->assign('dPHPVersion', $phpVersion);
		$hostname = $_SERVER['SERVER_NAME'];
		$this->tpl->assign('dHostname', $hostname);

		$mysqlVersion = mysql_get_server_info();
		$this->tpl->assign('dMySQLVersion', $mysqlVersion);
	}
}

$fw = new Framework(new CP_About_Confusa());
$fw->start();
?>
