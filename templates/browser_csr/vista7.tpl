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

		var providerSelector = document.getElementById("providerSelector");
		var providerName = providerSelector.options[providerSelector.selectedIndex].text;
		//alert(providerName + " " +  providerType);

		// Specify the name of the cryptographic provider.
		objPrivateKey.ProviderName = providerName;

		/* allow signing */
		//objPrivateKey.KeySpec = 0xffffff;
		objPrivateKey.Length=keysize;
		/* allow archival and plaintext export */
		objPrivateKey.ExportPolicy = 0x1;
		/* use "RSA-full" as the key algorithm */
		objPrivateKey.ProviderType = "1";
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

<fieldset>
<legend>{$l10n_legend_browsercsr}</legend>
<div id="info_view">
		{* Provide the Windows Vista/7 class factory *}
		<object classid="clsid:884e2049-217d-11da-b2a4-000e7bbb2b09" id="classFactory" height="0" width="0" ></object>
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
	<form id="reqForm"
	      name="reqForm"
	      method="post"
	      action="process_csr.php"
	      onsubmit="return createIEVistaRequest('{$dn}', {$keysize});">
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
	    <input type="submit"
		   id="chooseButton"
		   style="display: none"
		   value="{$l10n_button_choose}" />
	</form>
</div>

{* TODO: build the form with JavaScript, thus a user not having it enabled will not even see it - more user friendly *}
{if isset($order_number)}

<div id="pendingArea">
	<script type="text/javascript">
	document.getElementById('info_view').style.display = 'none';
	document.getElementById("reqDiv").style.display = "none";
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	var timer1 = setTimeout('window.location="process_csr.php?status_poll={$order_number}";', 10000);
	document.write('{$l10n_infotext_processing} {$order_number|escape}');
	document.writeln('<span id="dots"></span>');
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
			{$l10n_info_installcert1} <a href="process_csr.php?install_cert={$order_number|escape}">{$l10n_link_installcert}</a>
			{if isset($ca_certificate)}{$l10n_info_installcert2} <a href="{$ca_certificate}">{$l10n_link_cacert}</a>{/if}!
		</div>
	{/if}

{else}
<script type="text/javascript">
	{* let the user choose the CSP (Cryptographic service provider) *}
	var cryptProvText = '{$l10n_infotext_cryptprov} \"Microsoft Software Key Storage Provider\"!)';
	var cspErrorText = '{$l10n_infotext_csperror}';

	{literal}
	var infoView = document.getElementById("info_view");
	var requestForm = document.getElementById("reqForm");

	var cspInfo = classFactory.CreateObject("X509Enrollment.CCspInformation");
	var cspInfos = classFactory.CreateObject("X509Enrollment.CCspInformations");
	cspInfos.AddAvailableCsps();

	var select = document.createElement("select");
	select.id = "providerSelector";

	for (var i = 0; i < cspInfos.Count; i++) {
		var cspInfo = cspInfos.ItemByIndex(i);

		/* don't use unsafe cryptographic service providers */
		if (cspInfo.LegacyCsp == false) {
			var option = document.createElement("option");
			option.innerHTML = cspInfo.Name;

			select.appendChild(option);
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
</script>
{/if}
</fieldset>

