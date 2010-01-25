<p class="info">
  Please choose one of the options available below to create your
  certificate.
</p>
<p class="info">
  You have several different ways of creating the certificate, ranging
  from a complete in-browser experience to different ways of uploading
  an existing CSR.
</p>
<p class="info">
  If you are unsure about what an CSR is, you probably want the
  in-browser approach.
</p>

<div class="tabheader">
  <ul class="tabs">
    <li><a href="?show=browser_csr">Browser generation</a></li>
    <li><a href="?show=upload_csr">Upload CSR</a></li>
    <li><a href="?show=paste_csr">Paste CSR</a></li>
</ul>
</div>
<div class="spacer"></div>

{*
 * Approve CSR.
 * After a successful upload or paste, the CSR should be inspected.
 *}
{if $approve_csr}
{include file='csr/approvce_csr.tpl'}
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
