<?php
require_once 'confusa_include.php';
require_once 'Config.php';
require_once 'input.php';

$nren = Input::sanitizeNRENName($_GET['nren']);
$css_path = Config::get_config('custom_css') . $nren . '/custom.css';

if (file_exists($css_path)) {
	$fp = fopen($css_path, "r");
	$css = fread($fp, filesize($css_path));
	fclose($fp);

	header("Content-type: text/css");
	echo $css;
} else {
	echo "";
}

?>
