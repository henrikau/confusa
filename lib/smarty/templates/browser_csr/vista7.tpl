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
<legend>Apply for a certificate in browser</legend>
<div id="info_view">
		{* Provide the Windows Vista/7 class factory *}
		<object classid="clsid:884e2049-217d-11da-b2a4-000e7bbb2b09" id="classFactory" height="0" width="0" ></object>
		<noscript>
			<br />
			<b>Please activate JavaScript to enable browser key generation!</b>
		</noscript>
</div>

	<form id="reqForm" name="reqForm" method="post" action="process_csr.php" onsubmit="return createIEVistaRequest('{$dn}', {$keysize});">
		<input type="hidden" id="reqField" name="browserRequest" value="" />
		<input type="hidden" name="browserSigning" value="vista7" />
		<input type="submit" id="chooseButton" style="display: none" value="choose" />
	</form>
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
	pollStatus('Processing order number {$order_number|escape}.');

	{if $done === TRUE}
		clearTimeout(timer1);
		statusDone({$order_number|escape});
	{/if}
{else}
	{* let the user choose the CSP (Cryptographic service provider) *}
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
	infoView.innerHTML += "<p class=\"info\">Please pick a cryptographic service provider (For standard requirements, pick the \"Microsoft Software Key Storage Provider\"!)</p><br />";
	{/literal}
{/if}
</script>

