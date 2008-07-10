<?php

include_once('framework.php');

$fw = new Framework('welcome');
$fw->render_page();

function welcome($person) 
{
    if ($person->is_auth())
        {
             echo "This is the information we have received about you: <BR>\n";
             echo $person . "<BR>\n";
        }
    include('unclassified_intro.php');
}
?>

