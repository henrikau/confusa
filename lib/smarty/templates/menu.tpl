{if $person->getMode() == 0}
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
	{if $person->isAdmin()}
	<li><a href="{php}$_SERVER['PHP_SELF']{/php}?mode=admin">Admin menu</a></li>
	{/if}
{elseif $person->getMode() == 1}
<h3>Admin</h3>
<ul>
	{if $person->isSubscriberSubadmin()}
	<li><a href="revoke_certificate.php">Revoke Certificates</a></li>
	<li><a href="admin.php">Show Admins</a></li>
	{elseif $person->isSubscriberAdmin()}
	<li><a href="revoke_certificate.php">Revoke certificates</a></li>
	<li><a href="admin.php">Manage Subscriber Administrators</a></li>
	<li><a href="robot.php">Robot Interface</a></li>
	{elseif $person->isNRENAdmin()}
	<li><a href="admin.php">Manage Administrators</a></li>
	<li><a href="nren_admin.php">NREN-Admin</a></li>
	<li><a href="stylist.php?show=text">Customize appearance</a></li>
	{/if}
</ul>
<h3>Other</h3>
<ul>
	<li><a href="index.php?mode=normal">Normal mode</a></li>	
{/if}
	<li><a href="root_cert.php">CA Certificate</a></li>
</ul>

<h3>Help</h3>
<ul>
	<li><a href="about_nren.php">About</a></li>
	<li><a href="help.php">Help</a></li>
</ul>


<ul>
	{if !$person->isAuth()}
	<li><a href="index.php?start_login=yes">Login</a></li>
	{else}
	<li><a href="{$logoutUrl}">Log out</a></li>
    {/if}
</ul>
