<?php
include_once('framework.php');
$fw = new Framework('admin');
$fw->force_login();
$fw->render_page();

function admin($person) 
{
     if ($person->is_auth() && $person->is_admin()) {
          echo "You are admin!<BR>\n";
          echo "You are eligible to add news-items<BR>\n";
          
     }
          
}

?>