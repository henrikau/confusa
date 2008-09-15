<?php

include_once('framework.php');

$fw = new Framework('welcome');
$fw->render_page();

function welcome($person) 
{
     require_once('mdb2_wrapper.php');
     echo "<h3>News</h3>\n";
     $val = 0;
     $res = MDB2Wrapper::execute("SELECT news, issued FROM news WHERE newsid>?", array('integer'), array($val));

     foreach ($res as $key => $line) {
          echo "<P>\n";
          echo "<ADDRESS>".htmlentities($line['issued'])."</ADDRESS>\n";
          echo htmlentities($line['news'])."\n";
          echo "</P>\n";
     }
     if ($person->is_auth())
     {
          echo "This is the information we have received about you: <BR>\n";
          echo $person . "<BR>\n";
     }
     include('unclassified_intro.php');
}
?>

