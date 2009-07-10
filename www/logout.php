<?php
require_once('confusa_include.php');
include_once('framework.php');
$fw = new Framework('logout_local');
$fw->render_page();

function logout_local($person)
{
    if (isset($_GET['edu_name'])) {
        require_once('confusa_auth.php');
        deauthenticate_user($person);
    }
    if (!$person || !$person->is_auth()) {
         echo "<H2>You have been logged out of Confusa</H2>\n";
         echo "Return to <A HREF=\"index.php\">start</A><BR>\n";
    }
}
?>
