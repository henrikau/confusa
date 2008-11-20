<?php
include_once('framework.php');

$fw = new Framework('poc');
$fw->render_page();

function poc($person)
{
     ?>
     <H3>PoC Info</H3>
          On the way to getting PoC up and running, this is where you will find PoC related information.

          <P>
          <H4>Attributes</H4>
          <dl>
          
          <dt><i>eduPersonPrincipalName (ePPN)</i></dt>
          <dd>We need ePPN because we use this as the commonName in the subject of the certificate.
          With this and the signature from the CA, grid entites know that the CN is authentic.<BR>
          More on this attribute <A HREF="http://rnd.feide.no/node/1028">at feide-rnd</A>.<BR><BR>
          </dd>

          <dt><i>Full Name</i></dt>
          <dd>
          The name is used mostly to "be polite". As several attributes can act as this value, we have
          chosen <A HREF="http://rnd.feide.no/content/cn">cn</A>.<BR><BR>
          </dd>

          <dt><i>mail</i></dt>
          <dd>
          email-address. This is used to send confirmations, possibly the certificate itself, should the user
          so choses. More on this attribute <A HREF="http://rnd.feide.no/node/1045">at feide-rnd</A>.<BR><BR>
          </dd>
          </dl>
          </P>

          <?php
          echo "<P>\n";
          echo "<H4>Metadata</H4>\n";
          echo "<a href=\"" . dirname($_SERVER['HTTP_REFERER']) . "/simplesaml/saml2/sp/metadata.php?output=xhtml\">saml2</A><BR>\n";
          echo "<a href=\"" . dirname($_SERVER['HTTP_REFERER']) . "/simplesaml/shib13/sp/metadata.php?output=xhtml\">shib metadata</A><BR>\n";
          echo "</P>\n";
}
?>