<?php
$path = ini_get('include_path');
define('MODULES_DIR', dirname(__FILE__));

$path .= PATH_SEPARATOR . MODULES_DIR . '/auth';

ini_set('include_path', $path);
?>
