{if $person->get_mode() == 0}
<h3>Certificates</h3>
<ul>
	<li><a href="process_csr.php">Request new</a></li>
	<li><a href="download_certificate.php">Download</a></li>
	<li><a href="revoke_certificate.php">Revoke</a></li>
</ul>

<h3>Other</h3>
<ul>
	<li><a href="about_you.php">About you</a></li>
	<li><a href="tools.php">Tools</a></li>
	{if $person->is_admin()}
	<li><a href="{php}$_SERVER['PHP_SELF']{/php}?mode=admin">Admin menu</a></li>
	{/if}
{elseif $person->get_mode() == 1}
<h3>Admin</h3>
<ul>
	{if $person->is_subscriber_subadmin()}
	<li><a href="revoke_certificate.php">Revoke Certificates</a></li>
	{elseif $person->is_subscriber_admin()}
	<li><a href="revoke_certificate.php"></a></li>
	<li><a href="admin.php">Manage Subscriber Administrators</a></li>
	<li><a href="robot.php">Robot Interface</a></li>
	{elseif $person->is_nren_admin()}
	<li><a href="admin.php">Manage Administrators</a></li>
	{/if}
</ul>
<h3>Other</h3>
<ul>
	<li><a href="index.php?mode=normal">Normal mode</a></li>	
{/if}
	<li><a href="index.php">Old Start</a></li>
</ul>
<h3>Help</h3>
<ul>
	<li><a href="about_nren.php">About</a></li>
	<li><a href="help.php">Help</a></li>
</ul>


<ul>
	{if !$person->is_auth()}
	<li><a href="index.php?start_login=yes">Login</a></li>
	{else}
	<li><a href="">logout_link("logout.php", "Logout", $person)</a></li>
    {/if}
</ul>