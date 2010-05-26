<h3>{$heading_certificates|escape}</h3>
<ul>
  <li><a href="process_csr.php"	{$prot_title}>
      {$prot_l}{$item_request_new|escape}{$prot_r}</a></li>
  <li><a href="download_certificate.php"{$prot_title}>
      {$prot_l}{$item_my_certificates|escape}{$prot_r}</a></li>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}{$item_revoke_certificates|escape}{$prot_r}</a></li>
  <li><a href="root_cert.php">{$item_cacert|escape}</a></li>
</ul>

<h3>{$heading_help|escape}</h3>
<ul>
  <li><a href="about_nren.php">{$item_about|escape}</a></li>
  <li><a href="about_confusa.php">{$item_aboutconf|escape}</a></li>
  <li style="margin-bottom: 1em"><a href="privacy_notice.php">{$item_privacy_notice|escape}</a></li>
  <li><a href="help.php">{$item_help|escape}</a></li>
</ul>

{if $person->isAdmin()}
	{if $person->isNRENAdmin()}
		<h3>{$heading_view_menu|escape}</h3>
			<ul>
			<li>{$item_view_user|escape}</li>
			<li><a href="?mode=admin&amp;{$ganticsrf}">{$item_view_nren_admin|escape}</a></li>
			</ul>
	{elseif $person->isSubscriberAdmin()}
		<h3>{$heading_view_menu|escape}</h3>
			<ul>
			<li>{$item_view_user|escape}</li>
			<li><a href="?mode=admin&amp;{$ganticsrf}">{$item_view_subscr_admin|escape}</a></li>
			</ul>
	{elseif $person->isSubscriberSubAdmin()}
		<h3>{$heading_view_menu|escape}</h3>
			<ul>
			<li>{$item_view_user|escape}</li>
			<li><a href="?mode=admin&amp;{$ganticsrf}">{$item_view_admin|escape}</a></li>
			</ul>
	{/if}
{/if}
