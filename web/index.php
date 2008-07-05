<?php

include_once('framework.php');

$fw = new Framework('welcome');
$fw->render_page();

function welcome($person) 
{
    if ($person->is_auth())
        {
        /* from ../text/ */
        include('classified_intro.php');
        }
    /* from ../text/ */
    include('unclassified_intro.php');
}
?>

