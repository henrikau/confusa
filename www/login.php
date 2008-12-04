<?php

include_once('framework.php');
include_once('confusa_auth.php');

$fw = new Framework('login');
$fw->render_page();


function login($person) {
     if ($person->is_auth()) {
          succsess($person);
     }
     else {
          /* saml2_login(); */
          compose_login_links();
     }
}    /* end login */

function succsess($person)
{
         echo "<H3>Success!</H3>\n";
         echo "<BR>\n";
         echo "You are successfully logged into confusa<BR><BR>\n";
         echo $person;

         echo "<BR>\n";
         /* Show number of CSRs and Certs awaiting with link to keys */
         echo "<A HREF=\"key_handler.php\">Keys</A><BR>\n";
 } /* end success */
