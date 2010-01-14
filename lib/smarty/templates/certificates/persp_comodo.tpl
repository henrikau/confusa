<table style="width: 100%; table-layout: fixed; padding: 1em 0em 1em 1em; margin: 0em 0em 0em 0em">
		{foreach from=$certList item=cert}
			{assign var='name' value=$cert.cert_owner}

			{if isset($cert.valid_untill)}
				{assign var='valid' value=$cert.valid_untill}
			{/if}
		<tr>
			<td>
			<i>{$cert.order_number|escape}</i>
			</td>
			</tr>

			<tr>
			{if $cert.status == "Awaiting Validation" || $cert.status == "Revoked"}
				<td>
				[{$l10n_item_email|escape}]
				[{$l10n_item_download|escape}]
				[{$l10n_item_inspect|escape}]
				[{$l10n_item_install|escape}]
				</td>

				{if isset($cert.valid_untill) && isset($cert.order_number) && $cert.status == "Awaiting Validation"}
					<script type="text/javascript">pollCertStatus({$cert.order_number}, 30000)</script>
			{/if}
		{else}
		<td>
		  <a href="download_certificate.php?email_cert={$cert.order_number}">
		    <img src="graphics/email.png" alt=""
			 title="{$l10n_title_email|escape}" class ="url" /> {$l10n_item_email|escape}
		  </a><br />

		  <a href="download_certificate.php?file_cert={$cert.order_number}">
		    <img src="graphics/disk.png"
			 alt=""
			 title="{$l10n_title_download_cert|escape}"
			 class="url" />
		    {$l10n_item_download_cert|escape}
		  </a>
		  <br />

		  {if empty($inspectElement[$cert.order_number])}
		  <a href="download_certificate.php?inspect_cert={$cert.order_number}"
		     onclick="return inspectCertificateAJAX('{$cert.order_number}');">
		    <img src="graphics/information.png"
			 alt=""
			 title="{$l10n_title_inspect|escape}"
			 class="url" />
			 <span id="inspectText{$cert.order_number}">
		    {$l10n_item_inspect|escape}
			</span>
		  </a>
		  <br />
		  {/if}
		  <a href="download_certificate.php?install_cert={$cert.order_number}">
		    <img src="graphics/database_add.png"
		    alt=""
		    title="{$l10n_title_install_ks|escape}"
		    class="url" />
		    {$l10n_item_install_ks|escape}
		  </a>
		  <br />

			</td>
			<td>
				<form action="revoke_certificate.php" method="get">
				<div>
				{* Revoke-button *}
				<input type="hidden" name="revoke"		value="revoke_single" />
				<input type="hidden" name="order_number"	value="{$cert.order_number|escape}" />
				<input type="hidden" name="reason"		value="unspecified" />
				<input type="submit" name="submit"		value="{$l10n_button_revoke|escape}"
								 style=" background-color:#660000; color:#FFFFFF;"
								onclick="return confirm('\t\t{$l10n_confirm_revoke1|escape}\n\n{$l10n_text_ordernumber} {$cert.order_number|escape}\n{$l10n_confirm_revoke2|escape}     {$valid|escape}')" />
					</div>
					</form>
				</td>
			{/if}

			</tr>

			<tr>
			{if $cert.status == "Awaiting Validation" }
			<td id="certInfoText{$cert.order_number|escape}"
			    style="color: gray; font-weight: bold">{$l10n_status_processing|escape}</td>
			{elseif $cert.status === "Revoked"}
			<td id="certInfoText{$cert.order_number|escape}"
			    style="color: red; font-weight: bold">{$l10n_status_revoked|escape}</td>
			{else}
			<td id="certInfoText{$cert.order_number|escape}">
			    {$cert.valid_untill|escape}</td>
			{/if}
			</tr>
			<tr><td colspan="3">
			<div id="inspectArea{$cert.order_number|escape}">
				{if isset($inspectElement[$cert.order_number])}
					{$inspectElement[$cert.order_number]}
				{/if}
			</div>
			<br />
			</td></tr>
	{/foreach}
</table>

<div style="padding: 0em 0em 1em 1em; font-size: 0.9em">
{assign var='numCerts' value=$certList|@count}
	{if isset($showAll) && ($showAll===false)}
		{if $numCerts == 0}
			{$l10n_status_nonew|escape} {$defaultDays} {$l10n_status_days|escape}.<br />
		{else}
			<p>
			{$l10n_status_certhist|escape} {$defaultDays} {$l10n_status_days|escape}.</p>
		{/if}

		<a href="download_certificate.php?certlist_all=true">
		{$l10n_text_showall|escape} <img src="graphics/triangle_down.png" alt="Show older" style="border: none" /></a>
	{else}
		{if $numCerts == 0}
			{$l10n_text_novalid|escape}<br />
		{else}
			{$l10n_text_showingall|escape}<br />
		{/if}

		<a href="download_certificate.php?certlist_all=false">
		{$l10n_text_hideold|escape} <img src="graphics/triangle_up.png" alt="Hide older" style="border: none" /> </a>
	{/if}
</div>
