<?php
function render_menu($person)
    {
    echo "<B>Menu</B><BR/><BR>\n";
    /* always show the Frontpage-link (this page should always be
     * visible */
    if ($person->is_auth()) {
        echo "<A HREF=\"index.php\">Start</A><BR><BR>\n";
        echo "<A HREF=\"tools.php\">Tools</A><BR><BR>\n";
        echo "<A HREF=\"about_you.php\">About You</A><BR><BR>\n";
	echo "<BR>\n";
        echo logout_link("logout.php", "Logout", $person) . "<BR><BR>\n";
    }
    else {
         echo "<A HREF=\"index.php?start_login=yes\">Login</A><BR>\n";
    }

    } /* end render_menu */
?>
