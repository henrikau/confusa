<div style="padding-top: 3em">
<fieldset>
<legend>{$l10n_legend_browsercsr}</legend>
<div id="info_view">
{if isset($order_number)}
<div id="pendingArea">
	<noscript>
		<p class="info">
		{$l10n_infotext_kgprocessing1} {$l10n_infotext_kgprocessing2}
		{$order_number} <a href="process_csr.php?install_cert={$order_number}">
		{$l10n_link_kgclickhere}</a> {$l10n_infotext_kgprocessing3}
		</p>
	</noscript>
	<script type="text/javascript">
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	var timer1 = setTimeout('window.location="process_csr.php?status_poll={$order_number}";', 10000);
	document.write('{$l10n_infotext_processing} {$order_number|escape}');
	document.writeln('<span id="dots"></span>');
	{* tell the end-user not to close the browser etc. *}
	document.writeln('{$l10n_infotext_brows_csr_ong}');
	showSmallishDots(0);
	</script>
</div>

	{if isset($done) && $done === TRUE}
		<script type="text/javascript">
			clearTimeout(timer1);
			document.getElementById("pendingArea").style.display = "none";
		</script>

		{$l10n_info_installcert1} <a href="process_csr.php?install_cert={$order_number|escape}">{$l10n_link_installcert}</a>
	{/if}
{else}
	<form method="post" action="process_csr.php">
	<table>
	<tr>
	<td style="width: 20%;">
	<keygen name="browserRequest" keytype="RSA" />
	</td>
	<td>
	<p class="info">{$l10n_infotext_kgkeysize1}
	<b> {$keysize|escape} </b> {$l10n_infotext_kgkeysize2}</p>
	</td>
	</tr><tr><td>
	</td>
	<td style="border-style: inset; border-width: 1px; padding: 0.5em">
	<p class="info" >
		<strong>{$l10n_label_finalCertDN}</strong>
	</p>
	<p style="font-size: 1em; font-family: monospace; margin-bottom: 1em">
		{$finalDN}
	</p>
	</td>
	</tr>
	<tr>
	<td>
		<input type="hidden" name="browserSigning" value="keygen" />
		<input type="submit" value="{$l10n_button_send}" />
	</td>
	<td>
	<p class="info" style="padding-top: 1em">
		{$l10n_infotext_sendonce}
	</p></td>
	</tr>
	</table>
	</form>
{/if}
</div>
</fieldset>
</div>
