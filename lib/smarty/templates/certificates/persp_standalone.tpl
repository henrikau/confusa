<table style="width: 100%; table-layout: fixed; margin-left: 1em; padding-left: 0em">
	<tr></tr>
	{foreach from=$certList item=cert}
		{assign var='name' value=$cert.cert_owner}

		{if isset($cert.valid_untill)}
			{assign var='valid' value=$cert.valid_untill}
		{/if}

		{assign var='key' value=$cert.auth_key}
		{assign var='serial' value=$cert.serial}

		<tr>
		<td></td>
		<td>
		<i>{$key|escape}</i>
		</td>
		</tr>

		<tr>
		<td>
		Serial number: <B>{$serial|escape}</B>
		</td>
		</tr>

		<tr>
		<td>
		  <a href="download_certificate.php?email_cert={$key}">
		    <img src="graphics/email.png" alt=""
			 title="Send certificate via email" class ="url" /> Email
		  </a><br />

		  <a href="download_certificate.php?file_cert={$key}">
		    <img src="graphics/disk.png"
			 alt=""
			 title="Save certificate directly to disk"
			 class="url" />
		    Download Certificate
		  </a><br />

		  {if empty($inspectElement[$key])}
		  <a href="download_certificate.php?inspect_cert={$key}"
		     onclick="return inspectCertificateAJAX('{$key}');">
		    <img src="graphics/information.png"
			 alt=""
			 title="Inspect certificate details"
			 class="url" />
			 <span id="inspectText{$key}">
		    Inspect
			</span>
		  </a><br />
		  {/if}
		  <a href="download_certificate.php?delete_cert={$key}">
		    <img src="graphics/delete.png"
			 alt=""
			 title="Delete certificate from the database"
			 class="url" />
		    Delete
		  </a><br />
		</td>
		<td>
			{* Have the form wrap the table, otherwise it will not be legal HTML *}
		<form action="revoke_certificate.php" method="get">
		<div>
		{* Revoke-button *}
		<input type="hidden" name="revoke"		value="revoke_single" />
		<input type="hidden" name="order_number"	value="{$key|escape}" />
		<input type="hidden" name="reason"		value="unspecified" />
		<input type="submit" name="submit"		value="Revoke"
		       style=" background-color:#660000; color:#FFFFFF;"
		       onclick="return confirm('\t\tReally revoke certificate?\n\nAuth_key:       {$key}\nExpiry date:   {$cert.valid_untill|escape}')" />
		</div>
		</form>
		</td>
		<td></td>
		</tr>
		<tr>
		<td>{$cert.valid_untill|escape}</td>
		</tr>
		<tr><td colspan="3">
		<div id="inspectArea{$key|escape}">
			{if isset($inspectElement[$key])}
				{$inspectElement[$key]}
			{/if}
		</div>
		<br />
		</td></tr>
	{/foreach}
</table>