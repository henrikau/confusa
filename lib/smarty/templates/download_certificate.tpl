{literal}
<script type="text/javascript">
<!--
	var timer = null;
	var timer2 = null;

	/**
	 * Show five incrementally appearing dots after a text, which visually
	 * hints that something is happening.
	 *
	 * @param orderNumber the orderNumber, needed to find the right info-block
	 */
	function showSmallDots(orderNumber) {
		var infoCell = document.getElementById('certInfoText' + orderNumber);
		var infoText = infoCell.innerHTML;

		var firstDot = infoText.indexOf(".");

		if (firstDot >= 0) {
			var numDots = infoText.substring(firstDot, infoText.length).length;
		}

		if (numDots >= 5) {
			infoText =  infoText.substring(0, infoText.indexOf(".") + 1);
		} else {
			infoText = infoText + ".";
		}

		infoCell.innerHTML = infoText;
	}

	/**
	 * Poll the processing status (Pending, processed) of an online-certificate
	 * asynchrously. Reload the page, if the processing of the certificate is
	 * done.
	 *
	 * @param orderNumber the orderNumber of the certificate
	 */
	function pollCertStatusAJAX(orderNumber) {
		var req = new XMLHttpRequest();

		req.open("GET", "?cert_status=" + orderNumber, true);
		req.send(null);
		req.onreadystatechange = function() {
			if (req.readyState == 4 /*complete*/) {
				if (req.status == 200) {
					if (req.responseText == "done") {
						/* reload the list if the processing is done */
						window.clearInterval(timer);
						window.clearInterval(timer2);
						window.location.reload();
					}
				} else {
					/* didn't work, so what? */
				}
			}
		}
	}

	/**
	 * Inspect a certificate by asynchrously getting the content of the
	 * certificate from Confusa. Switch the link labels between "Inspect" and
	 * "Collapse"
	 *
	 * @param key mixed the auth_key or order_number identifying the certificate
	 */
	function inspectCertificateAJAX(key) {
		var req = new XMLHttpRequest();
		var inspectArea = document.getElementById('inspectArea' + key);
		var inspectText = document.getElementById('inspectText' + key);

		/* if there is text in the inspect area, collapse instead of inspect */
		if (inspectText.innerHTML == "Collapse") {
			inspectArea.innerHTML = "";
			inspectText.innerHTML = "Inspect";
			return false;
		}

		req.open("GET", "?inspect_cert=" + key + "&ajax=true", true);
		req.send(null);
		req.onreadystatechange = function() {
			if (req.readyState == 4) {
				if (req.status == 200) {
					inspectArea.innerHTML = req.responseText;
					inspectText.innerHTML = "Collapse";
				} else {
					/* no op */
				}
			}
		}

		return false;
	}

	/**
	 * Wrapper function that calls both pollCertStatusAJAX and displays the
	 * funny little dots
	 *
	 * @param orderNumber integer the orderNumber of the certificate
	 * @param interval integer the interval in which the certificate status is
	 *                 polled
	 */
	function pollCertStatus(orderNumber, interval)
	{
		timer = window.setInterval("pollCertStatusAJAX(" + orderNumber + ")", interval);
		/* give some visual feedback to the user that "something is happening" */
		timer2 = window.setInterval("showSmallDots(" + orderNumber + ")", 2000);
	}

// -->
</script>
{/literal}


