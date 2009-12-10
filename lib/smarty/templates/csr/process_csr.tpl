<div>
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
{*
 * Generate CSR in the browser.
 *}
<fieldset>
<legend>Apply for a certificate in browser</legend>
<div id="info_view">
	<p class="info">Press the start button <b>once</b> to generate a certificate request in your browser.<br /><br />
	Sometimes it will take a little while until you can see a browser reaction and there
	can be delays between browser actions.</p>
</div>

	<br />
	<form id="startForm" method="post" action="process_csr.php">
	<input type="hidden" name="browserSigning" value="start" />
	{if $user_cert_enabled}
	<input type="submit" name="Send" id="startButton" value="Start" />
	{* Disable the element if the user does not have the right entitlement *}
	{else}
	<input disabled type="submit" name="Send" id="startButton" value="Start" />
	{/if}
	</form>
	</fieldset>
<div class="spacer"></div>
<div class="spacer"></div>

{*
 * Upload CSR from file
 *}
<div class="spacer"></div>
{$upload_csr_file}

<div class="spacer"></div>

{* uploading new CSR via POST *}
{include file='csr/paste_csr.tpl'}
</div>
