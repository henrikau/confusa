<h3>{$heading_portal|escape}</h3>

<ul>
   <li><a href="admin.php"{$prot_title}>
      {$prot_l}{$item_admins|escape}{$prot_r}</a></li>
  <li>
    <a href="stylist.php?show=text"{$prot_title}>
      {$prot_l}{$item_appearance|escape}{$prot_r}</a>
  </li>
</ul>

<h3>{$heading_nren|escape}</h3>

<ul>
  <li><a href="attributes.php"{$prot_title}>
       {$prot_l}{$item_attributes|escape}{$prot_r}</a></li>
  {if $is_online === TRUE}
  <li><a href="accountant.php"{$prot_title}>
      {$prot_l}{$item_ca_account|escape}{$prot_r}</a></li>
  {/if}{* online *}
  <li><a href="nren_subs_settings.php"{$prot_title}>
      {$prot_l}{$item_nren_settings|escape}{$prot_r}</a></li>
  <li><a href="nren_admin.php"{$prot_title}>
      {$prot_l}{$item_subscribers|escape}{$prot_r}</a></li>
</ul>

<h3>{$heading_certificates|escape}</h3>

  <ul>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}{$item_revocation|escape}{$prot_r}</a></li>
  </ul>


<h3>{$heading_info|escape}</h3>

  <ul>
	<li><a href="about_confusa.php">{$item_aboutconf|escape}</a></li>
</ul>

<h3>{$heading_view_menu|escape}</h3>
<ul>
<li><a href="?mode=normal">{$item_view_user|escape}</a></li>
<li>{$item_view_nren_admin|escape}</li>
</ul>
