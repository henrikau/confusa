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
		    echo "<BR /><B>Certificates:</B><BR /><HR />\n";
		    echo get_menu_name("process_csr.php",		"Request New");
		    echo get_menu_name("download_certificate.php",	"Download");
		    echo get_menu_name("revoke_certificate.php",	"Revoke");

		    echo "<BR />\n";
		    echo "<B>Other</B><BR />\n";
		    echo "<HR />\n";
		    echo get_menu_name("about_you.php",	"About You");
		    echo get_menu_name("tools.php",	"Tools");

		    if ($person->is_admin()) {
			    echo get_menu_name("index.php?mode=admin", "Admin menu");
		    }

	    } else if ($mode == ADMIN_MODE) {
		    /* Create the admin-menu based on admin privileges.
		     * The pages common for for more than one type of admin (or
		     * normal users) will also check privileges.
		     */
		    if ($person->is_subscriber_subadmin()) {
			    echo get_menu_name("revoke_cert.php",	"Revoke Certificates");
		    } else if ($person->is_subscriber_admin()) {
			    echo get_menu_name("revoke_cert.php",	"Revoke Certificates");
			    echo get_menu_name("admin.php",		"Manage Subscriber Administrators");
			    echo get_menu_name("robot.php",		"Robot Interface");
		    } else if ($person->is_nren_admin()) {
			    echo get_menu_name("admin.php",		"Manage Administrators");
		    }
		    echo get_menu_name("index.php?mode=normal",	"Normal mode");
	    }

    }
    echo get_menu_name("index.php", "Old Start");
    echo "<BR /><HR />\n";

    /* Regardless of status, these should be visible */
    echo get_menu_name("about_nren.php","About");
    echo get_menu_name("help.php",	"Help");


    echo "<BR />\n";
    show_auth_link($person);

} /* end render_menu */

function get_menu_name($url, $name)
{
	return "<A HREF=\"".htmlentities($url)."\">".htmlentities($name)."</A><BR><BR>\n";
}

function show_auth_link($person)
{
    if (!$person->is_auth()) {
	    echo "<A HREF=\"index.php?start_login=yes\">Login</A><BR>\n";
    } else {
	    echo logout_link("logout.php", "Logout", $person) . "<BR><BR>\n";
    }
}
?>
