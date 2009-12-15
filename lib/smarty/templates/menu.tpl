
{* ------------------------------------------------------------ *}
{*		If the person is in normal-mode			*}
{* ------------------------------------------------------------ *}

{if ! $person->isAuth()}
{assign var=prot_title value='title="You will be redirected to login before you can view this page"'}
{assign var=prot_l value="<i>"}
{assign var=prot_r value="*</i>"}
{else}
{assign var=prot_title value=''}
{assign var=prot_l value=''}
{assign var=prot_r value=''}
{/if}

{if $person->getMode() == 0}
<h3>Certificates</h3>
<ul>
  <li><a href="process_csr.php"	{$prot_title}>
      {$prot_l}Request new{$prot_r}</a></li>
  <li><a href="download_certificate.php"{$prot_title}>
      {$prot_l}My certificates{$prot_r}</a></li>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}Revoke{$prot_r}</a></li>
</ul>

{* ------------------------------------------------------------ *}
{*		Person is in admin-mode				*}
{* ------------------------------------------------------------ *}
{elseif $person->getMode() == 1}
{if $person->isNRENAdmin()}
<div style="padding-bottom: 2em">
<h3>Admin</h3>
<i>(NREN)</i>
</div>
<ul>
  <li>
    <a href="stylist.php?show=text"{$prot_title}>
      {$prot_l}Appearance{$prot_r}</a>
  </li>
  <li><a href="nren_subs_settings.php"{$prot_title}>
      {$prot_l}Settings{$prot_r}</a></li>
  <li><a href="nren_admin.php"{$prot_title}>
      {$prot_l}Subscribers{$prot_r}</a></li>
</ul>
<br />

  {if $is_online === TRUE}
<ul>
  <li><a href="accountant.php"{$prot_title}>
      {$prot_l}CA Account{$prot_r}</a></li>
</ul>
  {/if}{* online *}

   <ul>
   <li><a href="attributes.php"{$prot_title}>
       {$prot_l}Attributes{$prot_r}</a></li>
   </ul>
   <br />


{elseif $person->isSubscriberAdmin()}
<div style="padding-bottom: 2em">
<h3>Admin</h3>
<i>(Subscriber)</i>
</div>
<ul>
  <li><a href="robot.php" {$prot_title}>
      {$prot_l}Robot{$prot_r}</a></li>
  <li><a href="nren_subs_settings.php"{$prot_title}>
      {$prot_l}Settings{$prot_r}</a></li>
</ul>

<ul>
<li><a href="attributes.php"{$prot_title}>
    {$prot_l}Attributes{$prot_r}</a></li>
</ul>

{else}
<div style="padding-bottom: 2em">
<h3>Admin</h3>
</div>
{/if} {* type admin *}

  <ul>
  <li><a href="admin.php"{$prot_title}>
      {$prot_l}Admins{$prot_r}</a></li>
  </ul>

  <ul>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}Revocation{$prot_r}</a></li>
  </ul>

{/if} { * Mode *}

{* ------------------------------------------------------------ *}
{*		Common for all					*}
{* ------------------------------------------------------------ *}

{if $person->getMode() == 0}
<h3>Help</h3>
<ul>
  <li><a href="about_nren.php">About</a></li>
  <li><a href="help.php">Help</a></li>
</ul>

<h3>Extras</h3>
<ul>
  <li><a href="tools.php"{$prot_title}>
      {$prot_l}Tools{$prot_r}</a></li>
  <li><a href="root_cert.php">CA Certificate</a></li>
  {if $person->isAdmin()}
		<li><a href="about_confusa.php">Version</a></li>
  {/if}
</ul>
{/if}

  {if $person->getMode() == 0 && $person->isAdmin() }
<h3>View menu</h3>
<ul>
<li>User</li>
<li><a href="?mode=admin">Admin</a></li>
</ul>

  {elseif $person->isAdmin()}
<h3>View menu</h3>
<ul>
<li><a href="?mode=normal">User</a></li>
<li>Admin</li>
</ul>
{/if}

{if !$person->isAuth()}
<h3><a href="index.php?start_login=yes">Login</a></h3>
{else}
<h3><a href="{$logoutUrl}">Log out</a></h3>
{/if}
