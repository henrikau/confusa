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
		{$l10n_text_serial_number|escape}: <B>{$serial|escape}</B>
		</td>
		</tr>

		<tr>
		<td>
		  <a href="download_certificate.php?email_cert={$key}&amp;{$ganticsrf}">
		    <img src="graphics/email.png" alt=""
			 title="{$l10n_title_email|escape}" class ="url" /> {$l10n_item_email|escape}
		  </a><br />

		  <a href="download_certificate.php?file_cert={$key}&amp;{$ganticsrf}">
		    <img src="graphics/disk.png"
			 alt=""
			 title="{$l10n_title_download_cert|escape}"
			 class="url" />
		    {$l10n_item_download_cert|escape}
		  </a><br />

		  {if empty($inspectElement[$key])}
		  <a href="download_certificate.php?inspect_cert={$key}&amp;{$ganticsrf}"
		     onclick="return inspectCertificateAJAX('{$key}', '{$ganticsrf}');">
		    <img src="graphics/information.png"
			 alt=""
			 title="{$l10n_title_inspect|escape}"
			 class="url" />
			 <span id="inspectText{$key}">
		    {$l10n_item_inspect|escape}
			</span>
		  </a><br />
		  {/if}
		  <a href="download_certificate.php?delete_cert={$key}&amp;{$ganticsrf}">
		    <img src="graphics/delete.png"
			 alt=""
			 title="{$l10n_title_delete|escape}"
			 class="url" />
		    {$l10n_item_delete|escape}
		  </a><br />
		</td>
		<td>
			{* Have the form wrap the table, otherwise it will not be legal HTML *}
		<form action="download_certificate.php" method="get">
		<div>
		  {$panticsrf}
		{* Revoke-button *}
		<input type="hidden" name="revoke"		value="revoke_single" />
		<input type="hidden" name="order_number"	value="{$key|escape}" />
		<input type="hidden" name="reason"		value="unspecified" />
		<input type="submit" name="submit"		value="Revoke"
		       style=" background-color:#660000; color:#FFFFFF;"
		       onclick="return confirm('\t\t{$l10n_confirm_revoke1|escape}\n\n{$l10n_text_authkey|escape}      {$key}\n{$l10n_confirm_revoke2}:   {$cert.valid_untill|escape}')" />
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
