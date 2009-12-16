<h3>Portal</h3>

<ul>
   <li><a href="admin.php"{$prot_title}>
      {$prot_l}Admins{$prot_r}</a></li>
  <li>
    <a href="stylist.php?show=text"{$prot_title}>
      {$prot_l}Appearance{$prot_r}</a>
  </li>
</ul>

<h3>NREN</h3>

<ul>
  <li><a href="attributes.php"{$prot_title}>
       {$prot_l}Attributes{$prot_r}</a></li>
  {if $is_online === TRUE}
  <li><a href="accountant.php"{$prot_title}>
      {$prot_l}CA Account{$prot_r}</a></li>
  {/if}{* online *}
  <li><a href="nren_subs_settings.php"{$prot_title}>
      {$prot_l}Contact info{$prot_r}</a></li>
  <li><a href="nren_admin.php"{$prot_title}>
      {$prot_l}Subscribers{$prot_r}</a></li>
</ul>

<h3>Certificates</h3>

  <ul>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}Revocation{$prot_r}</a></li>
  </ul>


<h3>Info</h3>

  <ul>
	<li><a href="about_confusa.php">Version</a></li>
</ul>

<h3>View menu</h3>
<ul>
<li><a href="?mode=normal">User</a></li>
<li>NREN-Admin</li>
</ul>