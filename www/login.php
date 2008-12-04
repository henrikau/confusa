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
          shib13_login();
          
     }
}    /* end login */

function saml2_login()
{
     unset($metadata);
     echo "<BR>\n<B>SAMLv2 hosts</B><BR>\n";
     include(Config::get_config('saml2_path') . "/metadata/saml20-idp-remote.php");


     $protocol = "http://";
     if ($_SERVER['HTTPS'] == "on")
          $protocol = "https://";

     $server            = $protocol . $_SERVER['HTTP_HOST'];
     $saml2_server      = $server . Config::get_config('www_saml2') . "saml2/sp/";
     $relay_state        = urlencode($server . $_SERVER['HTTP_REFREER'] . "/" . $_SERVER['PHP_SELF']);

     $disco_path        = $saml2_server . "idpdisco.php";
     $metadata_path     = $saml2_server . "metadata.php";
     $sso_path          = $saml2_server . "initSSO.php";


     foreach ($metadata as $key => $value) {
          $url = "$sso_path?RelayState=$relay_state&idpentityid=$key";
          echo "<A HREF=\"$url\">". $value['name']  ."</A><BR>\n";
     }

}

function shib13_login()
{
     
     unset($metadata);
     include(Config::get_config('saml2_path') . "/metadata/shib13-idp-remote.php");
     $shib13 = $metadata;
     echo "<BR>\n<B>Shibboleth v1.3 IdPs</B><BR>\n";
     echo "<I>Note</I> - this is not implemented fully yet!<BR>\n";
     foreach ($metadata as $index => $idp) {
          echo "[ <A HREF=\"error\">".$idp['name']."</A> ]<BR>\n";
     }

}

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
