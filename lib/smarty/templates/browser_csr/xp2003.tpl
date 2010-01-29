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
	var providerSelector = document.getElementById("providerSelector");
	var providerName = providerSelector.options[providerSelector.selectedIndex].text;
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

<fieldset>
<legend>{$l10n_legend_browsercsr}</legend>
<div id="info_view">
		{* Provide the Windows XP/Server 2003 class factory *}
		<object id="XEnroll" classid="clsid:127698e4-e730-4e5c-a2b1-21490a70c8a1" codebase="xenroll.dll"></object>
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

<div style="margin-top: 2em">
	<form id="reqForm" name="reqForm" method="post" action="process_csr.php" onsubmit="return createIEXPRequest('{$dn}', {$keysize});">
		<input type="hidden" id="reqField" name="browserRequest" value="" />
		<input type="hidden" name="browserSigning" value="xp2003" />
		<input type="submit" id="chooseButton" style="display: none" value="{$l10n_button_choose}" />
	</form>
</div>
<br />
</fieldset>

{* TODO: build the form with JavaScript, thus a user not having it enabled will not even see it - more user friendly *}
<script type="text/javascript">
{if isset($order_number)}
	{* No need to press "Start" once the order number is set *}
	var chooseButton = document.getElementById("chooseButton");
	{* IE workaround *}
	chooseButton.style.cssText = "display: none";
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	var timer1 = setTimeout('window.location="process_csr.php?status_poll={$order_number}";', 10000);
	pollStatus('{$l10n_infotext_processing} {$order_number|escape}.');

	{if isset($done) && $done === TRUE}
		clearTimeout(timer1);
		statusDone({$order_number|escape});
	{/if}
{else}
	var cryptProvText = '{$l10n_infotext_cryptprov} \"Microsoft Enhanced Cryptographic Provider\"!)';
	var cspErrorText = '{$l10n_infotext_csperror}';

	{literal}
	/* let the user choose the CSP (Cryptographic service provider) */
	var infoView = document.getElementById("info_view");
	var requestForm = document.getElementById("reqForm");

	var select = document.createElement("select");
	select.id = "providerSelector";

	XEnroll.Reset();
	XEnroll.ProviderType = 1;

	try {
		for (var cspNo = 0;; cspNo++) {
			var option = document.createElement("option");
			option.innerHTML = XEnroll.EnumProviders(cspNo, 0);
			select.appendChild(option);
		}
	} catch (e) {
		if (e.number == -2147024637) {
			/* no-op, done with iterating the CSPs */
		} else {
			alert(cspErrorText + e.description);
		}
	}

	var chooseButton = document.getElementById("chooseButton");
	requestForm.insertBefore(select, chooseButton);
	requestForm.insertBefore(document.createElement("br"), chooseButton);
	/* make it only visible if the user has JavaScript enabled */
	chooseButton.style.cssText = "display: block";

	var spacer = document.createElement("div");
	spacer.setAttribute("class", "spacer");
	infoView.appendChild(spacer);
	infoView.innerHTML += "<p class=\"info\">" + cryptProvText + "</p>";
	{/literal}
{/if}
</script>
