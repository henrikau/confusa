<?php

$webdir = dirname(__FILE__);
$path = ini_get('include_path');
$path .= PATH_SEPARATOR . $webdir;
$path .= PATH_SEPARATOR . $webdir . '/mocks';
$path .= PATH_SEPARATOR . dirname($webdir) . '/extlibs/simpletest';
$path = $path . PATH_SEPARATOR;
ini_set('include_path', $path);

define("PRD_ESCIENCE", 0);
define("PRD_PERSONAL", 1);


?>
