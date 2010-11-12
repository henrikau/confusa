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
	 * @param anticsrf the Anti-CSRF token used to prevent malicious
	 *		   code. If not set, the portal will block the request.
	 */
	function pollCertStatusAJAX(orderNumber, anticsrf) {
		var req = new XMLHttpRequest();

		req.open("GET", "?cert_status=" + orderNumber + "&" + anticsrf, true);
		req.send(null);
		req.onreadystatechange = function() {
			if (req.readyState == 4 /*complete*/) {
				if (req.status == 200) {
					if (req.responseText == "done") {
						/* reload the list if the processing is done */
						window.clearInterval(timer);
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
	 * @param anticsrf the Anti-CSRF token used to prevent malicious
	 *		   code. If not set, the portal will block the request.
	 */
	function inspectCertificateAJAX(key, anticsrf) {
		var req = new XMLHttpRequest();
		var inspectArea = document.getElementById('inspectArea' + key);
		var inspectText = document.getElementById('inspectText' + key);

		/* if there is text in the inspect area, collapse instead of inspect */
		if (inspectText.innerHTML == "Collapse") {
			inspectArea.innerHTML = "";
			inspectText.innerHTML = "Inspect";
			return false;
		}

		req.open("GET", "?inspect_cert=" + key + "&ajax=true&" + anticsrf, true);
		req.send(null);
		req.onreadystatechange = function() {
			if (req.readyState == 4) {
				if (req.status == 200) {
					var certHTML = req.responseText;

					if (certHTML.substring(0,8) == "Success:") {
						certHTML=certHTML.substring(8,certHTML.length);
						inspectArea.innerHTML = certHTML;
						inspectText.innerHTML = "Collapse";
					} else {
						/* force the user to login again */
						window.location.reload();
					}

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
	 * @param anticsrf the Anti-CSRF token used to prevent malicious
	 *		   code. If not set, the portal will block the request.
	 */
	function pollCertStatus(orderNumber, interval, anticsrf)
	{
		timer = window.setInterval("pollCertStatusAJAX(" + orderNumber + "," + anticsrf + ")", interval);
	}

// -->
</script>
{/literal}

<div class="csr">
<fieldset>
<legend>{$l10n_legend_availcerts|escape}</legend>

<div id="certbar">
	<a id="newcert" href="confirm_aup.php">
		<img src="graphics/new-certificate.png" alt="{$l10n_alt_newcertificate}" />
		<span> {$l10n_item_newcertificate}</span>
	</a>

	<a id="revokecert" href="revoke_certificate.php">
		<img src="graphics/revoke-all.png" alt="{$l10n_alt_revokeall}" />
		<span> {$l10n_item_revokeall}</span>
	</a>
</div>

{if $standalone}
	{include file='certificates/persp_standalone.tpl'}
{else}
	{include file='certificates/persp_comodo.tpl'}
{/if}

</fieldset>
</div>

{if isset($processingResult)}
	{$processingResult|escape}
{/if}

{if isset($script)}
{$script}
{/if}
