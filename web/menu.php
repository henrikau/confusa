<?php
function render_menu($authenticated)
    {
    echo "<B>Menu</B><BR/><BR>\n";
    /* always show the Frontpage-link (this page should always be
     * visible */
    echo "<A HREF=\"index.php\">Front</A><BR><BR>\n";

    if ($authenticated) {
        echo "<A HREF=\"key_gen.php\">Key</A><BR>\n";
        echo feide_logout_link("logout.php", "Logout") . "<BR>\n";
    }
    else {
        include_once('login_link.html');
    }
    echo "<BR><BR><BR>\n";
    /* we *have* to inform users of what Confusa is, before
     * we demand that they log in. Also, we must provide the
     * root-certs etc to all users
     */
/*     echo ("<A HREF=\"about.php\">About SLCS-web</A><BR>\n"); */
/*     echo ("<BR>\n"); */
/*     echo ("<A HREF=\"documentation.php\">SysDoc</A><BR>\n"); */
/*     echo ("<BR>\n"); */
        }
?>
