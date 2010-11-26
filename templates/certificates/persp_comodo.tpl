{if isset($ca_certificate)}
<div id="caNotification" style="float: right; border-style: solid; border-width: 1px; border-color: red; padding: 0.5em; margin-right: 1em">
	{$l10n_infotext_instcacert1} <a href="{$ca_certificate}">{$l10n_infotext_instcacert2}</a>!
</div>

{literal}
<script type="text/javascript">
	var caNot = document.getElementById("caNotification");
	caNot.style.display="none";

	function showCANotification()
	{
		caNot.style.display="block";
	}
</script>
{/literal}
{/if}

{if isset($certList)}
<table style="width: 100%; table-layout: fixed; padding: 1em 0em 1em
		1em; margin: 0em 0em 0em 0em" >
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
		  {if isset($cert.order_number) && $cert.status == "Awaiting Validation"}
		  <td>
		  <div class="waitnotification"><img src="graphics/ajax-loader.gif" alt="Processing..." /></div>
		  <script type="text/javascript">pollCertStatus({$cert.order_number}, 30000, '{$ganticsrf}');</script>
		  </td>
		  {/if}
		  {else}
		  {* valid certificate, show with normal graphics etc *}

		<td>
		  <!-- Send via email -->
		  <a href="download_certificate.php?email_cert={$cert.order_number}&amp;{$ganticsrf}">
		    <img src="graphics/email.png"
			 alt="{$l10n_title_email|escape}"
			 title="{$l10n_title_email|escape}"
			 class ="url" />
		    {$l10n_item_email|escape}
		  </a>
		  <br />

		  <!-- download as file -->
		  <a href="download_certificate.php?file_cert={$cert.order_number}&amp;{$ganticsrf}">
		    <img src="graphics/disk.png"
			 alt="{$l10n_title_download_cert|escape}"
			 title="{$l10n_title_download_cert|escape}"
			 class="url" />
		    {$l10n_item_download_cert|escape}
		  </a>
		  <br />

		  {if empty($inspectElement[$cert.order_number])}
		  <!-- Show details -->
		  <a href="download_certificate.php?inspect_cert={$cert.order_number}&amp;{$ganticsrf}"
		     onclick="return inspectCertificateAJAX('{$cert.order_number}', '{$ganticsrf}');">
		    <img src="graphics/information.png"
			 alt="{$l10n_title_inspect|escape}"
			 title="{$l10n_title_inspect|escape}"
			 class="url" />
			 <span id="inspectText{$cert.order_number}">
		    {$l10n_item_inspect|escape}
			</span>
		  </a>
		  <br />
		  {/if}

		  <!-- install into keystore in browser -->
		  <a href="download_certificate.php?install_cert={$cert.order_number}&amp;{$ganticsrf}"
		  {if isset($ca_certificate)}onclick="showCANotification()"{/if}>
		    <img src="graphics/database_add.png"
		    alt="{$l10n_title_install_ks|escape}"
		    title="{$l10n_title_install_ks|escape}"
		    class="url" />
		    {$l10n_item_install_ks|escape}
		  </a>
		  <br />
		</td>

		<!-- revoke single certificate -->
		  <td>
		    <form action="download_certificate.php" method="get">
		      <div>
			{$panticsrf}
			{* Revoke-button *}
			<input type="hidden" name="revoke"		value="revoke_single" />
			<input type="hidden" name="order_number"	value="{$cert.order_number|escape}" />
			<input type="hidden" name="reason"		value="unspecified" />
			<input type="submit" name="submit"		value="{$l10n_button_revoke|escape}"
			       style=" background-color:#660000; color:#FFFFFF;"
			       onclick="return confirm('\t\t{$l10n_confirm_revoke1|escape}\n\n{$l10n_text_ordernumber} {$cert.order_number|escape}\n{$l10n_confirm_revoke2|escape}     {$valid|escape}');" />
		      </div>
		    </form>
		  </td>
		  {/if} {* valid cert (not processd/revoked) *}

		</tr>

		<tr>
		  {if $cert.status == "Awaiting Validation" }
		  <td id="certInfoText{$cert.order_number|escape}"
		      style="color: gray; font-weight: bold">
		    {$l10n_status_processing|escape}
		  </td>

		  {elseif $cert.status === "Revoked"}
		  <td id="certInfoText{$cert.order_number|escape}"
		      style="color: red; font-weight: bold">
		    {$l10n_status_revoked|escape}
		  </td>

		  {else}
		  <td id="certInfoText{$cert.order_number|escape}">
		    {$cert.valid_untill|escape}
		  </td>
		  {/if}
		</tr>

		<tr>
		  <td colspan="3">
		    <div id="inspectArea{$cert.order_number|escape}">
		      {if isset($inspectElement[$cert.order_number])}
		      {$inspectElement[$cert.order_number]}
		      {/if}
		    </div>
		    <br />
		</td>
		</tr>
	{/foreach}{* each certificate in list *}
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

		<a href="download_certificate.php?certlist_all=true&amp;{$ganticsrf}">
		{$l10n_text_showall|escape} <img src="graphics/triangle_down.png" alt="Show older" style="border: none" /></a>
	{else}
		{if $numCerts == 0}
			{$l10n_text_novalid|escape}<br />
		{else}
			{$l10n_text_showingall|escape}<br />
		{/if}

		<a href="download_certificate.php?certlist_all=false&amp;{$ganticsrf}">
		{$l10n_text_hideold|escape} <img src="graphics/triangle_up.png"
						 alt="{$l10n_text_hideold|escape}"
						 style="border: none" /> </a>
	{/if}
</div>
{/if}
