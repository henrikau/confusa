<?php
$path = ini_get('include_path');
define('LIB_DIR', dirname(__FILE__));
define('MODULES_DIR', dirname(LIB_DIR) . '/modules');

$path .= PATH_SEPARATOR . LIB_DIR . '/auth';
$path .= PATH_SEPARATOR . LIB_DIR . '/exceptions';
$path .= PATH_SEPARATOR . LIB_DIR . '/file';
$path .= PATH_SEPARATOR . LIB_DIR . '/ca';
$path .= PATH_SEPARATOR . LIB_DIR . '/misc';
$path .= PATH_SEPARATOR . LIB_DIR . '/actors';
$path .= PATH_SEPARATOR . LIB_DIR . '/framework';
$path .= PATH_SEPARATOR . LIB_DIR . '/input';
$path .= PATH_SEPARATOR . LIB_DIR . '/robot';
$path .= PATH_SEPARATOR . MODULES_DIR . '/auth';

if (file_exists('/etc/confusa/confusa_config.php') === true) {
    $path .= PATH_SEPARATOR . '/etc/confusa';
}

ini_set('include_path', $path);



?>
