<h3>Requesting new Certificates</h3>

{include file='upload_form.tpl'}

<form action="index.php" method="get">
	<input name="inspect_csr" type="text" />
	<input type="submit" value="Inspect CSR" />
</form>


{if $signingOk}
<div class="success">
	The certificate is now being provessed by the CA (Certificate Authority)<BR />
	Depending on the load, this takes approximately 2 minutes.<BR /><BR />

	You will now be redirected to the certificate-download area found 
	<a href="download_certificate.php?poll={$sign_csr}">here</a>
</div>
{/if}

{if empty($csrList)}
No CSR in database
{else}
<h3>List of CSR</h3>
<table>
	<tr>
		<th>Upload date</th>
		<th>Common name</th>
		<th>From IP</th>
		<th>Insepct</th>
		<th>Delete</th>		
	</tr>
	{foreach from=$csrList item=csr}
	<tr>
		<td>{$csr.uploaded_date}</td>
		<td>{$csr-common_name}</td>
		<td>{$csr-from_ip}</td>
		<td>[ <a href="process_csr.php?inspect_csr={$csr-auth_key}">Inspect</a> ]</td>
		<td>[ <a href="process_csr.php?delete_csr={$csr-auth_key}">Delete</a> ]</td>
	</tr>
	{/foreach}
</table>
{/if}