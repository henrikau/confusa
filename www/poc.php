<?php
require_once('confusa_include.php');
include_once('framework.php');

$fw = new Framework('poc');
$fw->render_page();

function poc($person)
{
     ?>
     <BR>
     <CENTER><H2>PoC Info</H2></CENTER>
          On the way to getting PoC up and running, the metadata must spread.

          <P>
          <H4>Attributes</H4>
          <dl>
          
          <dt><i><A HREF="http://rnd.feide.no/node/1028">eduPersonPrincipalName (ePPN)</i></A></dt>
          <dd>We need ePPN because we use this as the commonName in the subject of the certificate.
          With this and the signature from the CA, grid entites know that the CN is authentic.<BR>
          </dd>

          <dt><i> <A HREF="http://rnd.feide.no/content/cn">Full Name (cn)</A></i></dt>
          <dd>
		The MICS/SLCS-profile states that the CN in the DN must contain a name that can
		be traced back to a specific user. In order to meet this criteria, as well as
		having a unique CN, we use the full name concatenated with the ePPN.
          </dd>

          <dt><i><A HREF="http://rnd.feide.no/node/1045">mail</A></i></dt>
          <dd>
		email-address. This is used to send confirmations, possibly the certificate itself.<BR>
		Furthermore, the MICS-profile requires the CA to send a notification to the user when a new
		certificate has been issued.
          </dd>
          </dl>
          </P>

          <?php
          echo "<P>\n";
          echo "<H4>Metadata</H4>\n";
          echo "As we try to support both SAMLv2 and Shibboleth v1.3,\n" ;
          echo "we are forced to use 2 different instances of simplesamlphp.<BR>\n";
          echo "If you are an IdP, and you want to enable your users to use Confusa, you must add the appropriate";
          echo "metadata at your site:<BR>\n";
          echo "<ul>\n";
          echo "<li><a href=\"" . dirname($_SERVER['HTTP_REFERER']) . "/simplesaml/saml2/sp/metadata.php?output=xhtml\">saml2</A><BR>\n";
          echo "<li><a href=\"" . dirname($_SERVER['HTTP_REFERER']) . "/simplesaml_shib13/shib13/sp/metadata.php?output=xhtml\">shib metadata</A><BR>\n";
          echo "</ul>\n";
          echo "</P>\n";
}
?>
