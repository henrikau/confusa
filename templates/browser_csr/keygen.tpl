<div style="padding-top: 3em">
<fieldset>
<legend>{$l10n_legend_browsercsr}</legend>
<div id="info_view">
{if isset($order_number)}
<div id="pendingArea">
	<noscript>
		<p class="info">
		{$l10n_infotext_kgprocessing1} {$l10n_infotext_kgprocessing2}
		{$order_number} <a href="process_csr.php?install_cert={$order_number}&amp;{$ganticsrf}">
		{$l10n_link_kgclickhere}</a> {$l10n_infotext_kgprocessing3}
		</p>
	</noscript>
	<script type="text/javascript">
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	var timer1 = setTimeout('window.location="process_csr.php?status_poll={$order_number}&{$ganticsrf}";', 10000);
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

		<div style="margin-top: 1em">
			{$l10n_info_installcert1} <a href="process_csr.php?install_cert={$order_number|escape}&amp;{$ganticsrf}">{$l10n_link_installcert}</a>
			{if isset($ca_certificate)}{$l10n_info_installcert2} <a href="{$ca_certificate}">{$l10n_link_cacert}</a>{/if}!
		</div>
	{/if}
{else}
	<form method="post" action="process_csr.php">
	  <div>{$panticsrf}</div>
	<table>
	<tr>
	<td id="keygenCell" style="width: 20%;">
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

<script type="text/javascript">

var keysize={$keysize};

{literal}
	var keygenCell = document.getElementById("keygenCell");
	var options = keygenCell.getElementsByTagName("option");

	/* Gecko based browsers use some strange "Grade" syntax for keylengths - replace*/
	if (navigator.userAgent.indexOf('Gecko') != -1) {
		/* do not touch the constants!
		 * You will break key generation otherwise */
		var GECKO_STRING_HIGH = "High Grade";
		var GECKO_STRING_MEDIUM = "Medium Grade";

		for (var i = 0; i < options.length; i++) {
			var option = options[i];

			if (option.text == GECKO_STRING_HIGH) {
				option.text = "2048 bits";
				option.value=GECKO_STRING_HIGH;
			} else if (option.text == GECKO_STRING_MEDIUM) {
				option.text = "1024 bits";
				option.value=GECKO_STRING_MEDIUM;
			}
		}
	}

	/* autoselect the option with the right keysize */
	for (var i = 0; i < options.length; i++) {
		var option = options[i];

		if (option.text.indexOf(keysize) != -1) {
			option.selected = true;
			break;
		}
	}
</script>
{/literal}
{/if}
</div>
</fieldset>
</div>
