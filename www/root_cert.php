<?php
include_once('framework.php');
$fw = new Framework('root_cert');
$fw->render_page();

function root_cert($person)
{
  echo "This is the Certificate we use for signing the CSRs we receive.<br>\n";
  echo "If you want the certificate, it's <A HREF=\"certs/sigma_cert.pem\">here</A><BR>\n";

  /* read and display the key in a nicely formatted way */
  $content = file_get_contents("certs/sigma_cert.pem");
  openssl_x509_export($content, $tmp, false);
  echo "<PRE>\n";
  echo $tmp;
  echo "</PRE>\n";
}


?>
