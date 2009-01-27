<?php

include_once('framework.php');
include_once('debug.php');

$fw = new Framework('welcome');
$fw->render_page();

function welcome($person) 
{
	Debug::dump($person);
     include('unclassified_intro.php');
}
?>

