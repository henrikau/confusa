{if $signingOk}
<div class="success">
<CENTER>
	The certificate is now being provessed by the CA (Certificate Authority)<BR />
	Depending on the load, this takes approximately 2 minutes.<BR /><BR />

	You will now be redirected to the certificate-download area found 
	<a href="download_certificate.php?poll={$sign_csr}">here</a>
</CENTER>
</div>
{/if}

{if empty($csrList)}
{* No CSR in database*}
{else}
    <div id="csr">
    <fieldset>
    <legend>List of CSR</legend>

<table>
	<tr>
	</tr>
	<tr>
		<td><B>Upload date</B></td>
		<td></td>
		<td><B>Common name</B></td>
		<td></td>
		<td><B>Remote IP</B></td>
		<td></td>
		<td>{*<B>Inspect</B>*}</td>
		<td></td>
		<td>{*<B>Delete</B>*}</td>
		<td></td>
	</tr>
	{foreach from=$csrList item=csr}
	<tr></tr>
	<tr>
		<td>{$csr.uploaded_date}</td>
		<td> </td>
		<td>{$csr.common_name}</td>
		<td> </td>
		<td>{$csr.from_ip}</td>
		<td> </td>
		<td>
		{if $csrInspect.auth_token eq $csr.auth_key}
			[<FONT COLOR="GRAY">Inspect</FONT>]
		{else}
			[<a href="process_csr.php?inspect_csr={$csr.auth_key}">Inspect</a>]
		{/if}
		</td>
		<td> </td>
		<td>[<a href="process_csr.php?delete_csr={$csr.auth_key}">Delete</a>]</td>
		<td></td>
	</tr>
	{/foreach}
	<tr>
	</tr>
</table>
</legend>
</div>
<BR />
{/if}

{if !empty($csrInspect)}
    <div id="inspect_csr">
    <fieldset>
    <legend>Inspect CSR</legend>
    <table>
	<tr></tr>

	{* Auth Token *}
	<tr>
	<td>Auth token</td>
	<td></td>
	<td>{$csrInspect.auth_token}</td>
	</tr>
	<tr></tr>

	{* Country *}
	{if !empty($csrInspect.countryName)}
	<tr>
	<td>Country:</td>
	<td></td>
	<td>{$csrInspect.countryName}</td>
	</tr>
	<tr></tr>
	{/if}

	{* Organization name *}
	{if !empty($csrInspect.organizationName)}
	<tr>
	<td>Organization Name:</td>
	<td></td>
	<td>{$csrInspect.organizationName}</td>
	</tr>
	<tr></tr>
	{/if}

	{* Common-Name *}
	{if !empty($csrInspect.commonName)}
	<tr>
	<td>Common-Name:</td>
	<td></td>
	<td>{$csrInspect.commonName}</td>
	</tr>
	<tr></tr>
	{/if}

	{* Length of key *}
	{if !empty($csrInspect.length)}
	<tr>
	<td>Key length:</td>
	<td></td>
	<td>{$csrInspect.length}</td>
	</tr>
	<tr></tr>
	{/if}

	{* Uploaded *}
	{if !empty($csrInspect.length)}
	<tr>
	<td>Was uploaded:</td>
	<td></td>
	<td>{$csrInspect.uploaded}</td>
	</tr>
	<tr></tr>
	{/if}

	{* Remote IP *}
	{if !empty($csrInspect.length)}
	<tr>
	<td>IP:</td>
	<td></td>
	<td>{$csrInspect.from_ip}</td>
	</tr>
	<tr></tr>
	{/if}

	<tr>
	<td>
	[<A HREF="?delete_csr={$csrInspect.auth_token}">Delete</A>]
	</td>
	<td></td>
	<td>
	[<A HREF="?sign_csr={$csrInspect.auth_token}">Approve</A>]
	</td>
	</tr>
	<tr></tr>

	<tr>
	<td></td>
	<td></td>
	<td></td>
	</tr>
    </table>
    </fieldset>
    </div>{* inspect_csr *}
<BR />    
{/if}

{* uploading new certificate via FILE *}
<div id="csr">
	<fieldset>
		<legend>Upload new CSR</legend>
		<BR />
		<P>
		Upload a local CSR for signing by the CA. If you created
		this with any globus-specific tools, you should look for
		the folder ".globus" in you home directory.
		</P>
		<BR />
		<table>
			<tr>
				<td>
					<form action="" method="post" enctype="multipart/form-data">
					<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
					<input type="file" name="user_csr" />
					<input type="submit" value="Upload CSR" />
					</form>
				</td>
			</tr>
			<tr></tr>
			<tr></tr>
		</table>
	</fieldset>
</div> {* id="csr" *}


