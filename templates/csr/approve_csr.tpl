{if isset($signingOk) && $signingOk == true}
<div class="success">
<CENTER>
	{$l10n_infotext_certproc1}
	<a href="download_certificate.php?poll={$sign_csr}">{$l10n_link_here}</a>
</CENTER>
</div>
{/if}

{if (isset($csrInspect))}
	{include file='csr/inspect_csr.tpl'}
{/if}
