<?php
  /* commons.php
   *
   * A collection of misc functions, used a lot
   *
   * Author: Henrik Austad <henrik.austad@uninett.no>
   *
   * This file is deprecated.
   */
$print_debug = true;


function printn($msg)
{
  print $msg . "\n";
}

function printBRn($msg)
{
  printn($msg.'<BR>');
}

function send_email($address, $subject, $body)
{
    echo $address . "<br>\n";
    echo $subject . "<br>\n";
    echo $body . "<br>\n";
    $from = "webmaster@slcsweb.uninett.no";
    $host = "localhost";
    $username = "";
    $password = "";
    
    $headers = array ('MIME-Version' => "1.0", 
                      'Content-type' => "text/html; charset=utf-8;", 
                      'From' => $from, 
                      'To' => $address, 
                      'Subject' => $subject);
    $c=0;
    while($c<sizeof($headers)) {
        echo $headers[$c] . "<BR>\n";
        $c = $c + 1;
    }

    /* $smtp = Mail::factory('smtp', array ('host' => $host, 'auth' => false)); */

/*     $mail = $smtp->send($to, $headers, $body); */
}
?>
