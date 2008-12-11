<?php
function render_menu($person)
    {
    echo "<B>Menu</B><BR/><BR>\n";
    /* always show the Frontpage-link (this page should always be
     * visible */
    echo "<A HREF=\"index.php\">Start</A><BR><BR>\n";
    echo "<A HREF=\"root_cert.php\">CA-Cert.</A><BR><BR>\n";
    echo "<A HREF=\"poc.php\">PoC info</A><BR><BR>\n";

    if ($person->is_auth()) {
        echo "<A HREF=\"key_handler.php\">Keys</A><BR><BR>\n";
        echo "<A HREF=\"tools.php\">Tools</A><BR><BR>\n";
        echo feide_logout_link("logout.php", "Logout", $person) . "<BR><BR>\n";
    }
    else {
         echo "<A HREF=\"index.php?start_login=yes\">Login</A><BR>\n";
/*         require_once('confusa_auth.php'); */
    }

    } /* end render_menu */
?>
