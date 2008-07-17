<?php
function render_menu($authenticated)
    {
    echo "<B>Menu</B><BR/><BR>\n";
    /* always show the Frontpage-link (this page should always be
     * visible */
    echo "<A HREF=\"index.php\">Start</A><BR><BR>\n";
    echo "<A HREF=\"root_cert.php\">CA-Cert.</A><BR><BR>\n";

    if ($authenticated) {
        echo "<A HREF=\"key_handler.php\">Keys</A><BR><BR>\n";
        echo feide_logout_link("logout.php", "Logout") . "<BR><BR>\n";
    }
    else {
        include_once('login_link.html');
    }
    } /* end render_menu */
?>
