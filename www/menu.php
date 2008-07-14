<?php
function render_menu($authenticated)
    {
    echo "<B>Menu</B><BR/><BR>\n";
    /* always show the Frontpage-link (this page should always be
     * visible */
    echo "<A HREF=\"index.php\">Front</A><BR><BR>\n";

    if ($authenticated) {
        echo "<A HREF=\"key_handler.php\">Key</A><BR>\n";
        echo feide_logout_link("logout.php", "Logout") . "<BR>\n";
    }
    else {
        include_once('login_link.html');
    }
    } /* end render_menu */
?>
