{assign var='table'	value='<div class="admin_table">'}
{assign var='table_e'	value='</div>'}
{assign var='tr'	value='<DIV CLASS="admin_table_row">'}
{assign var='tr_e'	value='</DIV>'}
{assign var='td'	value='<DIV CLASS="admin_table_cell">'}
{assign var='td_e'	value='</DIV>'}

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

{if !empty($csrInspect)}
    <H4>Inspect CSR</H4>	
    {$table}
	{$tr}
	{$td}{$td_e}{$td}{$td_e}
	{$tr_e}

	{* Auth Token *}
	{$tr}
	{$td}Auth token{$td_e}
	{$td}{$csrInspect.auth_token}{$td_e}
	{$tr_e}

	{* Country *}
	{if !empty($csrInspect.countryName)}
	{$tr}
	{$td}Country:{$td_e}
	{$td}{$csrInspect.countryName}{$td_e}
	{$tr_e}
	{/if}

	{* Organization name *}
	{if !empty($csrInspect.organizationName)}
	{$tr}
	{$td}Organization Name:{$td_e}
	{$td}{$csrInspect.organizationName}{$td_e}
	{$tr_e}
	{/if}

	{* Common-Name *}
	{if !empty($csrInspect.commonName)}
	{$tr}
	{$td}Common-Name:{$td_e}
	{$td}{$csrInspect.commonName}{$td_e}
	{$tr_e}
	{/if}

	{* Length of key *}
	{if !empty($csrInspect.length)}
	{$tr}
	{$td}Key length:{$td_e}
	{$td}{$csrInspect.length}{$td_e}
	{$tr_e}
	{/if}

	{* Uploaded *}
	{if !empty($csrInspect.length)}
	{$tr}
	{$td}Was uploaded:{$td_e}
	{$td}{$csrInspect.uploaded}{$td_e}
	{$tr_e}
	{/if}

	{* Remote IP *}
	{if !empty($csrInspect.length)}
	{$tr}
	{$td}IP:{$td_e}
	{$td}{$csrInspect.from_ip}{$td_e}
	{$tr_e}
	{/if}

	{$tr}
	{$td}
	[<A HREF="?delete_csr={$csrInspect.auth_token}">Delete</A>]
	{$td_e}
	{$td}
	[<A HREF="?sign_csr={$csrInspect.auth_token}">Approve</A>]
	{$td_e}
	{$tr_e}
	
    {$table_e}
    
{/if}
{if empty($csrList)}
No CSR in database
{else}
<h3>List of CSR</h3>

{$table}
	{$tr}
		{$td}<B>Upload date</B>{$td_e}
		{$td}{$td_e}
		{$td}<B>Common name</B>{$td_e}
		{$td}{$td_e}
		{$td}<B>From IP</B>{$td_e}
		{$td}{$td_e}
		{$td}<B>Inspect</B>{$td_e}
		{$td}{$td_e}
		{$td}<B>Delete</B>{$td_e}		
	{$tr_e}
	{foreach from=$csrList item=csr}
	{$tr}{$tr_e}
	{$tr}
		{$td}{$csr.uploaded_date}{$td_e}
		{$td} {$td_e}
		{$td}{$csr.common_name}{$td_e}
		{$td} {$td_e}
		{$td}{$csr.from_ip}{$td_e}
		{$td} {$td_e}
		{$td}[<a href="process_csr.php?inspect_csr={$csr.auth_key}">Inspect</a>]{$td_e}
		{$td} {$td_e}
		{$td}[<a href="process_csr.php?delete_csr={$csr.auth_key}">Delete</a>]{$td_e}
	{$tr_e}
	{/foreach}
{$table_e}
{/if}