<?php
require_once 'confusa_config.php';
require_once 'confusa_gen.php';


/**
 * MetaInfo - or Confua all about itself
 */
class MetaInfo
{

	/**
	 * Get the version of the currently running Confusa instance.
	 *
	 * @return Version in the format major.minor.extra
	 * @throws ConfusaGenException if the version of Confusa can not be determined
	 */
	public static function getConfusaVersion()
	{
		$version_file = file_get_contents(Config::get_config('install_path') .
		                                  '/VERSION');

		$major_v_line_start=strpos($version_file, "MAJOR_VERSION=");
		$major_v_line_end = strpos($version_file, "\n", $major_v_line_start);

		if ($major_v_line_start === false || $major_v_line_end === false) {
			throw new ConfusaGenException("Could not determine the major version of Confusa!" .
			                              " Please contact an administrator about that!");
		}

		$major_v_line_start += 14;
		$major_version = substr($version_file, $major_v_line_start,
		                       ($major_v_line_end - $major_v_line_start));


		$minor_v_line_start=strpos($version_file, "MINOR_VERSION=");
		$minor_v_line_end = strpos($version_file, "\n", $minor_v_line_start);

		if ($minor_v_line_start === false || $minor_v_line_end === false) {
			throw new ConfusaGenException("Could not determine the minor version of Confusa!" .
			                             " Please contact an administrator about that!");
		}

		$minor_v_line_start += 14;
		$minor_version = substr($version_file, $minor_v_line_start,
		                       ($minor_v_line_end - $minor_v_line_start));

		$extra_v_line_start=strpos($version_file, "EXTRA_VERSION=");
		$extra_v_line_end = strpos($version_file, "\n", $extra_v_line_start);

		if ($extra_v_line_start === false || $extra_v_line_end === false) {
			throw new ConfusaGenException("Could not determine the extra version of Confusa!" .
			                              " Please contact an administrator about that!");
		}

		$extra_v_line_start += 14;
		$extra_version = substr($version_file, $extra_v_line_start,
		                       ($extra_v_line_end - $extra_v_line_start));

		$confusaVersion = $major_version . "." . $minor_version . "." . $extra_version;

		return $confusaVersion;
	}
}
?>
