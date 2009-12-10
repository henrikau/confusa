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

	{if $standalone}
		{include file='certificates/persp_standalone.tpl'}
	{else}
		{include file='certificates/persp_comodo.tpl'}
	{/if}

	</fieldset>
	</div>
{/if} {* empty(certList) *}

{if isset($processingResult)}
	{$processingResult|escape}
{/if}

{if isset($script)}
{$script}
{/if}