{if empty($certList)}
<h3>No certificates in database</h3>
{else}
	<div class="csr">
	<fieldset>
	<legend>Available Certificates</legend>
	<table>
		<tr><td></td></tr>
		{foreach from=$certList item=cert}
			{assign var='name' value=$cert.cert_owner}

			{if isset($cert.valid_untill)}
				{assign var='valid' value=$cert.valid_untill}
			{/if}

			{if $standalone}

				{assign var='key' value=$cert.auth_key}
				{assign var='serial' value=$cert.serial}
				<tr>
				<td></td>
				<td>
				<i>{$key|escape}</i>
				</td>
				</tr>

				<tr>
				<td></td>
				<td>
				Serial number: <B>{$serial|escape}</B>
				</td>
				</tr>


				<tr>
				<td></td>
				<td>
				  <a href="download_certificate.php?email_cert={$key}">
				    <img src="graphics/email.png" alt=""
					 title="Send certificate via email" class ="url" /> Email
				  </a><br />
				  
				  <a href="download_certificate.php?file_cert={$key}">
				    <img src="graphics/disk.png"
					 alt=""
					 title="Save certificate directly to disk"
					 class="url" />
				    Download Certificate
				  </a><br />

				  {if empty($inspectElement[$key])}
				  <a href="download_certificate.php?inspect_cert={$key}"
				     onclick="return inspectCertificateAJAX('{$key}');">
				    <img src="graphics/information.png"
					 alt=""
					 title="Inspect certificate details"
					 class="url" />
					 <span id="inspectText{$key}">
				    Inspect
					</span>
				  </a><br />
				  {/if}
				  <a href="download_certificate.php?delete_cert={$key}">
				    <img src="graphics/delete.png"
					 alt=""
					 title="Delete certificate from the database"
					 class="url" />
				    Delete
				  </a><br />
				</td>
				<td>
					{* Have the form wrap the table, otherwise it will not be legal HTML *}
				<form action="revoke_certificate.php" method="get">
				<div>
				{* Revoke-button *}
				<input type="hidden" name="revoke"		value="revoke_single" />
				<input type="hidden" name="order_number"	value="{$key|escape}" />
				<input type="hidden" name="reason"		value="unspecified" />
				<input type="submit" name="submit"		value="Revoke"
				       		     style=" background-color:#660000; color:#FFFFFF;" 
						     onclick="return confirm('\t\tReally revoke certificate?\n\nAuth_key:       {$key}\nExpiry date:   {$cert.valid_untill|escape}')" />
				</div>
				</form>
				</td>
				<td></td>
				</tr>
				<tr>
				<td></td>
				<td>{$cert.valid_untill|escape}</td>
				</tr>
				<tr><td colspan="3">
				<div id="inspectArea{$key|escape}">
					{if isset($inspectElement[$key])}
						{$inspectElement[$key]}
					{/if}
				</div>
				<br />
				</td></tr>
			{else}
				<tr>
				<td></td>
				<td>
				<i>{$cert.order_number|escape}</i>
				</td>
				</tr>

				<tr>
				<td></td>
				{if $cert.status == "Awaiting Validation" || $cert.status == "Revoked"}
					<td>
					[Email]
					[Download]
					[Inspect]
					[Install]
					</td>

					{if isset($cert.valid_untill) && isset($cert.order_number) && $cert.status == "Awaiting Validation"}
						<script type="text/javascript">pollCertStatus({$cert.order_number}, 30000)</script>
					{/if}
				{else}
				<td>
				  <a href="download_certificate.php?email_cert={$cert.order_number}">
				    <img src="graphics/email.png" alt=""
					 title="Send certificate via email" class ="url" /> Email
				  </a><br />
				  
				  <a href="download_certificate.php?file_cert={$cert.order_number}">
				    <img src="graphics/disk.png"
					 alt=""
					 title="Save certificate directly to disk"
					 class="url" />
				    Download Certificate
				  </a>
				  <br />

				  {if empty($inspectElement[$cert.order_number])}
				  <a href="download_certificate.php?inspect_cert={$cert.order_number}"
				     onclick="return inspectCertificateAJAX('{$cert.order_number}');">
				    <img src="graphics/information.png"
					 alt=""
					 title="Inspect certificate details"
					 class="url" />
					 <span id="inspectText{$cert.order_number}">
				    Inspect
					</span>
				  </a>
				  <br />
				  {/if}
				  <a href="download_certificate.php?install_cert={$cert.order_number}">
				    <img src="graphics/database_add.png"
				    alt=""
				    title="Install certificate to keystore"
				    class="url" />
				    Install to keystore
				  </a>
				  <br />

					</td>
					<td>
						<form action="revoke_certificate.php" method="get">
						<div>
						{* Revoke-button *}
						<input type="hidden" name="revoke"		value="revoke_single" />
						<input type="hidden" name="order_number"	value="{$cert.order_number|escape}" />
						<input type="hidden" name="reason"		value="unspecified" />
						<input type="submit" name="submit"		value="Revoke"
										 style=" background-color:#660000; color:#FFFFFF;"
										onclick="return confirm('\t\tReally revoke certificate?\n\Order number: {$cert.order_number|escape}\nExpiry date:     {$valid|escape}')" />
						</div>
						</form>
					</td>
				{/if}

				</tr>

				<tr>
				<td></td>
				{if $cert.status == "Awaiting Validation" }
				<td id="certInfoText{$cert.order_number|escape}"
				    style="color: gray; font-weight: bold">Processing pending</td>
				{elseif $cert.status === "Revoked"}
				<td id="certInfoText{$cert.order_number|escape}"
				    style="color: red; font-weight: bold">Revoked!!</td>
				{else}
				<td id="certInfoText{$cert.order_number|escape}">
				    {$cert.valid_untill|escape}</td>
				{/if}
				</tr>
				<tr><td colspan="3">
				<div id="inspectArea{$cert.order_number|escape}">
					{if isset($inspectElement[$cert.order_number])}
						{$inspectElement[$cert.order_number]}
					{/if}
				</div>
				<br />
				</td></tr>
			{/if}
		{/foreach}
	</table>
	</fieldset>
	</div>
{/if} {* empty(certList) *}

{if isset($processingResult)}
	{$processingResult|escape}
{/if}

{if isset($script)}
{$script}
{/if}
