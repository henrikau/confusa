<h2>Certificate Download Area</h2>

{if empty($certList)}
No certificates in database
{else}
<table>
	<tr>
		<th colspan="2"></th>
		<th>Expires(from DB)</th>
		<th></th>
		<th>AuthToken</th>
		<th>Owner</th>
	</tr>
	{foreach from=$certList item=cert}
	<tr>
		{if $standaloje}
		<td>[ <a href="download_certificate.php?email_cert={$cert.auth_key}">Email</a> ]</td>
		<td>[ <a href="download_certificate.php?file_cert={$cert.auth_key}">Download</a> ]</td>
		<td>{$cert.valid_untill}</td>
		<td>{$cert.cert_owner}</td>
		<td>[ <a href="download_certificate.php?inspect_cert={$cert.auth_key}">Inspect</a> ]</td>
		<td>[ <a href="download_certificate.php?delete_cert={$cert.auth_key}">Delete</a> ]</td>
		{else}
		<td>[ <a href="download_certificate.php?email_cert={$cert.order_number}">Email</a> ]</td>
		<td>[ <a href="download_certificate.php?file_cert={$cert.order_number}">Download</a> ]</td>
		<td>[ <a href="download_certificate.php?inspect_cert={$cert.order_number}">Inspect</a> ]</td>
		<td></td>
		<td>{$cert.order_number}</td>
		<td>{$cert.cert_owner}</td>
		{/if}		
	</tr>
	{/foreach}
</table>
{/if}