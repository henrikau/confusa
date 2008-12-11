<?php

include_once('framework.php');

$fw = new Framework('welcome');
$fw->render_page();

function welcome($person) 
{
     include('unclassified_intro.php');
}
?>

