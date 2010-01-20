<?php
require_once 'confusa_include.php';
require_once 'config.php';
require_once 'input.php';
require_once 'confusa_constants.php';

/*
 * Get the custom NREN logo from the filesystem and return it as an image
 */

$nren = Input::sanitize($_GET['nren']);
$position = Input::sanitize($_GET['pos']);
$suffix = Input::sanitize($_GET['suffix']);

if (isset($nren)) {
	$logo_path = Config::get_config('custom_logo') . $nren . '/custom_' . $position . '.';
	$logo_path .= $suffix;

	/*
	 * Search if there is one custom.png, custom.jpg or custom.any_other_
	 * allowed_suffix file in the custom-logo folder.
	 *
	 * If there isn't return null
	 */
	if (file_exists($logo_path)) {
		$fp = fopen($logo_path, "r");
		$image = fread($fp, filesize($logo_path));
		fclose($fp);

		header("Content-type: image/$suffix");
		echo $image;

	} else {
		exit(1);
	}
} else {
	exit(1);
}




?>
