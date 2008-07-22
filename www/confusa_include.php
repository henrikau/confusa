<?php
$path = ini_get('include_path');
$path = $path . PATH_SEPARATOR . $path_extra;

define('WEB_DIR', dirname($_SERVER['SCRIPT_FILENAME']));
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/lib';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/lib/exceptions';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/programs';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/config';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/include';

/* echo __FILE__ . ": -> path: " . $path . "<br>\n"; */
ini_set('include_path', $path);



?>
