<h3 id="heading">{$l10n_heading_step4browser}</h3>

{* Provide the Windows Vista/7 class factory *}
<object classid="clsid:884e2049-217d-11da-b2a4-000e7bbb2b09" id="classFactory" height="0" width="0" ></object>

{* big section with Windows certificate request JavaScript ahead *}
{literal}
<script type="text/javascript">
function checkWindowsRequest(request) {
		with (document.reqForm) {
			browserRequest.value = request;
			submit();
		}
}

function createIEVistaRequest(dn, keysize)
{
    try {
		// Declaration of the objects
		var objEnroll = classFactory.CreateObject("X509Enrollment.CX509Enrollment");
		var objPrivateKey = classFactory.CreateObject("X509Enrollment.CX509PrivateKey");
		var objRequest = classFactory.CreateObject("X509Enrollment.CX509CertificateRequestPkcs10");
		var objDN = classFactory.CreateObject("X509Enrollment.CX500DistinguishedName");
		var hashObjID = classFactory.CreateObject("X509Enrollment.CObjectId");
		hashObjID.InitializeFromName("89");

		var providerName = $('#providerSelector :selected').text();
		//alert(providerName + " " +  providerType);

		// Specify the name of the cryptographic provider.
		objPrivateKey.ProviderName = providerName;

		/* allow signing */
		//objPrivateKey.KeySpec = 0xffffff;
		objPrivateKey.Length=keysize;
		/* allow archival and plaintext export */
		objPrivateKey.ExportPolicy = 0x1;
		/* use "RSA-full" as the key algorithm */
        /* ProviderType breaks cert generation in Win8/IE10, turned it off*/
		//objPrivateKey.ProviderType = "1";
		objRequest.InitializeFromPrivateKey(1, objPrivateKey, "");

		// Comodo API does not support SHA-256 yet, specify SHA-1
		objRequest.HashAlgorithm = hashObjID;

		objDN.Encode(dn, 0);
		objRequest.Subject = objDN;
		objEnroll.InitializeFromRequest(objRequest);
		request = objEnroll.CreateRequest(1);
    } catch (e) {
		var message="Hit the following error upon key generation: " + e.description;
		alert(message);
		return false;
    }

    checkWindowsRequest(request);
    return false;
}
</script>
{/literal}

<form id="reqForm"
      name="reqForm"
      method="post"
      action="browser_csr.php"
      onsubmit="return createIEVistaRequest('{$dn}', {$default_keysize});">
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
	    <input type="hidden"
		   name="browserSigning"
		   value="vista7" />
	    {$panticsrf}
	  </div>
		<div id="csps">
		</div>
</div>

{* TODO: build the form with JavaScript, thus a user not having it enabled will not even see it - more user friendly *}
{if isset($order_number)}

<div id="pendingArea">
	<script type="text/javascript">
	document.getElementById('info_view').style.display = 'none';
	document.getElementById("reqDiv").style.display = "none";
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	var timer1 = setTimeout('window.location="browser_csr.php?status_poll={$order_number}&{$ganticsrf}";', 10000);
	document.write("{$l10n_infotext_processing} {$order_number|escape}");
	document.writeln('<img src="graphics/ajax-loader.gif" style="padding-left: 1em" alt="{$l10n_alt_processing}" />');
	document.writeln("{$l10n_infotext_brows_csr_ong}");
	</script>
</div>

{else}
<script type="text/javascript">
	{* let the user choose the CSP (Cryptographic service provider) *}
	var cryptProvText = "{$l10n_infotext_cryptprov} \"Microsoft Software Key Storage Provider\"!)";
	var cspErrorText = "{$l10n_infotext_csperror}";

	{literal}
	var infoView = document.getElementById("info_view");
	var requestForm = document.getElementById("reqForm");

	var cspInfo = classFactory.CreateObject("X509Enrollment.CCspInformation");
	var cspInfos = classFactory.CreateObject("X509Enrollment.CCspInformations");
	cspInfos.AddAvailableCsps();

	var select = $("<select></select>");
	select.attr('id','providerSelector');

	for (var i = 0; i < cspInfos.Count; i++) {
		var cspInfo = cspInfos.ItemByIndex(i);

		/* don't use unsafe cryptographic service providers */
		if (cspInfo.LegacyCsp == false) {
			var option = $("<option></option>");
			option.html(cspInfo.Name);

			select.append(option);
		}
	}


	$('#csps').append(select);
	$('#csps').append('<div class="spacer"></div>');
	$('#csps').append('<p class="info">' + cryptProvText + '</p>');
	{/literal}
</script>
{/if}
</fieldset>

<div class="nav">
		{$panticsrf}
		<input id="nextButton" type="submit" title="{$l10n_button_next}" value="{$l10n_button_next} &gt;" />
</div>
</form>

<form action="receive_csr.php?{$ganticsrf}" method="get">
<div class="nav">
	<input id="backButton" type="submit" title="{$l10n_button_back}" value="&lt; {$l10n_button_back}" />
</div>
</form>

{if isset($order_number)}
<script type="text/javascript">
$('#nextButton').hide();
$('#backButton').hide();
</script>
{/if}
