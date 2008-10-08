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
     require_once('mdb2_wrapper.php');
     $res = MDB2Wrapper::execute("SELECT news, issued FROM news WHERE newsid > ? ORDER BY issued DESC", array('integer'), array(0));
     if (count($res > 0)) {
          echo "<h3>News</h3>\n";
          foreach ($res as $key => $line) {
               echo "<P>\n";
               echo "<ADDRESS>".htmlentities($line['issued'])."</ADDRESS>\n";
               echo htmlentities($line['news'])."\n";
               echo "</P>\n";
          }
     }
}
?>

