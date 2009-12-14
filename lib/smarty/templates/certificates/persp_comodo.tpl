	<table style="width: 100%; table-layout: fixed; margin-left: 1em; padding-left: 0em">
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
				[Email]
				[Download]
				[Inspect]
				[Install]
				</td>

				{if isset($cert.valid_untill) && isset($cert.order_number) && $cert.status == "Awaiting Validation"}
					<script type="text/javascript">pollCertStatus({$cert.order_number}, 30000)</script>
			{/if}
		{else}
		<td>
		  <a href="download_certificate.php?email_cert={$cert.order_number}">
		    <img src="graphics/email.png" alt=""
			 title="Send certificate via email" class ="url" /> Email
		  </a><br />

		  <a href="download_certificate.php?file_cert={$cert.order_number}">
		    <img src="graphics/disk.png"
			 alt=""
			 title="Save certificate directly to disk"
			 class="url" />
		    Download Certificate
		  </a>
		  <br />

		  {if empty($inspectElement[$cert.order_number])}
		  <a href="download_certificate.php?inspect_cert={$cert.order_number}"
		     onclick="return inspectCertificateAJAX('{$cert.order_number}');">
		    <img src="graphics/information.png"
			 alt=""
			 title="Inspect certificate details"
			 class="url" />
			 <span id="inspectText{$cert.order_number}">
		    Inspect
			</span>
		  </a>
		  <br />
		  {/if}
		  <a href="download_certificate.php?install_cert={$cert.order_number}">
		    <img src="graphics/database_add.png"
		    alt=""
		    title="Install certificate to keystore"
		    class="url" />
		    Install to keystore
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
				<input type="submit" name="submit"		value="Revoke"
								 style=" background-color:#660000; color:#FFFFFF;"
								onclick="return confirm('\t\tReally revoke certificate?\n\Order number: {$cert.order_number|escape}\nExpiry date:     {$valid|escape}')" />
					</div>
					</form>
				</td>
			{/if}

			</tr>

			<tr>
			{if $cert.status == "Awaiting Validation" }
			<td id="certInfoText{$cert.order_number|escape}"
			    style="color: gray; font-weight: bold">Processing pending</td>
			{elseif $cert.status === "Revoked"}
			<td id="certInfoText{$cert.order_number|escape}"
			    style="color: red; font-weight: bold">Revoked!!</td>
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

<div style="text-align: right; font-size: 0.9em; padding-bottom: 1em; padding-right: 1em">
	{if isset($showAll) && ($showAll===false)}
		Showing certificates ordered within the last {$defaultDays} days.<br />
		<a href="download_certificate.php?certlist_all=true">Show all valid certificates.</a>
	{else}
		Showing all valid certificates.<br />
		<a href="download_certificate.php?certlist_all=false">Hide older than {$defaultDays} days.</a>
	{/if}
</div>