<?php
include_once('framework.php');
$fw = new Framework('logout_local');
$fw->render_page();

function logout_local($person)
{
    if (isset($_GET['edu_name'])) {
        require_once('confusa_auth.php');
        /* require_once('slcsweb_auth.php'); */
        deauthenticate_user($person);
        echo "<H2>You have been logged out of SLCS Web</H2>\n";
        echo "Return to slcs-web: <A HREF=\"index.php\">SLCS-Web</A><BR>\n";
    }
}
?>
