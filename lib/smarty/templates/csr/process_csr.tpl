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

{if ! empty($csrList)}
{$list_all_csr}
{/if}

{* uploading new certificate via FILE *}
{$upload_csr_file}

<div class="spacer"></div>

{if empty($browserTemplate)}
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
{else}
	{$browserTemplate}
{/if}

{* This part will be JavaScript or another script executable by the browser (ActiveX?) *}
{if isset($deployment_script)}
	{$deployment_script}
{/if}

