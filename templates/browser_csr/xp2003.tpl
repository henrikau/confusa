<h3 id="heading">{$l10n_heading_step4browser}</h3>

{* Provide the Windows XP/Server 2003 class factory *}
<object id="XEnroll" classid="clsid:127698e4-e730-4e5c-a2b1-21490a70c8a1" codebase="xenroll.dll"></object>
{* big section with Windows certificate request JavaScript ahead *}
{literal}
<script type="text/javascript">
var timer="";

function checkWindowsRequest(request) {
		with (document.reqForm) {
			browserRequest.value = request;
			submit();
		}
}

function createIEXPRequest(dn, keysize)
{
    XEnroll.Reset();
    XEnroll.ProviderType = 1;
    /* Note that the "base provider" will only allow for RSA keys with a maximum
     * of 512 bits due to former export restrictions - therefore use the
     * enhanced cryptographic provider */
	var providerName = $('#providerSelector :selected').text();
    XEnroll.ProviderName = providerName;
    /* create the key with the right keysize (upper 16 bits)
     * flag 1 (crypt_exportable) as the export policy for the private key */
    XEnroll.GenKeyFlags=(keysize<<16)+1;
    XEnroll.HashAlgID = 0x8004;
    XEnroll.KeySpec = 1;
    var request = XEnroll.CreatePKCS10(dn, "1.3.6.1.5.5.7.3.2");
    checkWindowsRequest(request);
    return false;
}
</script>
{/literal}

<form id="reqForm"
	      name="reqForm"
	      method="post"
	      action="browser_csr.php" onsubmit="return createIEXPRequest('{$dn}', {$default_keysize});">
<fieldset>
<legend>{$l10n_legend_browsercsr}</legend>
<div id="info_view">

		<noscript>
			<br />
			<b>{$l10n_infotext_reqjs}</b>
		</noscript>

	<div style="border-style: inset; border-width: 1px; padding: 0.5em">
		<p class="info" >
			<strong>{$l10n_label_finalCertDN}</strong>
		</p>
		<p style="font-size: 1em; font-family: monospace; margin-bottom: 1em">
			{$finalDN}
		</p>
	</div>
</div>

<div id="reqDiv" style="margin-top: 2em">
	  <div>
	    <input type="hidden"
		   id="reqField"
		   name="browserRequest"
		   value="" />
	    {$panticsrf}
	  </div>
	  <div id="csps">
	  </div>
</div>

{* TODO: build the form with JavaScript, thus a user not having it enabled will not even see it - more user friendly *}
{if isset($order_number)}
<div id="pendingArea">
	<script type="text/javascript">
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	document.getElementById('info_view').style.display = 'none';
	document.getElementById("reqDiv").style.display = "none";
	var timer1 = setTimeout('window.location="browser_csr.php?status_poll={$order_number}&{$ganticsrf}";', 10000);
	document.write("{$l10n_infotext_processing} {$order_number|escape}");
	document.writeln('<img src="graphics/ajax-loader.gif" style="padding-left: 1em" alt="{$l10n_alt_processing}" />');
	document.writeln("{$l10n_infotext_brows_csr_ong}");
	</script>
</div>
{else}
<script type="text/javascript">
	var cryptProvText = "{$l10n_infotext_cryptprov} \"Microsoft Enhanced Cryptographic Provider\"!)";
	var cspErrorText = "{$l10n_infotext_csperror}";

	{literal}
	/* let the user choose the CSP (Cryptographic service provider) */
	var select = $("<select></select>");
	select.attr('id','providerSelector');

	XEnroll.Reset();
	XEnroll.ProviderType = 1;

	try {
		for (var cspNo = 0;; cspNo++) {
			var option = $("<option></option>");
			option.html(XEnroll.EnumProviders(cspNo, 0));
			select.append(option);
		}
	} catch (e) {
		if (e.number == -2147024637) {
			/* no-op, done with iterating the CSPs */
		} else {
			alert(cspErrorText + e.description);
		}
	}

	$('#csps').append(select);
	$('#csps').append('<div class="spacer"></div>');
	$('#csps').append('<p class="info">' + cryptProvText + '</p>');
	/* make it only visible if the user has JavaScript enabled */

	{/literal}
</script>
{/if}
</fieldset>
<div class="nav">
		{$panticsrf}
		<input id="nextButton" type="submit" title="{$l10n_button_next}" value="{$l10n_button_next} >" />
</div>
</form>

<div class="nav">
<form action="receive_csr.php?{$ganticsrf}" method="get">
	<input id="backButton" type="submit" title="{$l10n_button_back}" value="< {$l10n_button_back}" />
</form>
</div>

{if isset($order_number)}
<script type="text/javascript">
$('#nextButton').hide();
$('#backButton').hide();
</script>
{/if}
