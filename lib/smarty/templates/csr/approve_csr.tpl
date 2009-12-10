{if isset($signingOk) && $signingOk == true}
<div class="success">
<CENTER>
	The certificate is now being processed by the CA (Certificate Authority)<BR />
	Depending on the load, this takes approximately 2 minutes.<BR /><BR />

	You will now be redirected to the certificate-download area found
	<a href="download_certificate.php?poll={$sign_csr}">here</a>
</CENTER>
</div>
{/if}

{if (isset($csrInspect))}
	{include file='csr/inspect_csr.tpl'}
{/if}