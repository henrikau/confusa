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
				<i>{$key}</i>
				</td>
				</tr>
				
				<tr>
				<td></td>
				<td>
				Serial number: <B>{$serial}</B>
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
				  <a href="download_certificate.php?inspect_cert={$key}">
				    <img src="graphics/information.png"
					 alt=""
					 title="Inspect certificate details"
					 class="url">
				    Inspect
				  </a><br />
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
				<input type="hidden" name="order_number"	value="{$key}" />
				<input type="hidden" name="reason"		value="unspecified" />
				<input type="submit" name="submit"		value="Revoke"
				       		     style=" background-color:#660000; color:#FFFFFF;" 
						     onclick="return confirm('\t\tReally revoke certificate?\n\nAuth_key:       {$key}\nExpiry date:   {$cert.valid_untill}')" />
				</div>
				</form>
				</td>
				<td></td>
				</tr>
				<tr>
				<td></td>
				<td>{$cert.valid_untill}</td>
				</tr>
			{else}
				<tr>
				<td></td>
				<td>
				<i>{$cert.order_number}</i>
				</td>
				</tr>

				<tr>
				<td></td>
				{if is_null($valid)}
					<td>
					[Email]
					[Download]
					[Inspect]
					</td>
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

				  <a href="download_certificate.php?inspect_cert={$cert.order_number}">
				    <img src="graphics/information.png"
					 alt=""
					 title="Inspect certificate details"
					 class="url">
				    Inspect
				  </a>
				  <br />

					</td>
					<td>
						<form action="revoke_certificate.php" method="get">
						<div>
						{* Revoke-button *}
						<input type="hidden" name="revoke"		value="revoke_single" />
						<input type="hidden" name="order_number"	value="{$cert.order_number}" />
						<input type="hidden" name="reason"		value="unspecified" />
						<input type="submit" name="submit"		value="Revoke"
										 style=" background-color:#660000; color:#FFFFFF;"
										onclick="return confirm('\t\tReally revoke certificate?\n\Order number: {$cert.order_number}\nExpiry date:     {$valid}')" />
						</div>
						</form>
					</td>
				{/if}

				</tr>

				<tr>
				<td></td>
				{if is_null($valid)}
				<td><span style="color: gray"><b>Processing pending</b></span></td>
				{else}
				<td>{$cert.valid_untill}</td>
				{/if}
				</tr>
			{/if}
			<tr><td><br /></td></tr>
		{/foreach}
	</table>
	</fieldset>
	</div>
{/if} {* empty(certList) *}
{$processingResult}
