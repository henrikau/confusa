<h3>Certificates</h3>
<ul>
  <li><a href="process_csr.php"	{$prot_title}>
      {$prot_l}Request new{$prot_r}</a></li>
  <li><a href="download_certificate.php"{$prot_title}>
      {$prot_l}My certificates{$prot_r}</a></li>
  <li><a href="revoke_certificate.php"{$prot_title}>
      {$prot_l}Revoke{$prot_r}</a></li>
</ul>

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
</ul>

{if $person->isAdmin()}
	{if $person->isNRENAdmin()}
		<h3>View menu</h3>
			<ul>
			<li>User</li>
			<li><a href="?mode=admin">NREN-Admin</a></li>
			</ul>
	{elseif $person->isSubscriberAdmin()}
		<h3>View menu</h3>
			<ul>
			<li>User</li>
			<li><a href="?mode=admin">Subscr.-Admin</a></li>
			</ul>
	{elseif $person->isSubscriberSubAdmin()}
		<h3>View menu</h3>
			<ul>
			<li>User</li>
			<li><a href="?mode=admin">Admin</a></li>
			</ul>
	{/if}
{/if}