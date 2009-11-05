<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'config.php';
include_once 'mdb2_wrapper.php';

class CP_About_Confusa extends Content_Page
{
	function __construct()
	{
		parent::__construct("About Confusa", false);
	}

	function process()
	{
		$this->assignVersionVariables();
		$this->assignDBVariables();

		if (Config::get_config('debug') === true) {
			$this->tpl->assign('debug', true);
			$this->assignDebugVariables();
		}

		$this->tpl->assign('content', $this->tpl->fetch('about_confusa.tpl'));
	}

	/**
	 * Decorate the about::confusa template with the information from the
	 * VERSION file
	 */
	private function assignVersionVariables()
	{
		$version_file = file_get_contents(Config::get_config('install_path') .
		                                  '/VERSION');

		$major_v_line_start=strpos($version_file, "MAJOR_VERSION=");
		$major_v_line_end = strpos($version_file, "\n", $major_v_line_start);

		if ($major_v_line_start === false || $major_v_line_end === false) {
			Framework::error_output("Could not determine the major version of Confusa!" .
			                        " Please contact an administrator about that!");
		}

		$major_v_line_start += 14;
		$major_version = substr($version_file, $major_v_line_start,
		                       ($major_v_line_end - $major_v_line_start));


		$minor_v_line_start=strpos($version_file, "MINOR_VERSION=");
		$minor_v_line_end = strpos($version_file, "\n", $minor_v_line_start);

		if ($minor_v_line_start === false || $minor_v_line_end === false) {
			Framework::error_output("Could not determine the minor version of Confusa!" .
			                        " Please contact an administrator about that!");
		}

		$minor_v_line_start += 14;
		$minor_version = substr($version_file, $minor_v_line_start,
		                       ($minor_v_line_end - $minor_v_line_start));

		$extra_v_line_start=strpos($version_file, "EXTRA_VERSION=");
		$extra_v_line_end = strpos($version_file, "\n", $extra_v_line_start);

		if ($extra_v_line_start === false || $extra_v_line_end === false) {
			Framework::error_output("Could not determine the extra version of Confusa!" .
			                        " Please contact an administrator about that!");
		}

		$extra_v_line_start += 14;
		$extra_version = substr($version_file, $extra_v_line_start,
		                       ($extra_v_line_end - $extra_v_line_start));

		$confusaVersion = $major_version . "." . $minor_version . "." . $extra_version;
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

		$db_v_line_start = strpos($version_file, "DB_SCHEMA_VERSION=");
		$db_v_line_end = strpos($version_file, "\n", $db_v_line_start);

		if ($db_v_line_start === false || $db_v_line_end === false) {
			Framework::error_output("Could not determine the expected DB-schema version " .
			                        "of Confusa! Please contact an administrator about " .
			                        "that!");
		}

		$db_v_line_start += 18;
		$dbVersion = substr($version_file, $db_v_line_start,
		                    ($db_v_line_end - $db_v_line_start));

		$this->tpl->assign('cExpSchema', $dbVersion);
	}

	/**
	 * Assign Confusa status variables that can be retrieved from the database
	 * to the about_confusa template.
	 * One example of this is the DB schema version
	 */
	private function assignDBVariables()
	{
		$query = "SELECT version FROM schema_version";

		try {
			$res = MDB2Wrapper::execute($query,
			                            null,
			                            null);
		} catch (ConfusaGenException $cge) {
			Framework::error_output("Could not get the actual DB-schema version " .
			                        "of Confusa. Please contact an administrator " .
			                        "that!");
			return;
		}

		if (isset($res[0]['version'])) {
			$this->tpl->assign('cFoundSchema', $res[0]['version']);
		} else {
			$this->tpl->assign('cFoundSchema', "none found!");
		}
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
