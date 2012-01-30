<h3 id="heading">{$l10n_heading_step4browser}</h3>
<form method="post" action="browser_csr.php">
<fieldset>
<legend>{$l10n_legend_browsercsr}</legend>
<div id="info_view">
{if isset($order_number)}
<div id="pendingArea">
	<noscript>
		<p class="info">
		{$l10n_infotext_kgprocessing1} {$l10n_infotext_kgprocessing2}
		{$order_number} <a href="browser_csr.php?install_cert={$order_number}&amp;{$ganticsrf}">
		{$l10n_link_kgclickhere}</a> {$l10n_infotext_kgprocessing3}
		</p>
	</noscript>
	<script type="text/javascript">
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	var timer1 = setTimeout('window.location="browser_csr.php?status_poll={$order_number}&{$ganticsrf}";', 10000);
	document.write("{$l10n_infotext_processing} {$order_number|escape}");
	document.writeln('<img src="graphics/ajax-loader.gif" style="padding-left: 1em" alt="{$l10n_alt_processing}" />');
	{* tell the end-user not to close the browser etc. *}
	document.writeln("{$l10n_infotext_brows_csr_ong}");
	</script>
</div>
{else}
	  <div>{$panticsrf}</div>
	<table>
	<tr>
	<td id="keygenCell" style="width: 20%;">
	<keygen name="browserRequest" keytype="RSA" />
	</td>
	<td>
	<p class="info">{$l10n_infotext_kgkeysize1}
	<b> {$default_keysize|escape} </b> {$l10n_infotext_kgkeysize2}</p>
	<p class="info">{$l10n_infotext_kgkeysize3} {$min_keysize|escape} {$l10n_infotext_kgkeysize4}</p>
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
	</td>
	<td>
	<p class="info" style="padding-top: 1em">
		{$l10n_infotext_sendonce}
	</p></td>
	</tr>
	</table>

<script type="text/javascript">

var keysize={$default_keysize};

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

<div class="nav">
		{$panticsrf}
		<input id="nextButton" type="submit" title="{$l10n_button_next}" value="{$l10n_button_next} &gt;" />
</div>
</form>

<div class="nav">
<form action="receive_csr.php?{$ganticsrf}" method="get">
	<input id="backButton" type="submit" title="{$l10n_button_back}" value="&lt; {$l10n_button_back}" />
</form>
</div>

{if isset($order_number)}
<script type="text/javascript">
	$('#nextButton').hide();
	$('#backButton').hide();
</script>
{/if}
