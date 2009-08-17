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

{if ! empty($csrList)}
{$list_all_csr}
{/if}

{* uploading new certificate via FILE *}
{$upload_csr_file}

