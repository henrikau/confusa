<?php

include_once('framework.php');
require_once('SimpleSAML/Utilities.php');

$fw = new Framework('test');
$fw->render_page();


/*
  If you are using the builtin IdP discovery page, a bit more work is 
  required, since you don't know which SP entity id should used before 
  the user has chosen an IdP. You can work around this by creating a 
  a PHP-page which takes the result of the discovery service, and chooses 
  the SP entity id based on that. It should then redirect to the 
  initSSO-script with the correct spentityid and idpentityid parameters.

  When you need authentication, you can then redirect to 
  saml2/sp/idpdisco.php with the return-url set to this page. The 
  idpdisco.php-script has three required parameters:
  - return: The URL the user should be redirected to after choosing an 
  IdP.
  - returnIDParam: The name of the parameter which will contain the 
  entity id of the selected IdP.
  - entityID: This is supposed to be the entity id of the SP, but it 
  won't be used for anything. You must however include it.

  Example URL:
  .../saml2/sp/idpdisco.php?entityID&return=/page.php&returnIDParam=idpentityid
 */

function test($person)
{

     echo "<a href=\"".dirname(SimpleSAML_Utilities::selfURL())."/test.php\">test</A><br>\n";
     if (isset($_GET['entityid']))
          redir(htmlentities($_GET['entityid']));

     global $metadata;
     require_once('/var/www/simplesamlphp/metadata/saml20-sp-hosted.php');

     foreach ($metadata as $key => $line) 
          echo "<A HREF=\"" . SimpleSAML_Utilities::selfURL() . "?entityid=".urlencode($key)."\">".$key."</A><br>\n";
}


function redir($entityid)
{
        /*
         * https://slcstest.uninett.no/slcsweb/saml2/sp/idpdisco.php?
         * entityID=urn%3Amace%3Afeide.no%3Aservices%3Ano.uninett.slcsweb&
         * return=https%3A%2F%2Fslcstest.uninett.no%2Fslcsweb%2Fsaml2%2Fsp%2FinitSSO.php%3FRelayState%3Dhttps%253A%252F%252Fslcstest.uninett.no%252Fslcsweb%252Findex.php%253Fstart_login%253Dyes&
         * returnIDParam=idpentityid 
         */
     /* get full url */
     $full_url=SimpleSAML_Utilities::selfURL();
     $base_url = dirname($full_url);

     $discourl = "$base_url/saml2/sp/idpdisco.php";
     $spentityid=$entityid;
     $return=urlencode("$base_url/saml2/sp/initSSO.php?RelayState=$full_url");

     /* redirect */
     /* SimpleSAML_Utilities::redirect($discourl, array( */
     /*                                     'entityID' => $spentityid, */
     /*                                     'return' => SimpleSAML_Utilities::selfURL(), */
     /*                                     'returnIDParam' => 'idpentityid') */
     /* ); */
     SimpleSAML_Utilities::redirect('/slcsweb/saml2/sp/idpdisco.php',array('entityID' => $spentityid,'return' => $return,'returnIDParam' => 'idpentityid'));
     /* SimpleSAML_Utilities::redirect($discourl, array( */
     /*                                     'entityID' => $spentityid, */
     /*                                     'return' => $return, */
     /*                                     'returnIDParam' => 'idpentityid')); */

}
?>