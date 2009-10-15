<?php
$path = ini_get('include_path');
define('LIB_DIR', dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/lib');

$path .= PATH_SEPARATOR . LIB_DIR . '/auth';
$path .= PATH_SEPARATOR . LIB_DIR . '/exceptions';
$path .= PATH_SEPARATOR . LIB_DIR . '/file';
$path .= PATH_SEPARATOR . LIB_DIR . '/key';
$path .= PATH_SEPARATOR . LIB_DIR . '/misc';
$path .= PATH_SEPARATOR . LIB_DIR . '/actors';
$path .= PATH_SEPARATOR . LIB_DIR . '/framework';
$path .= PATH_SEPARATOR . LIB_DIR . '/input';
$path .= PATH_SEPARATOR . LIB_DIR . '/robot';

if (file_exists('/etc/confusa/confusa_config.php') === true) {
    $path .= PATH_SEPARATOR . '/etc/confusa';
}

ini_set('include_path', $path);



?>
