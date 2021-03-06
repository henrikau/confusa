<?php
$path = ini_get('include_path');
$path = $path . PATH_SEPARATOR;//  . $path_extra;


/* Admin status */
define("NREN_ADMIN", 2);
define("SUBSCRIBER_ADMIN", 1);
define("SUBSCRIBER_SUB_ADMIN", 0);
define("NORMAL_USER", -1);

/* Page-view modes */
define("NORMAL_MODE", 0);
define("ADMIN_MODE", 1);

/* CertManager enums */
define("CA_STANDALONE", 0);
define("CA_ONLINE", 1);

define('WEB_DIR', dirname($_SERVER['SCRIPT_FILENAME']));
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/lib';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/programs';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/config';
$path .= PATH_SEPARATOR . dirname(WEB_DIR) . '/include';

/* echo __FILE__ . ": -> path: " . $path . "<br>\n"; */
ini_set('include_path', $path);

/* include lib */
require_once 'lib_include.php';

?>
