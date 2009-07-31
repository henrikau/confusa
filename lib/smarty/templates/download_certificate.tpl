{assign var='table'	value='<div class="admin_table">'}
{assign var='table_e'	value='</div><!--admin_table-->'}
{assign var='tr'	value='<DIV CLASS="admin_table_row">'}
{assign var='tr_e'	value='</DIV><!--admin_table_row-->'}
{assign var='td'	value='<DIV CLASS="admin_table_cell">'}
{assign var='td_e'	value='</DIV><!--admin_table_cell-->'}

{if empty($certList)}
<H3>No certificates in database</H3>
{else}
	<DIV ID="csr">
	<FIELDSET>
	<LEGEND>Available Certificates</LEGEND>
	{$table}
		{$tr}{$tr_e}
		{foreach from=$certList item=cert}
			{assign var='key' value=$cert.auth_key}
			{assign var='name' value=$cert.cert_owner}
			{assign var='valid' value=$cert.valid_untill}
			{if $standalone}

				{$tr}
				{$td}{$td_e}
				{$td}
				<I>{$key}</I>
				{$td_e}
				{$tr_e}

				{$tr}
				{$td}{$td_e}
				{$td}
				<FORM ACTION="revoke_certificate.php" METHOD="GET">
				[<a href="download_certificate.php?email_cert={$key}">Email</a>]
				[<a href="download_certificate.php?file_cert={$key}">Download</a>]
				{if $processingToken eq $key}
					<FONT COLOR="GRAY">[Inspect]</FONT>
				{else}
					[<a href="download_certificate.php?inspect_cert={$key}">Inspect</a>]
				{/if}
				[<a href="download_certificate.php?delete_cert={$key}">Delete</a>]
				
				{* Revoke-button *}
				<INPUT TYPE="hidden" NAME="revoke"		VALUE="revoke_single">
				<INPUT TYPE="hidden" NAME="order_number"	VALUE="{$key}">
				<INPUT TYPE="submit" NAME="submit"		VALUE="Revoke"
				       		     style=" background-color:#660000; color:#FFFFFF;" 
						     onclick="return confirm('\t\tReally revoke certificate?\n\nAuth_key:       {$key}\nExpiry date:   {$cert.valid_untill}')" />
				</FORM>
				{$td_e}
				{$td}{$td_e}
				{$tr_e}
				{$tr}
				{$td}{$td_e}
				{$td}{$cert.valid_untill}{$td_e}
				{$tr_e}
			{else}
				[<a href="download_certificate.php?email_cert={$cert.order_number}">Email</a>]
				[<a href="download_certificate.php?file_cert={$cert.order_number}">Download</a>]
				[<a href="download_certificate.php?inspect_cert={$cert.order_number}">Inspect</a>]<BR />
				{$cert.order_number}<BR />
				{$cert.cert_owner}<BR />
			{/if}
			<BR />
		{/foreach}
	{$table_e}
	</FIELDSET>
	</DIV>
{/if} {* empty(certList) *}
{$processingResult}
