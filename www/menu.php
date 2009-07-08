<?php
function render_menu($person)
    {
    echo "<B>Menu</B><BR/><BR>\n";
    /* always show the Frontpage-link (this page should always be
     * visible */
    if ($person->is_auth()) {
	    /* use get_mode to figure out the mode. Note: non-admin users will
	     * have NORMAL_MODE returned regardless of datbase etc. */
	    $mode = $person->get_mode();
	    if ($mode == NORMAL_MODE) {
		    echo get_menu_name("process_csr.php",		"Request New Certificate");
		    echo get_menu_name("download_certificate.php",	"Download Certificate");
		    echo get_menu_name("revoke_certificate.php",	"Revoke Certificate");
		    echo get_menu_name("about_nren.php",		"About <NREN>");
		    echo get_menu_name("help.php",			"Help <NREN>");
		    if ($person->is_admin()) {
			    echo get_menu_name("index.php?mode=admin", "Admininstrative menu");
		    }
	    } else if ($mode == ADMIN_MODE) {
		    /* Create the admin-menu based on admin privileges.
		     * The pages common for for more than one type of admin (or
		     * normal users) will also check privileges.
		     */
		    if ($person->is_subscriber_sub_admin()) {
			    echo get_menu_name("revoke_cert.php",	"Revoke Certificates");
		    } else if ($person->is_subscriber_sub_admin()) {
			    echo get_menu_name("revoke_cert.php",	"Revoke Certificates");
			    echo get_menu_name("admin.php",		"Manage Subscriber Administrators");
			    echo get_menu_name("robot.php",		"Robot Interface");
		    } else if ($person->is_nren_admin()) {
			    echo get_menu_name("admin.php",		"Manage Administrators");
		    }
		    echo get_menu_name("index.php?mode=normal",	"Normal mode");
	    }

	    echo "END NEW MENU<BR>\n";
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
	return "<A HREF=\"".htmlentities($url)."\">".htmlentities($name)."</A><BR><BR>\n";
}
?>
