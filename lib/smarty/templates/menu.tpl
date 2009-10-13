
{* ------------------------------------------------------------ *}
{*		If the person is in normal-mode			*}
{* ------------------------------------------------------------ *}
{if $person->getMode() == 0}

<h3>Certificates</h3>
<br />
<ul>
  <li><a href="process_csr.php">Request new</a></li>
  <li><a href="download_certificate.php">Download</a></li>
  <li><a href="revoke_certificate.php">Revoke</a></li>
</ul>
<br />
<br />
<br />
{* ------------------------------------------------------------ *}
{*		Person is in admin-mode				*}
{* ------------------------------------------------------------ *}
{elseif $person->getMode() == 1}
<h3>Admin</h3>

{if $person->isNRENAdmin()}
<i>(NREN)</i>
<br />
<br />
<ul>
  <li><a href="stylist.php?show=text">Appearance</a></li>
  <li><a href="nren_subs_settings.php">Settings</a></li>
  <li><a href="nren_admin.php">Subscribers</a></li>

  {if $is_online === TRUE}
  <li><a href="accountant.php">CA Account</a></li>
  {else}
  <br />
  {/if}{* online *}


{elseif $person->isSubscriberAdmin()}
<i>(Subscriber)</i>
<br />
<br />
<ul>
  <li><a href="robot.php">Robot</a></li>
  <li><a href="nren_subs_settings.php">Settings</a></li>
{else}
<br />
<br />
  <ul>
  {/if} {* type admin *}
  <li><a href="admin.php">Admins</a></li>
  <br />
  <li><a href="revoke_certificate.php">Revocation</a></li>
</ul>

{/if} { * Mode *}

<br />

{* ------------------------------------------------------------ *}
{*		Common for all					*}
{* ------------------------------------------------------------ *}
<h3>Other</h3>
<ul>
  {if $person->getMode() == 0 && $person->isAdmin() }
  <li><a href="{php}$_SERVER['PHP_SELF']{/php}?mode=admin">Admin menu</a></li>
  {elseif $person->isAdmin()}
  <li><a href="index.php?mode=normal">Normal mode</a></li>
  {else}
  <br />
  {/if}

  <li><a href="about_you.php">About you</a></li>
  <li><a href="tools.php">Tools</a></li>
  <li><a href="root_cert.php">CA Certificate</a></li>
</ul>
<br />
<h3>Help</h3>
<br />
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
