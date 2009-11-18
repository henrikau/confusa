{literal}
<script type="text/javascript">
	var timer = null;

	function pollCertStatusAJAX(orderNumber) {
		var req = new XMLHttpRequest();

		req.open("GET", "?cert_status=" + orderNumber, true);
		req.send(null);
		req.onreadystatechange = function() {
			if (req.readyState == 4 /*complete*/) {
				if (req.status == 200) {
					if (req.responseText == "done") {
						/* reload the list if the processing is done */
						window.clearInterval(timer);
						window.location.reload();
					}
				} else {
					/* didn't work, so what? */
				}
			}
		}
	}

	function pollCertStatus(orderNumber, interval)
	{
		timer = window.setInterval("pollCertStatusAJAX(" + orderNumber + ")", interval);
	}
</script>
{/literal}


{if empty($certList)}
<h3>No certificates in database</h3>
{else}
	<div class="csr">
	<fieldset>
	<legend>Available Certificates</legend>
	<table>
		<tr><td></td></tr>
		{foreach from=$certList item=cert}
			{assign var='key' value=$cert.auth_key}
			{assign var='serial' value=$cert.serial}
			{assign var='name' value=$cert.cert_owner}
			{assign var='valid' value=$cert.valid_untill}

			{if $standalone}

				<tr>
				<td></td>
				<td>
				<i>{$key|escape}</i>
				</td>
				</tr>

				<tr>
				<td></td>
				<td>
				Serial number: <B>{$serial|escape}</B>
				</td>
				</tr>


				<tr>
				<td></td>
				<td>
				  <a href="download_certificate.php?email_cert={$key}">
				    <img src="graphics/email.png" alt=""
					 title="Send certificate via email" class ="url"> Email
				  </a><br />
				  
				  <a href="download_certificate.php?file_cert={$key}">
				    <img src="graphics/disk.png"
					 alt=""
					 title="Save certificate directly to disk"
					 class="url">
				    Download Certificate
				  </a><br />

				  {if empty($inspectElement[$key])}
				  <a href="download_certificate.php?inspect_cert={$key}">
				    <img src="graphics/information.png"
					 alt=""
					 title="Inspect certificate details"
					 class="url">
				    Inspect
				  </a><br />
				  {/if}
				  <a href="download_certificate.php?delete_cert={$key}">
				    <img src="graphics/delete.png"
					 alt=""
					 title="Delete certificate from the database"
					 class="url">
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
				<td></td>
				<td>{$cert.valid_untill|escape}</td>
				</tr>
				<tr><td colspan="3">
				<div id="inspect_area">
					<br />
					{if isset($inspectElement[$key])}
						{$inspectElement[$key]}
					{/if}
				</div>
				</td></tr>
			{else}
				<tr>
				<td></td>
				<td>
				<i>{$cert.order_number|escape}</i>
				</td>
				</tr>

				<tr>
				<td></td>
				{if $cert.status == "Awaiting Validation" || $cert.status == "Revoked"}
					<td>
					[Email]
					[Download]
					[Inspect]
					[Install]
					</td>

					{if isset($cert.valid_untill) && isset($cert.order_number)}
						<script type="text/javascript">pollCertStatus({$cert.order_number}, 30000)</script>
					{/if}
				{else}
				<td>
				  <a href="download_certificate.php?email_cert={$cert.order_number}">
				    <img src="graphics/email.png" alt=""
					 title="Send certificate via email" class ="url"> Email
				  </a><br />
				  
				  <a href="download_certificate.php?file_cert={$cert.order_number}">
				    <img src="graphics/disk.png"
					 alt=""
					 title="Save certificate directly to disk"
					 class="url">
				    Download Certificate
				  </a>
				  <br />

				  {if empty($inspectElement[$cert.order_number])}
				  <a href="download_certificate.php?inspect_cert={$cert.order_number}">
				    <img src="graphics/information.png"
					 alt=""
					 title="Inspect certificate details"
					 class="url">
				    Inspect
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
				<td></td>
				{if $cert.status == "Awaiting Validation" }
				<td><span style="color: gray"><b>Processing pending</b></span></td>
				{elseif $cert.status === "Revoked"}
				<td><span style="color: red"><b>Revoked!!</b></span></td>
				{else}
				<td>{$cert.valid_untill|escape}</td>
				{/if}
				</tr>
				<tr><td colspan="3">
				<div id="inspect_area">
					<br />
					{if isset($inspectElement[$cert.order_number])}
						{$inspectElement[$cert.order_number]}
					{/if}
				</div>
				</td></tr>
			{/if}
		{/foreach}
	</table>
	</fieldset>
	</div>
{/if} {* empty(certList) *}
{$processingResult|escape}
{if isset($script)}
{$script}
{/if}
