<div style="padding-top: 3em">
<fieldset>
<legend>Apply for a certificate in browser</legend>
<div id="info_view">
{if isset($order_number)}
	<noscript>
		<p class="info">
		(You are viewing the JavaScript-less version of the page. Enabling JavaScript will give
		you extra comfort, such as visual progress feedback.)<br /><br />
		Please wait some time (around <b>2 minutes</b>) and then try to install the certificate with order-number
		{$order_number} by <a href="process_csr.php?install_cert={$order_number}">clicking here</a>. Thanks!
		</p>
	</noscript>
	<script type="text/javascript">
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	var timer1 = setTimeout('window.location="process_csr.php?status_poll={$order_number}";', 10000);
	pollStatus('Processing order number {$order_number|escape}.');

	{if isset($done) && $done === TRUE}
		clearTimeout(timer1);
		statusDone({$order_number|escape});
	{/if}
	</script>
{else}
	<form method="post" action="process_csr.php">
	<table>
	<tr>
	<td width="20%">
	<keygen name="browserRequest" keytype="RSA" />
	</td>
	<td>
	<p class="info">We strongly recommend to choose a key with keysize
	<b> {$keysize|escape} </b> bits. Please check which keysizes correspond to which "grades" in your browser!</p>
	</td>
	</tr><tr><td>
	<input type="hidden" name="browserSigning" value="keygen" />
	<input type="submit" value="Send" />
	</td><td><br /><p class="info">Please press the send button only <b>once</b>.</p></td>
	</tr></table></form>
{/if}
</div>
</fieldset>
</div>
