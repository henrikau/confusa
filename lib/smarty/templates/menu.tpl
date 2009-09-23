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
	<li><a href="admin.php">Subscriber Admins</a></li>
	<li><a href="robot.php">Robot Interface</a></li>
	<li><a href="language_selector.php">Language</a></li>
	<li><a href="nren_subs_settings.php">Subscriber settings</a></li>
	{elseif $person->isNRENAdmin()}
	<li><a href="admin.php">Admins</a></li>
	<li><a href="nren_admin.php">Subscribers</a></li>
	<li><a href="stylist.php?show=text">Appearance</a></li>
	<li><a href="language_selector.php">Language</a></li>
	<li><a href="nren_subs_settings.php">NREN settings</a></li>

	{if $is_online === TRUE}
		<li><a href="accountant.php">CA-accounts</a></li>
	{/if}
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
