<?php
include_once('framework.php');

if (isset($_GET['send_file'])) {
     include_once('file_download.php');
     download_file(file_get_contents("certs/sigma_cert.pem"), "sigma_cert.pem");
     exit(1);
}

$fw = new Framework('root_cert');
$fw->render_page();

function root_cert($person)
{
     echo "<P>\n";
     echo "This is the Certificate we use for signing the CSRs we receive.<br>\n";
     echo "If you want the certificate, it's <A HREF=\"certs/sigma_cert.pem\">here</A><BR>\n";
     echo "</P>\n";

     echo "<P>\n";
     echo "Or, if you want to download it directly, press here:  ";
     echo '<form method="GET" action="root_cert.php">';
     echo '<input type="hidden" name="send_file">';
     echo '<input type="submit" value="root cert">';
     echo '</form>';
     echo "</P>\n";


  /* read and display the key in a nicely formatted way */
  $content = file_get_contents("certs/sigma_cert.pem");
  openssl_x509_export($content, $tmp, false);
  echo "<PRE>\n";
  echo $tmp;
  echo "</PRE>\n";
}


?>
