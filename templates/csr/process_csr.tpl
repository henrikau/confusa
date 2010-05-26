<p class="info">
  {$l10n_infotext_csroverview1}
</p>
<p class="info">
 {$l10n_infotext_csroverview2}
</p>
<p class="info">
  {$l10n_infotext_csroverview3}
</p>

<div class="tabheader">
  <ul class="tabs">
    <li><a href="?show=browser_csr&amp;{$ganticsrf}">{$l10n_tab_browsergen}</a></li>
    <li><a href="?show=upload_csr&amp;{$ganticsrf}">{$l10n_tab_uploadcsr}</a></li>
    <li><a href="?show=paste_csr&amp;{$ganticsrf}">{$l10n_tab_pastecsr}</a></li>
</ul>
</div>
<div class="spacer"></div>

{*
 * Approve CSR.
 * After a successful upload or paste, the CSR should be inspected.
 *}
{if $approve_csr}
{include file='csr/approve_csr.tpl'}
{/if}

{*
 * Generate CSR in the browser.
 *}
{if $browser_csr}
{include file='csr/browser_csr.tpl'}
{/if}

{*
 * Upload CSR from file
 *}
{if $upload_csr}
{include file='csr/upload_csr_file.tpl'}
{/if}

{*
 * uploading new CSR via POST
 *}
{if $paste_csr}
{include file='csr/paste_csr.tpl'}
{/if}

{*
 * Here comes JavaScript or another script executable by the browser (ActiveX?)
 *}
{if isset($deployment_script)}
	{$deployment_script}
{/if}
