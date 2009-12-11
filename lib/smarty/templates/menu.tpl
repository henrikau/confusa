
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
<h3 style="padding-bottom: 1em">Certificates</h3>
<div style="padding-bottom: 1em">
<ul>
  <li><a href="process_csr.php"	{$prot_title}>
      {$prot_l}Request new{$prot_r}</a></li>
  <li><a href="download_certificate.php"{$prot_title}>
      {$prot_l}My certificates{$prot_r}</a></li>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}Revoke{$prot_r}</a></li>
</ul>
</div>
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
<ul style="padding-bottom: 1em">
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

  <ul style="padding-bottom: 1em">
  <li><a href="admin.php"{$prot_title}>
      {$prot_l}Admins{$prot_r}</a></li>
  </ul>

  <ul style="padding-bottom: 1em">
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}Revocation{$prot_r}</a></li>
  </ul>

{/if} { * Mode *}

{* ------------------------------------------------------------ *}
{*		Common for all					*}
{* ------------------------------------------------------------ *}

{if $person->getMode() == 0}
<h3 style="padding-bottom: 1em">Extras</h3>
<ul style="padding-bottom: 1em">
  <li><a href="tools.php"{$prot_title}>
      {$prot_l}Tools{$prot_r}</a></li>
  <li><a href="root_cert.php">CA Certificate</a></li>
</ul>

<div style="padding-bottom: 1em">
<h3>Help</h3>
</div>
<ul style="padding-bottom: 1em">
  <li><a href="about_nren.php">About</a></li>
  <li><a href="help.php">Help</a></li>

  {if $person->isAdmin()}
		<li><a href="about_confusa.php">Version</a></li>
  {/if}
</ul>
{/if}

  {if $person->getMode() == 0 && $person->isAdmin() }
  <div style="padding-bottom: 1em">
<h3>View menu</h3>
</div>
<ul style="padding-bottom: 1em">
<li>User</li>
<li><a href="index.php?mode=admin" title="Change">Admin</a></li>
</ul>

  {elseif $person->isAdmin()}
  <div style="padding-bottom: 1em">
<h3>View menu</h3>
</div>
<ul style="padding-bottom: 1em">
<li><a href="index.php?mode=normal">User</a></li>
<li>Admin</li>
</ul>
{/if}

<!-- <ul>-->
  {if !$person->isAuth()}
  <h3><a href="index.php?start_login=yes">Login</a></h3>
  {else}
  <h3><a href="{$logoutUrl}">Log out</a></h3>
  {/if}
<!-- </ul>-->
