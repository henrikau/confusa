
{* ------------------------------------------------------------ *}
{*		If the person is in normal-mode			*}
{* ------------------------------------------------------------ *}
{if $person->getMode() == 0}

{if ! $person->isAuth()}
{assign var=prot_title value='title="You will be redirected to login before you can view this page"'}
{assign var=prot_l value="<i>"}
{assign var=prot_r value="*</i>"}
{else}
{assign var=prot_title value=''}
{assign var=prot_l value=''}
{assign var=prot_r value=''}
{/if}


<h3>Certificates</h3>
<br />
<ul>
  <li><a href="process_csr.php"	{$prot_title}>
      {$prot_l}Request new{$prot_r}</a></li>
  <li><a href="download_certificate.php"{$prot_title}>
      {$prot_l}Download{$prot_r}</a></li>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}Revoke{$prot_r}</a></li>
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
<i>(Subscriber)</i>
<br />
<br />
<ul>
  <li><a href="robot.php" {$prot_title}>
      {$prot_l}Robot{$prot_r}</a></li>
  <li><a href="nren_subs_settings.php"{$prot_title}>
      {$prot_l}Settings{$prot_r}</a></li>
</ul>

<br />
<ul>
<li><a href="attributes.php"{$prot_title}>
    {$prot_l}Attributes{$prot_r}</a></li>
</ul>

{else}
<br />
<br />
{/if} {* type admin *}

  <ul>
  <li><a href="admin.php"{$prot_title}>
      {$prot_l}Admins{$prot_r}</a></li>
  </ul>
  <br />
  <ul>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}Revocation{$prot_r}</a></li>
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
  </ul>
  <br />
  <ul>
  {/if}

  <li><a href="about_you.php"{$prot_title}>
      {$prot_l}About you{$prot_r}</a></li>
  <li><a href="tools.php"{$prot_title}>
      {$prot_l}Tools{$prot_r}</a></li>
  <li><a href="root_cert.php">
      {$prot_l}CA Certificate{$prot_r}</a></li>
</ul>
<br />
<h3>Help</h3>
<br />
<ul>
  <li><a href="about_nren.php">About</a></li>
  <li><a href="help.php">Help</a></li>

  {if $person->isAdmin()}
		<li><a href="about_confusa.php">Version</a></li>
  {/if}
</ul>


<ul>
  {if !$person->isAuth()}
  <li><a href="index.php?start_login=yes">Login</a></li>
  {else}
  <li><a href="{$logoutUrl}">Log out</a></li>
  {/if}
</ul>
