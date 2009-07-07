<?php
function render_menu($person)
    {
    echo "<B>Menu</B><BR/><BR>\n";
    /* always show the Frontpage-link (this page should always be
     * visible */
    if ($person->is_auth()) {
	    echo get_menu_name("Request New Certificate", "process_csr.php");
	    echo get_menu_name("Download Certificate", "download_certificate.php");
	    echo get_menu_name("Revoke Certificate", "revoke_certificate.php");
	    echo "<BR><BR>\n";
	    echo get_menu_name("index.php", "Start");
	    echo get_menu_name("tools.php","Tools");
	    echo get_menu_name("about_you.php","About You");
	    echo "<BR>\n";
	    echo logout_link("logout.php", "Logout", $person) . "<BR><BR>\n";
    }
    else {
         echo "<A HREF=\"index.php?start_login=yes\">Login</A><BR>\n";
    }

    } /* end render_menu */

function get_menu_name($url, $name)
{
	return "<A HREF=\"".$url."\">".$name."</A><BR><BR>\n";
}
?>
