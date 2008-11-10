<?php
include_once('framework.php');

$cert_file = "certs/sigma_cert.pem";
if (isset($_GET['send_file'])) {
     include_once('file_download.php');
     download_file(file_get_contents($cert_file), "sigma_cert.pem");
     exit(1);
}

if (isset($_GET['install_root']) && file_exists($cert_file)) {
     $myCert = join("", file($cert_file));
     header("Content-Type: application/x-x509-ca-cert");
     print $myCert;
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

     echo "Alternatively, you can install it directly into your browser: ";
     echo "<A HREF=\"" . $_SERVER['HOST'] . $_SERVER['PHP_SELF'] . "?install_root\">here</A>\n";
  /* read and display the key in a nicely formatted way */
  $content = file_get_contents("certs/sigma_cert.pem");
  openssl_x509_export($content, $tmp, false);
  echo "<PRE>\n";
  echo $tmp;
  echo "</PRE>\n";
}


?>
