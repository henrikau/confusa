<div class="csr">
  <fieldset>
    <legend>Upload new CSR</legend>
    <br />
    <p class="info">
      Upload a local CSR for signing by the CA. If you created
      this with any globus-specific tools, you should look for
      the folder ".globus" in you home directory.
    </p>
    <br />
    <table>
      <tr>
	<td>
	  <form action="process_csr.php" method="post" enctype="multipart/form-data">
	    <div><!-- XHTML strict won't allow inputs just within forms -->
	      <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
	      <input type="file" name="user_csr" />
	      <input type="submit" value="Upload CSR" />
	    </div>
	  </form>
	</td>
      </tr>
    </table>
  </fieldset>

<div class="spacer"></div>

<fieldset>
<legend>Apply for a certificate in browser</legend>
<div id="info_view">
	<p class="info">Press the start button <b>once</b> to generate a certificate request in your browser.<br /><br />
	Sometimes it will take a little while until you can see a browser reaction and there
	can be delays between browser actions.</p>
</div>

<br />
<form name="reqForm" id="reqForm" onSubmit="return createRequest('{$dn}', {$keysize});" method="post" action="process_csr.php">
<input id="reqField" type="hidden" name="browserRequest" value="" />
{if $user_cert_enabled}
	<input type="submit" name="Send" id="startButton" value="Start" />
{else}
	{* Disable the element if the user does not have the right entitlement *}
	<input disabled type="submit" name=Send" id="startButton" value="Start" />
{/if}

<noscript>
	<b>Please activate JavaScript to enable browser key generation!</b>
</noscript>
</form>
</fieldset>

{if isset($order_number)}
<script type="text/javascript">
	{* No need to press "Start" once the order number is set *}
	var startButton = document.getElementById("startButton");
	startButton.setAttribute("style","display: none");
	{* IE workaround *}
	startButton.style.cssText = "display: none";
	{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
	setTimeout('window.location="process_csr.php?status_poll={$order_number}";', 10000);
	pollStatus('Processing order number {$order_number}.');

	{if $done === TRUE}
		statusDone({$order_number});
	{/if}
</script>
{/if}

{* This part will be JavaScript or another script executable by the browser (ActiveX?) *}
{if isset($deployment_script)}
	{$deployment_script}
{/if}
</div> <!-- upload csr -->
