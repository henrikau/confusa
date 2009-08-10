{if empty($certList)}
<h3>No certificates in database</h3>
{else}
	<DIV ID="csr">
	<FIELDSET>
	<LEGEND>Available Certificates</LEGEND>
	<table>
		<tr></tr>
		{foreach from=$certList item=cert}
			{assign var='key' value=$cert.auth_key}
			{assign var='name' value=$cert.cert_owner}
			{assign var='valid' value=$cert.valid_untill}
			{if $standalone}

				<tr>
				<td></td>
				<td>
				<I>{$key}</I>
				</td>
				</tr>
				<tr>
				<td></td>
				<td>
				[<a href="download_certificate.php?email_cert={$key}">Email</a>]
				[<a href="download_certificate.php?file_cert={$key}">Download</a>]
				{if $processingToken eq $key}
					<FONT COLOR="GRAY">[Inspect]</FONT>
				{else}
					[<a href="download_certificate.php?inspect_cert={$key}">Inspect</a>]
				{/if}
				[<a href="download_certificate.php?delete_cert={$key}">Delete</a>]
				</td>
				<td>
					{* Have the form wrap the table, otherwise it will not be legal HTML *}
				<FORM ACTION="revoke_certificate.php" METHOD="GET">
				{* Revoke-button *}
				<INPUT TYPE="hidden" NAME="revoke"		VALUE="revoke_single">
				<INPUT TYPE="hidden" NAME="order_number"	VALUE="{$key}">
				<INPUT TYPE="hidden" NAME="reason"		VALUE="unspecified">
				<INPUT TYPE="submit" NAME="submit"		VALUE="Revoke"
				       		     style=" background-color:#660000; color:#FFFFFF;" 
						     onclick="return confirm('\t\tReally revoke certificate?\n\nAuth_key:       {$key}\nExpiry date:   {$cert.valid_untill}')" />
				</FORM>
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
					[<a href="download_certificate.php?email_cert={$cert.order_number}">Email</a>]
					[<a href="download_certificate.php?file_cert={$cert.order_number}">Download</a>]
					[<a href="download_certificate.php?inspect_cert={$cert.order_number}">Inspect</a>]
					</td>
					<td>
						<FORM ACTION="revoke_certificate.php" METHOD="GET">
						{* Revoke-button *}
						<INPUT TYPE="hidden" NAME="revoke"		VALUE="revoke_single">
						<INPUT TYPE="hidden" NAME="order_number"	VALUE="{$cert.order_number}">
						<INPUT TYPE="hidden" NAME="reason"		VALUE="unspecified">
						<INPUT TYPE="submit" NAME="submit"		VALUE="Revoke"
										 style=" background-color:#660000; color:#FFFFFF;"
										onclick="return confirm('\t\tReally revoke certificate?\n\Order number: {$cert.order_number}\nExpiry date:     {$valid}')" />
						</FORM>
					</td>
				{/if}

				</tr>

				<tr>
				<td></td>
				{if is_null($valid)}
				<td><font color="gray"><b>Processing pending</b></font></td>
				{else}
				<td>{$cert.valid_untill}</td>
				{/if}
				</tr>
			{/if}
			<tr><td><br /></td></tr>
		{/foreach}
	</table>
	</FIELDSET>
	</DIV>
{/if} {* empty(certList) *}
{$processingResult}
