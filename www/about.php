<?php
require_once 'confusa_include.php';
include_once 'framework.php';
$fw = new Framework('about_confusa');
$fw->render_page();

function about_confusa($valid_user)
{
    ?>
        <H3>About Confusa and various contact-information</H3>
        Here you will find: 
        <ol>
        <li>url to/info about the CA
        <li>Confusa's CA's <a href="root_cert.php">root-certificate</A>
        <li>We don't support CRL (since lifetime is 11 days)
        <li>CP and CPS docs
        <li>Official email-address: <A HREF="mailto:sigma@uninett.no">sigma@uninett.no</A>
        <li>Official postal address:
	<address>
	Uninett Sigma A/S <BR>
	Abelsgate 5 <BR>
	7465 Trondheim <BR>
	</address>
        </ol>

    <?php
}
?>
