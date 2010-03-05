<?php
require_once 'confusa_include.php';
require_once 'Config.php';
require_once 'input.php';
require_once 'confusa_constants.php';

/*
 * Get the custom NREN logo from the filesystem and return it as an image
 */


if (isset($_GET['nren'])) {
	$nren = Input::sanitize($_GET['nren']);
	$position = Input::sanitize($_GET['pos']);
	$suffix = Input::sanitize($_GET['suffix']);

	$logo_path = Config::get_config('custom_logo') . $nren . '/custom_' . $position . '.';
	$logo_path .= $suffix;
} else if (isset($_GET['op'])) {
	$logo_path = Config::get_config('operator_logo');
	$suffix = substr($logo_path, strlen($logo_path)-3, strlen($logo_path)-1);
} else {
	exit(1);
}

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

?>
