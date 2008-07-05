<?php
include_once('framework.php');
include_once('slcs_key.php');
$fw = new Framework('root_cert');
$fw->render_page();

function root_cert($person)
{
  echo "This is the Certificate we use for signing the CSRs we receive.<br>\n";
  echo "If you want the certificate, it's <A HREF=\"certs/sigma_cert.pem\">here</A><BR>\n";

  /* read and display the key in a nicely formatted way */
  $myFile = "certs/sigma_cert.pem";
  $fd = fopen($myFile, 'r');
  $content = fread($fd, filesize($myFile));
  fclose($fd);

  $key = new SLCSKey();
  $key->set_scsr($content);
  echo "<PRE>\n";
  echo $key->cert2str();
  echo "</PRE>\n";
}


?>
