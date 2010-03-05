<?php
$path = ini_get('include_path');
define('LIB_DIR', dirname(__FILE__));

$path .= PATH_SEPARATOR . LIB_DIR . '/actors';
$path .= PATH_SEPARATOR . LIB_DIR . '/auth';
$path .= PATH_SEPARATOR . LIB_DIR . '/ca';
$path .= PATH_SEPARATOR . LIB_DIR . '/config';
$path .= PATH_SEPARATOR . LIB_DIR . '/db';
$path .= PATH_SEPARATOR . LIB_DIR . '/exceptions';
$path .= PATH_SEPARATOR . LIB_DIR . '/file';
$path .= PATH_SEPARATOR . LIB_DIR . '/framework';
$path .= PATH_SEPARATOR . LIB_DIR . '/input';
$path .= PATH_SEPARATOR . LIB_DIR . '/io';
$path .= PATH_SEPARATOR . LIB_DIR . '/misc';
$path .= PATH_SEPARATOR . LIB_DIR . '/mail';
$path .= PATH_SEPARATOR . LIB_DIR . '/robot';

if (file_exists('/etc/confusa/confusa_config.php') === true) {
    $path .= PATH_SEPARATOR . '/etc/confusa';
}

ini_set('include_path', $path);



?>
