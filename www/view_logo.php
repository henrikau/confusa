<?php
require_once 'confusa_include.php';
require_once 'config.php';
require_once 'input.php';
require_once 'confusa_constants.php';

/*
 * Get the custom NREN logo from the filesystem and return it as an image
 */

$nren = Input::sanitize($_GET['nren']);
$logo_path = Config::get_config('custom_logo') . $nren . '/custom.';

/*
 * Search if there is one custom.png, custom.jpg or custom.any_other_
 * allowed_suffix file in the custom-logo folder.
 *
 * If there isn't return null
 */
foreach(ConfusaConstants::$ALLOWED_IMG_SUFFIXES as $suffix) {
	if (file_exists($logo_path . $suffix)) {
		$logo_suffix = $suffix;
		break;
	}
}

if (empty($logo_suffix)) {
	echo "";
} else {
	$logo_name = $logo_path . $logo_suffix;
	$fp = fopen($logo_name, "r");
	$image = fread($fp, filesize($logo_name));
	fclose($fp);

	header("Content-type: image/$logo_suffix");
	echo $image;
}


?>
